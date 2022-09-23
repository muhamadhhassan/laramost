<?php

namespace LaraMost\Handler;

use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use LaraMost\Formatter\MattermostFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Monolog\LogRecord;

class MattermostWebhookHandler extends AbstractProcessingHandler
{
    protected Client $client;

    public function __construct(private readonly string $hook, int|string $level = Logger::DEBUG, bool $bubble = true) {
        parent::__construct($level, $bubble);

        $this->client = new Client([
            RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath(),
        ]);
    }

    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        if ($formatter instanceof MattermostFormatter) {
            $formatter = new MattermostFormatter($this->level);
            return parent::setFormatter($formatter);
        }

        throw new \InvalidArgumentException('MattermostHandler is only compatible with MattermostFormatter');
    }

    protected function write(array|LogRecord $record): void
    {
        $logEntry = $record['formatted'];
        $logEntry['icon_emoji'] = $this->getFormatter()->getMarkdownEmoji($this->level);
        $logEntry['attachments'][0]['color'] = $this->getFormatter()->getHexColor($this->level);
        $logEntry['attachments'][0]['fields'][] = [
            'title' => 'Level',
            'value' => Logger::getLevelName($this->level),
            'short' => true
        ];

        $this->client->request('POST', $this->hook, [
            'form_params' => [
                'payload' => json_encode($logEntry)
            ],
        ]);
    }
}
