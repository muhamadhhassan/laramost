<?php

namespace LaraMost\Formatter;

use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;

class MattermostFormatter implements FormatterInterface
{
    public function format(array $record): array
    {
        $env = strtoupper(getenv('APP_ENV') ?? '');
        $appName = getenv('APP_NAME');
        $date = $record['datetime']->jsonSerialize();
        $message = $record['message'];
        $formattedDate = date('Y-m-d H:i:s e', strtotime($date));

        return [
            'attachments' => [
                [
                    'pretext' => "New log entry from **$appName**'s **$env** environment",
                    'title' => "# $message",
                    'text' => json_encode($record['context']),
                    'fields' => [
                        [
                            'title' => 'App',
                            'value' => $appName,
                            'short' => true,
                        ],
                        [
                            'title' => 'Date',
                            'value' => $formattedDate,
                            'short' => true,
                        ],
                        [
                            'title' => 'Environment',
                            'value' => $env,
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function formatBatch(array $records): array
    {
        $formatted = [];

        foreach ($records as $record) {
            $formatted[] = $this->format($record);
        }

        return $formatted;
    }

    public function getMarkdownEmoji(int|string $level): string
    {
        return match ($level) {
            Logger::DEBUG => 'mag',
            Logger::INFO => 'information_source',
            Logger::NOTICE => 'memo',
            Logger::WARNING => 'warning',
            Logger::ERROR => 'bug',
            Logger::CRITICAL => 'x',
            Logger::EMERGENCY => 'rotating_light',
        };
    }

    public function getHexColor(int|string $level): string
    {
        return match ($level) {
            Logger::DEBUG, Logger::INFO => '#91C4EB',
            Logger::NOTICE => '#99cc33',
            Logger::WARNING => '#ffcc00',
            Logger::ERROR, Logger::CRITICAL, Logger::EMERGENCY => '#cc3300',
        };
    }
}
