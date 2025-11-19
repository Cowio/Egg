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
        $webhookUrl = config("egg.slack_webhook_url");
        if (!$webhookUrl) {
            return false;
        }

        $payload = json_encode([
            "text" => "Fallback tekst: En alvorlig fejl er sket.",
            "blocks" => [
                [
                    "type" => "header",
                    "text" => [
                        "type" => "plain_text",
                        "text" => "🚨 Ny Exception Rapporteret (EggBot)"
                    ]
                ],
                [
                    "type" => "section",
                    "fields" => [
                        [
                            "type" => "mrkdwn",
                            "text" => "*Type:*\n`RuntimeException`"
                        ],
                        [
                            "type" => "mrkdwn",
                            "text" => "*Miljø:*\n`production`"
                        ],
                        [
                            "type" => "mrkdwn",
                            "text" => "*Fil:*\n`/var/www/html/app/Services/EggLayer.php:42`"
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
                        "text" => "*Besked:*\n`Something about eggs went terribly wrong.`"
                    ]
                ],
                [
                    "type" => "section",
                    "text" => [
                        "type" => "mrkdwn",
                        "text" => "*Stack Trace (Uddrag):*\n```\n#0 [internal function]: App\\Services\\EggLayer->hatch()\n#1 /var/www/html/vendor/laravel/framework/src/Illuminate/Container/Container.php:803\n#2 /var/www/html/public/index.php:56\n```"
                    ]
                ]
            ]
        ]);
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


