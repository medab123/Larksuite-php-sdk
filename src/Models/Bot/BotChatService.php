<?php

namespace Larksuit\SDK\Models\Bot;

use Larksuit\SDK\LarkService;
use Exception;

class BotChatService
{
    private LarkService $larkService;
    protected array $apiUrls = [
        'send-message' => 'im/v1/messages?receive_id_type=:receive_id_type'
    ];
    private string $receiveIdType;
    protected string $receiveId;
    protected string $appEnv;
    protected string $appName;


    public function __construct(LarkService $larkService, string $receiveIdType, string $receiveId)
    {
        $this->larkService = $larkService;
        $this->receiveIdType = $receiveIdType;
        $this->receiveId = $receiveId;
        $this->appEnv = env('APP_ENV', config('larksuit.app_env', 'local'));
        $this->appName = ucfirst(env('APP_NAME', config('larksuit.app_name', 'larksuit')));

    }

    /**
     * Sends an error notification to Lark.
     */
    public function sendErrorNotification(string $type,string $message, ?Exception $exception = null): array
    {
        $title = "{$this->appName} Notification | {$this->appEnv} | $type | Error";
        $content = $this->formatMessage('Error Message: ', $message);

        if ($exception) {
            $this->addExceptionDetails($content, $exception);
        }

        return $this->sendPost($title, $content);
    }

    /**
     * Sends a warning notification to Lark.
     */
    public function sendWarningNotification(string $type,string $message, ?array $context = []): array
    {
        $title = "{$this->appName} Notification | {$this->appEnv} | $type | Warning";
        $content = $this->formatMessage('Warning Message: ', $message);

        if (!empty($context)) {
            $this->addContextDetails($content, $context);
        }

        return $this->sendPost($title, $content);
    }

    /**
     * Sends an informational notification to Lark.
     */
    public function sendInfoNotification(string $type,string $message, ?array $context = []): array
    {
        $title = "{$this->appName} Notification | {$this->appEnv} | $type | Info";

        $content = $this->formatMessage('Info Message: ', $message);

        if (!empty($context)) {
            $this->addContextDetails($content, $context);
        }

        return $this->sendPost($title, $content);
    }

    /**
     * Formats a message with a specified label and main text.
     */
    private function formatMessage(string $label, string $text): array
    {
        return [
            [
                ['tag' => 'text', 'text' => $label, 'style' => ['bold']],
                ['tag' => 'text', 'text' => $text],
            ]
        ];
    }

    /**
     * Adds exception details to the content.
     */
    private function addExceptionDetails(array &$content, Exception $exception): void
    {
        $content[] = [
            ['tag' => 'text', 'text' => 'Exception Type: ', 'style' => ['bold']],
            ['tag' => 'text', 'text' => get_class($exception), 'style' => ['italic']],
        ];
        $content[] = [
            ['tag' => 'text', 'text' => 'Exception Message: ', 'style' => ['bold']],
            ['tag' => 'text', 'text' => $exception->getMessage()],
        ];
        $content[] = [
            ['tag' => 'text', 'text' => 'Stack Trace: ', 'style' => ['bold']],
            ['tag' => 'md', 'text' => "```\n" . $exception->getTraceAsString() . "\n```"],
        ];
    }

    /**
     * Adds context details to the content, formatted as a code block.
     */
    private function addContextDetails(array &$content, array $context): void
    {
        $formattedContext = json_encode($context, JSON_PRETTY_PRINT);
        $content[] = [
            ['tag' => 'text', 'text' => 'Context: ', 'style' => ['bold']],
            ['tag' => 'md', 'text' => "```\n" . $formattedContext . "\n```"],
        ];
    }

    /**
     * Sends a post message to Lark and logs any errors encountered.
     */
    private function sendPost(string $title, array $content): array
    {
        $uri = $this->larkService->buildUrl($this->apiUrls, 'send-message', ['receive_id_type' => $this->receiveIdType]);
        $messagePayload = [
            'receive_id' => $this->receiveId,
            'msg_type' => 'post',
            'content' => json_encode([
                'zh_cn' => [
                    'title' => $title,
                    'content' => $content
                ]
            ])
        ];

        try {
            $response = $this->larkService->client->post($uri, ['json' => $messagePayload]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            logger()->error("Failed to send Lark notification: {$e->getMessage()}", [
                'exception' => $e,
                'uri' => $uri,
                'payload' => $messagePayload,
            ]);
            return ['error' => 'Failed to send notification', 'message' => $e->getMessage()];
        }
    }
}
