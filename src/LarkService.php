<?php

namespace Larksuit\SDK;

use Larksuit\SDK\Models\Bot\BotChatService;
use Larksuit\SDK\Models\Docs\DocsService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class LarkService
{
    public Client $client;
    protected string $appId;
    protected string $appSecret;
    private string $baseUri;

    protected array $apiUrls = [
        'auth' => [
            'get-tenant-token' => 'auth/v3/tenant_access_token/internal',
            'get-user-token' => 'authen/v1/oidc/access_token',
        ],
        'base' => [
            'tables' => 'bitable/v1/apps/:baseId/tables',
            'create' => 'bitable/v1/apps',
        ],
        'table' => [
            'records' => 'bitable/v1/apps/:baseId/tables/:tableId/records',
            'create' => 'bitable/v1/apps/:baseId/:tableId/records',
            'write' => 'bitable/v1/apps/:baseId/tables/:tableId/records/batch_create',
            'delete-records' => 'bitable/v1/apps/:baseId/tables/:tableId/records/batch_delete',
        ],
        'bot' => [
            'send-message' => '/im/v1/messages?receive_id_type=chat_id'
        ]
    ];

    /**
     * LarkService constructor.
     * Initializes the Guzzle client and authenticates.
     * @throws Exception
     */
    public function __construct()
    {
        $this->appId = config('larksuit.app_id');
        $this->appSecret = config('larksuit.app_secret');
        $this->baseUri = config('larksuit.base_uri');

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);

        $this->authenticate();
    }

    /**
     * Authenticate and set the authorization header.
     *
     * @throws Exception
     */
    /**
     * Authenticate and set the authorization header.
     *
     * @throws Exception
     */
    public function authenticate(): void
    {
        // Retrieve the token from cache or request a new one
        $token = cache()->remember('lark_service_tenant_token', now()->addHours(1), function () {
            return $this->getTokenFromLark();
        });

        // Set the Authorization header with the token
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    /**
     * Get a new token from Lark API.
     *
     * @return string
     * @throws Exception
     */
    private function getTokenFromLark(): string
    {
        $larkCredential = [
            "app_id" => $this->appId,
            "app_secret" => $this->appSecret
        ];

        try {
            $response = $this->client->post($this->buildUrl($this->apiUrls['auth'], 'get-tenant-token'), [
                'json' => $larkCredential
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('Failed to get tenant token. Status code: ' . $response->getStatusCode());
            }

            $result = json_decode($response->getBody()->getContents(), true);
            $token = $result['tenant_access_token'] ?? '';

            if (!$token) {
                throw new Exception('Token not found in the response.');
            }

            return $token;
        } catch (GuzzleException $e) {
            throw new Exception('GuzzleException: ' . $e->getMessage());
        }
    }

    /**
     * @return DocsService
     */
    public function useDocs(): DocsService
    {
        return new DocsService($this);
    }

    /**
     * @param string $chatId
     * @return BotChatService
     */
    public function useBotChat(string $chatId): BotChatService
    {
        return new BotChatService($this,'chat_id',$chatId);
    }

    /**
     * Build URL with parameters.
     *
     * @param array $serviceUrls The service URL patterns.
     * @param string $action The action to build the URL for.
     * @param array $params Optional parameters to replace placeholders in the URL.
     * @return string The built URL.
     * @throws Exception
     */
    public function buildUrl(array $serviceUrls, string $action, array $params = []): string
    {
        if (!isset($serviceUrls[$action])) {
            throw new Exception("URL pattern for {$action} does not exist.");
        }

        $url = $serviceUrls[$action];

        foreach ($params as $key => $value) {
            $placeholder = ":{$key}";
            if (str_contains($url, $placeholder)) {
                $url = str_replace($placeholder, $value, $url);
            } else {
                throw new Exception("Placeholder {$placeholder} not found in URL pattern.");
            }
        }

        return $url;
    }
}
