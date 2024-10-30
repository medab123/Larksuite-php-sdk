<?php

namespace Larksuit\SDK\Models\Docs\Folder;

use Larksuit\SDK\LarkService;
use GuzzleHttp\Exception\GuzzleException;

class FolderService
{

    private LarkService $larkService;

    protected array $apiUrls = [
        'create-base' => 'bitable/v1/apps',
    ];
    private string $folderId;

    public function __construct(LarkService $larkService,string $folderId)
    {
        $this->larkService = $larkService;
        $this->folderId = $folderId;
    }

    /**
     * Create a new base.
     *
     * @param string $folderId The ID of the folder where the base will be created.
     * @param string $name The name of the new base.
     * @return array The response from the API.
     * @throws GuzzleException
     */
    public function createBase(string $folderId, string $name): array
    {
        $uri = $this->larkService->buildUrl($this->apiUrls['base'], 'create-base');
        $response = $this->larkService->client->post($uri, ['json' => ['name' => $name, 'folder_token' => $folderId]]);
        return json_decode($response->getBody()->getContents(), true);
    }

}
