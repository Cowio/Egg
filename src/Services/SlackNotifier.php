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
        dump($message);
        $webhookUrl = getenv('slack_webhook_url');
        dump($webhookUrl);
        if (!$webhookUrl) {
            return false;
        }

        $payload = json_encode(['text' => $message]);
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
        dump("done");
        return $result !== false;
    }
}

