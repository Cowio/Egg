<?php

namespace G4\Egg\Services;

use G4\Egg\Models\CaughtException;

class SlackNotifier
{
    /**
     * Send a formatted exception message to Slack.
     *
     * @param \Throwable|string $exception
     * @return bool true on success, false on failure
     */
    public static function send($exception): bool
    {
        $webhookUrl = config('egg.slack_webhook_url');
        if (!$webhookUrl) {
            return false;
        }

        // If a Throwable was passed, format it nicely
        if ($exception instanceof CaughtException) {
            $message = $exception->message;
            $file    = $exception->file;
            $line    = $exception->line;
            $class   = $exception->exception_class ?? 'Exception';
            $trace   = $exception->trace ?? '';

            $payload = [
                "blocks" => [
                    [
                        "type" => "header",
                        "text" => [
                            "type" => "plain_text",
                            "text" => "🚨 Exception Reported",
                            "emoji" => true
                        ]
                    ],
                    [
                        "type" => "section",
                        "fields" => [
                            [
                                "type" => "mrkdwn",
                                "text" => "*Message:*\n{$message}"
                            ],
                            [
                                "type" => "mrkdwn",
                                "text" => "*Type:*\n`{$class}`"
                            ],
                            [
                                "type" => "mrkdwn",
                                "text" => "*File:*\n`{$file}:{$line}`"
                            ],
                        ]
                    ],
                    [
                        "type" => "divider"
                    ],
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "*Stack Trace:*\n```{$trace}```"
                        ]
                    ]
                ]
            ];
        } else {
            // Fallback: raw messages still formatted as code block
            $payload = [
                "text" => "```{$exception}```"
            ];
        }

        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => json_encode($payload),
                'timeout' => 5,
            ],
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($webhookUrl, false, $context);

        return $result !== false;
    }
}
