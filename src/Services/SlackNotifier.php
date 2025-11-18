<?php

namespace G4\Egg\Services;

use G4\Egg\Models\CaughtException;

class SlackNotifier
{
    public static function send(CaughtException $exception): bool
    {
        $webhookUrl = config('egg.slack_webhook_url');
        if (!$webhookUrl) {
            return false;
        }

        $message = $exception->message ?? 'No message';
        $file    = $exception->file ?? 'unknown';
        $line    = $exception->line ?? 0;
        $class   = $exception->exception_class ?? 'Exception';
        $trace   = $exception->trace ?? '';

        // truncate long trace to avoid Slack issues
        $trace = mb_strimwidth($trace, 0, 3000, "\n... [truncated]");

        $payload = [
            'text' => "Exception: {$message} in {$file}:{$line}", // fallback
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '🚨 Exception Reported',
                        'emoji' => true
                    ]
                ],
                [
                    'type' => 'section',
                    'fields' => [
                        ['type' => 'mrkdwn', 'text' => "*Message:*\n{$message}"],
                        ['type' => 'mrkdwn', 'text' => "*Type:*\n`{$class}`"],
                        ['type' => 'mrkdwn', 'text' => "*File:*\n`{$file}:{$line}`"],
                    ]
                ],
                ['type' => 'divider'],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Stack Trace:*\n```{$trace}```"
                    ]
                ]
            ]
        ];

        // Use curl instead of file_get_contents
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $result = curl_exec($ch);
        $error  = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false || $status >= 400) {
            logger()->error("SlackNotifier failed: {$error}, HTTP status {$status}");
            return false;
        }

        return true;
    }
}