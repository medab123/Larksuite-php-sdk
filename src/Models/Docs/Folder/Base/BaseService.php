<?php

namespace Larksuit\SDK\Models\Docs\Folder\Base;

use Larksuit\SDK\LarkService;

class BaseService
{
    public LarkService $larkService;
    public string $baseId;

    protected array $apiUrls = [
        'list-tables' => 'bitable/v1/apps/:baseId/tables',
        'create-table' => 'bitable/v1/apps/:baseId/tables',
    ];

    /**
     * BaseService constructor.
     *
     * @param LarkService $larkService The LarkService instance.
     * @param string $baseId The ID of the base.
     */
    public function __construct(LarkService $larkService, string $baseId)
    {
        $this->larkService = $larkService;
        $this->baseId = $baseId;
    }

    /**
     * List tables in the base.
     *
     * @return array The response from the API.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function listTables(): array
    {
        $uri = $this->larkService->buildUrl($this->apiUrls, 'list-tables', ['baseId' => $this->baseId]);
        $response = $this->larkService->client->get($uri);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Create a new table in the base.
     *
     * @param string $name The name of the table.
     * @param array $fields The fields for the table.
     * @param string $defaultViewName The name of the default view.
     * @return array The response from the API.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTable(string $name, array $fields, string $defaultViewName = 'Grid'): array
    {
        $uri = $this->larkService->buildUrl($this->apiUrls, 'create-table', ['baseId' => $this->baseId]);
        $table = [
            'name' => $name,
            'default_view_name' => $defaultViewName,
            'fields' => $fields
        ];
        $response = $this->larkService->client->post($uri, ['json' => ['table' => $table]]);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Open a table and return a TableService instance.
     *
     * @param string $tableId The ID of the table.
     * @return TableService The TableService instance.
     */
    public function openTable(string $tableId): TableService
    {
        return new TableService($this, $tableId);
    }

    /**
     * Close the service and return the LarkService instance.
     *
     * @return LarkService The LarkService instance.
     */
    public function close(): LarkService
    {
        return $this->larkService;
    }
}
