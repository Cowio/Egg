<?php

namespace G4\Egg\Services;

use Throwable;

class SlackNotifier
{
    /**
     * Predefined templates. Each template returns an array payload (without webhook url) when built.
     * You can add more templates here or override dynamically by calling registerTemplate.
     */
    protected static array $templates = [
        'exception' => [self::class, 'buildExceptionTemplate'],
        'simple' => [self::class, 'buildSimpleTemplate'],
        'success' => [self::class, 'buildSuccessTemplate'],
        'warning' => [self::class, 'buildWarningTemplate'],
        'info' => [self::class, 'buildInfoTemplate'],
        // New detailed card-style exception template
        'exception_card' => [self::class, 'buildExceptionCardTemplate'],
    ];

    /**
     * Register or override a template at runtime.
     */
    public static function registerTemplate(string $name, callable $builder): void
    {
        self::$templates[$name] = $builder;
    }

    /**
     * Send a message to Slack using the webhook URL from the environment.
     * Backward compatible: if only message provided, sends plain text.
     * Options may include:
     *  - template: string template name
     *  - data: array data passed to template builder
     *  - blocks, attachments: direct Slack payload overrides
     *  - username, icon_emoji, channel, thread_ts, unfurl_links, unfurl_media
     */
    public static function send($message, array $options = []): bool
    {
        // If a template is requested, delegate with merged data.
        if (isset($options['template'])) {
            $normalized = self::normalizeExceptionData($message) ?? [];
            $data = array_merge($normalized, $options['data'] ?? []);
            if (!isset($data['fallback_text']) && is_string($message)) {
                $data['fallback_text'] = $message;
            }
            return self::sendTemplate($options['template'], $data, $options);
        }

        // Auto-detect exception-like payloads and use default template.
        $defaultTemplate = config('egg.slack_default_template', 'exception_card');
        if ($tplData = self::normalizeExceptionData($message)) {
            return self::sendTemplate($defaultTemplate, $tplData, $options);
        }

        // If associative array payload with explicit blocks/attachments
        if (is_array($message) && (isset($message['blocks']) || isset($message['attachments']))) {
            $base = self::buildBasePayload($message['text'] ?? ($options['text'] ?? ''), $options);
            unset($base['text']);
            $payload = array_merge($base, $message);
            return self::postToWebhook($payload);
        }

        // Fallback to plain text.
        $text = is_string($message) ? $message : json_encode($message);
        $payload = self::buildBasePayload($text ?: '', $options);
        return self::postToWebhook($payload);
    }

    /**
     * Send using a named template.
     */
    public static function sendTemplate(string $template, array $data = [], array $options = []): bool
    {
        if (!isset(self::$templates[$template])) {
            // Fallback to simple text if template not found.
            return self::send('[Unknown template] ' . ($data['fallback_text'] ?? ''), $options);
        }

        $builder = self::$templates[$template];
        $templatePayload = \call_user_func($builder, $data, $options);

        // Merge base payload (username/icon/channel) with template content.
        $base = self::buildBasePayload($templatePayload['fallback'] ?? ($data['fallback_text'] ?? ''), $options);
        // Remove fallback so Slack doesn't display unintended text if blocks are present.
        unset($base['text']);

        // Combine.
        $payload = array_merge($base, $templatePayload);

        return self::postToWebhook($payload);
    }

    /**
     * Build base payload using general options and config defaults.
     */
    protected static function buildBasePayload(string $text, array $options): array
    {
        $payload = [
            'text' => $text,
        ];

        // Provide overridable defaults from config.
        $username = $options['username'] ?? config('egg.slack_username');
        if ($username) {
            $payload['username'] = $username;
        }
        $icon = $options['icon_emoji'] ?? config('egg.slack_icon');
        if ($icon) {
            $payload['icon_emoji'] = $icon;
        }
        $channel = $options['channel'] ?? config('egg.slack_channel');
        if ($channel) {
            $payload['channel'] = $channel;
        }

        // Allow direct overrides.
        foreach (['thread_ts','unfurl_links','unfurl_media'] as $key) {
            if (array_key_exists($key, $options)) {
                $payload[$key] = $options[$key];
            }
        }

        // Direct blocks / attachments overrides.
        if (isset($options['blocks'])) {
            $payload['blocks'] = $options['blocks'];
        }
        if (isset($options['attachments'])) {
            $payload['attachments'] = $options['attachments'];
        }

        return $payload;
    }

    /**
     * Actually POST the payload to Slack.
     */
    protected static function postToWebhook(array $payload): bool
    {
        $webhookUrl = config('egg.slack_webhook_url');
        if (!$webhookUrl) {
            return false;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $json,
                'timeout' => 5,
            ],
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($webhookUrl, false, $context);
        return $result !== false;
    }

    /**
     * Normalize exception-like data from a message or payload.
     * Extracts common fields for templates like 'exception_card'.
     */
    protected static function normalizeExceptionData($data): ?array
    {
        // If data is already an array, assume it's normalized.
        if (is_array($data)) {
            return $data;
        }

        // If data is a string, try to decode as JSON.
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // For Throwable objects, extract relevant data.
        if ($data instanceof Throwable) {
            return [
                'exception_class' => get_class($data),
                'message' => $data->getMessage(),
                'file' => $data->getFile(),
                'line' => $data->getLine(),
                'trace' => $data->getTraceAsString(),
                // Add any other normalization as needed.
            ];
        }

        // Unknown format, return null.
        return null;
    }

    /* ===================== TEMPLATE BUILDERS ===================== */

