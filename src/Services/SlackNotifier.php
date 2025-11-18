<?php

namespace G4\Egg\Services;

class SlackNotifier
{
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
                        'text' => $message,
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


