<?php

namespace G4\Egg\Services;

class SlackNotifier
{
    private const MAX_BLOCK_TEXT = 2900;   // Slight margin under Slack's ~3000 limit
    private const MAX_FALLBACK_TEXT = 3000;
    /**
     * Send a message to Slack using the webhook URL from the environment.
     *
     * @param string $message
     * @return bool true on success, false on failure
     */
    public static function send(string $message): bool
    {
        $webhookUrl = config('egg.slack_webhook_url');
        if (!$webhookUrl) {
            return false;
        }

        if (function_exists('mb_substr')) {
            $blockText = mb_substr($message, 0, self::MAX_BLOCK_TEXT, 'UTF-8');
            $fallback  = mb_substr($message, 0, self::MAX_FALLBACK_TEXT, 'UTF-8');
        } else {
            $blockText = substr($message, 0, self::MAX_BLOCK_TEXT);
            $fallback  = substr($message, 0, self::MAX_FALLBACK_TEXT);
        }

        $payload = json_encode
        ([
            'text' => "Der er sket en fejl din spasser",
            'blocks' =>
            [
                [
                    'type' => 'section',
                    'text' =>
                    [
                        'type' => 'mrkdwn',
                        'text' => $blockText,
                        dump($message),
                    ],
                ]
            ]
        ]);
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 5,
            ],
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($webhookUrl, false, $context);
        return $result !== false;
    }
}