    protected static function buildSimpleTemplate(array $data): array
    {
        $text = $data['text'] ?? $data['fallback_text'] ?? 'Notification';
        return [
            'fallback' => $text,
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [ 'type' => 'mrkdwn', 'text' => $text ],
                ],
            ],
        ];
    }

    protected static function buildSuccessTemplate(array $data): array
    {
        $title = $data['title'] ?? 'Success';
        $details = $data['details'] ?? ($data['text'] ?? 'Operation completed');
        return [
            'fallback' => $title . ' - ' . $details,
            'attachments' => [
                [
                    'color' => 'good',
                    'title' => $title,
                    'text' => $details,
                ],
            ],
        ];
    }

    protected static function buildWarningTemplate(array $data): array
    {
        $title = $data['title'] ?? 'Warning';
        $details = $data['details'] ?? ($data['text'] ?? 'Attention required');
        return [
            'fallback' => $title . ' - ' . $details,
            'attachments' => [
                [
                    'color' => 'warning',
                    'title' => $title,
                    'text' => $details,
                ],
            ],
        ];
    }

    protected static function buildInfoTemplate(array $data): array
    {
        $title = $data['title'] ?? 'Info';
        $details = $data['details'] ?? ($data['text'] ?? 'Information');
        return [
            'fallback' => $title . ' - ' . $details,
            'attachments' => [
                [
                    'color' => '#439FE0',
                    'title' => $title,
                    'text' => $details,
                ],
            ],
        ];
    }

    protected static function buildExceptionTemplate(array $data): array
    {
        // Expect keys: exception_class, message, file, line, trace
        $class = $data['exception_class'] ?? 'UnknownException';
        $message = $data['message'] ?? 'No message';
        $file = $data['file'] ?? 'unknown.php';
        $line = $data['line'] ?? 0;
        $trace = $data['trace'] ?? '';

        // Trim trace to avoid hitting Slack limits (max ~40k chars for blocks).
        $maxTrace = 1200;
        if (strlen($trace) > $maxTrace) {
            $trace = substr($trace, 0, $maxTrace) . "\n... (truncated)";
        }

        $fallback = "$class: $message";

        $blocks = [
            [
                'type' => 'header',
                'text' => [ 'type' => 'plain_text', 'text' => 'Exception Caught' ],
            ],
            [
                'type' => 'section',
                'text' => [ 'type' => 'mrkdwn', 'text' => "*Class:* `$class`\n*Message:* $message" ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [ 'type' => 'mrkdwn', 'text' => "*File:* `$file:$line`" ],
                ],
            ],
        ];

        if ($trace) {
            $blocks[] = [
                'type' => 'section',
                'text' => [ 'type' => 'mrkdwn', 'text' => "*Trace:*\n```" . $trace . "```" ],
            ];
        }

        return [
            'fallback' => $fallback,
            'blocks' => $blocks,
            'attachments' => [
                [
                    'color' => 'danger',
                    'footer' => date('Y-m-d H:i:s'),
                ],
            ],
        ];
    }

    protected static function buildExceptionCardTemplate(array $data): array
    {
        $message = (string)($data['message'] ?? $data['text'] ?? $data['fallback_text'] ?? 'An error occurred');
        $level = (string)($data['level'] ?? 'ERROR');
        $session = (string)($data['session_uuid'] ?? $data['session'] ?? '');
        $type = (string)($data['exception_class'] ?? $data['type'] ?? 'Exception');
        $file = (string)($data['file'] ?? 'unknown.php');
        $line = (string)($data['line'] ?? '');
        $fileLine = $line !== '' ? "$file:$line" : $file;

        // Optional action buttons
        $openUrl = $data['open_url'] ?? $data['url'] ?? null;
        $openTempUrl = $data['open_tempurl'] ?? $data['tempurl'] ?? null;

        // Build blocks inside an attachment to get the left color bar.
        $blocks = [];

        // Message section
        $blocks[] = [
            'type' => 'section',
            'text' => [ 'type' => 'mrkdwn', 'text' => "*Message*\n" . $message ],
        ];

        // Level
        $blocks[] = [
            'type' => 'section',
            'text' => [ 'type' => 'mrkdwn', 'text' => "*Level*\n`$level`" ],
        ];

        // Session uuid (optional)
        if ($session !== '') {
            $blocks[] = [
                'type' => 'section',
                'text' => [ 'type' => 'mrkdwn', 'text' => "*Session uuid*\n`$session`" ],
            ];
        }

        // Type
        $blocks[] = [
            'type' => 'section',
            'text' => [ 'type' => 'mrkdwn', 'text' => "*Type*\n`$type`" ],
        ];

        // File
        $blocks[] = [
            'type' => 'section',
            'text' => [ 'type' => 'mrkdwn', 'text' => "*File*\n`$fileLine`" ],
        ];

        // Optional actions row
        $elements = [];
        if ($openUrl) {
            $elements[] = [
                'type' => 'button',
                'text' => [ 'type' => 'plain_text', 'text' => 'Open Url' ],
                'url' => $openUrl,
            ];
        }
        if ($openTempUrl) {
            $elements[] = [
                'type' => 'button',
                'text' => [ 'type' => 'plain_text', 'text' => 'Open Tempurl' ],
                'url' => $openTempUrl,
            ];
        }
        if (!empty($elements)) {
            $blocks[] = [
                'type' => 'actions',
                'elements' => $elements,
            ];
        }

        $fallback = ($type ? ($type . ': ') : '') . $message;

        return [
            'fallback' => $fallback,
            'attachments' => [
                [
                    'color' => 'danger',
                    'blocks' => $blocks,
                    'footer' => date('Y-m-d H:i:s'),
                ],
            ],
        ];
    }
}

