<?php

namespace Larksuit\SDK\Models\Docs;

use Larksuit\SDK\LarkService;
use Larksuit\SDK\Models\Docs\Folder\Base\BaseService;
use Larksuit\SDK\Models\Docs\Folder\FolderService;

class DocsService
{

    private LarkService $larkService;

    public function __construct(LarkService $larkService)
    {
        $this->larkService = $larkService;
    }

    /**
     * @param string $folderId
     * @return FolderService
     */
    public function openFolder(string $folderId): FolderService
    {
        return new FolderService($this->larkService, $folderId);
    }
    /**
     * Open a base and return a BaseService instance.
     *
     * @param string $baseId The ID of the base.
     * @return BaseService The BaseService instance.
     */
    public function openBase(string $baseId): BaseService
    {
        return new BaseService($this->larkService, $baseId);
    }
}
