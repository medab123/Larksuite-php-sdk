<?php

namespace Larksuit\SDK\Models\Docs\Folder\Base;

use Larksuit\SDK\LarkService;
use GuzzleHttp\Exception\GuzzleException;

class TableService
{
    private LarkService $larkService;
    private string $baseId;
    private string $tableId;
    private BaseService $baseService;

    protected array $apiUrls = [
        'list-records' => 'bitable/v1/apps/:baseId/tables/:tableId/records',
        'batch-create' => 'bitable/v1/apps/:baseId/tables/:tableId/records/batch_create',
        'batch-delete' => 'bitable/v1/apps/:baseId/tables/:tableId/records/batch_delete',
    ];

    /**
     * TableService constructor.
     *
     * @param BaseService $baseService The BaseService instance.
     * @param string $tableId The ID of the table.
     */
    public function __construct(BaseService $baseService, string $tableId)
    {
        $this->larkService = $baseService->larkService;
        $this->baseService = $baseService;
        $this->baseId = $baseService->baseId;
        $this->tableId = $tableId;
    }

    /**
     * List records in the table.
     *
     * @return array The response from the API.
     * @throws GuzzleException
     */
    public function listRecords(): array
    {
        $uri = $this->larkService->buildUrl($this->apiUrls, 'list-records', ['baseId' => $this->baseId, 'tableId' => $this->tableId]);
        $response = $this->larkService->client->get($uri);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Batch create records in the table.
     *
     * @param array $records The records to be created.
     * @return mixed The response from the API.
     * @throws \Exception|GuzzleException
     */
    public function batchCreateRecords(array $records): mixed
    {
        $globalResponse = [];
        $uri = $this->larkService->buildUrl($this->apiUrls, 'batch-create', ['baseId' => $this->baseId, 'tableId' => $this->tableId]);

        $collection = collect($records)->chunk(1000);
        foreach ($collection->toArray() as $chunk) {
            $prepared = $this->prepareForInsertBatch(array_values($chunk));
            $response = $this->larkService->client->post($uri, ['json' => $prepared]);
            $globalResponse[]  =json_decode($response->getBody()->getContents(), true);
            sleep(1);
        }
        return $globalResponse;
    }

    /**
     * Delete all records from the table.
     *
     * @param int $totalRecords The total number of records deleted.
     * @return int The total number of records deleted.
     * @throws GuzzleException
     */
    public function deleteAllRecords(int $totalRecords = 0): int
    {
        $records = $this->listRecords();
        if ($records && isset($records['data']['items'])) {
            $totalRecords += (int)$records['data']['total'];
            $hasMore = $records['data']['has_more'];
            $recordIds = array_column($records['data']['items'], 'record_id');
            $this->batchDeleteRecords($recordIds);
            if ($hasMore) {
                $this->deleteAllRecords($totalRecords);
            }
            return $totalRecords;
        }
        return -1;
    }

    /**
     * Batch delete records from the table.
     *
     * @param array $recordIds The IDs of the records to be deleted.
     * @return mixed The response from the API.
     * @throws \Exception|GuzzleException
     */
    public function batchDeleteRecords(array $recordIds): mixed
    {
        $uri = $this->larkService->buildUrl($this->apiUrls, 'batch-delete', ['baseId' => $this->baseId, 'tableId' => $this->tableId]);
        $response = $this->larkService->client->post($uri, ['json' => ['records' => $recordIds]]);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Close the service and return the BaseService instance.
     *
     * @return BaseService The BaseService instance.
     */
    public function close(): BaseService
    {
        return $this->baseService;
    }

    /**
     * Transform records into the required format for batch creation.
     *
     * @param array $records The records to be transformed.
     * @return array The transformed records.
     */
    private function prepareForInsertBatch(array $records): array
    {
        $transformedRecords = array_map(fn($record) => ['fields' => $record], $records);
        return ['records' => $transformedRecords];
    }
}
