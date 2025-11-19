<?php

namespace G4\Egg\Services;

use G4\Egg\Models\CaughtException;

class SlackNotifier
{
    /**
     * Send a message to Slack using the webhook URL from the environment.
     *
     * @param $message
     * @return bool true on success, false on failure
     */
    public static function send($content): bool
    {
        $webhookUrl = config("egg.slack_webhook_url");
        if (!$webhookUrl) {
            dump("Slack webhook URL is not configured.");
            return false;
        }

        if ($content instanceof CaughtException) {
            dump("Sending exception to Slack: ", $content);

            $exceptionClass = $content->exception_class ?? "Not Found";
            $message = $content->message ?? "Not Found";
            $file = $content->file ?? "Not Found";
            $line = $content->line ?? "Not Found";
            $trace = $content->trace ?? "Not Found";
            $category = $content->category ?? "Not Found";

            $data = [
                "text" => "Fallback tekst: En alvorlig fejl er sket.",
                "blocks" => [
                    [
                        "type" => "header",
                        "text" => [
                            "type" => "plain_text",
                            "text" => "🚨 New Exception Reported (EggBot)"
                        ]
                    ],
                    [
                        "type" => "section",
                        "fields" => [
                            [
                                "type" => "mrkdwn",
                                "text" => "*Type:*\n`{$exceptionClass}`"
                            ],
                            [
                                "type" => "mrkdwn",
                                "text" => "*Category:*\n`{$category}`"
                            ],
                            [
                                "type" => "mrkdwn",
                                "text" => "*Path:*\n`{$file}:{$line}`"
                            ]
                        ]
                    ],
                    [
                        "type" => "divider"
                    ],
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "*Message:*\n`{$message}`"
                        ]
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

            $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        else
        {
            dump("Sending message to Slack: ", $content);
            $payload = json_encode([
                "text" => $content
            ]);
        }

        $options = [
            "http" => [
                "method"  => "POST",
                "header"  => "Content-Type: application/json\r\n",
                "content" => $payload,
                "timeout" => 5,
            ],
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($webhookUrl, false, $context);
        return $result !== false;
    }
}


