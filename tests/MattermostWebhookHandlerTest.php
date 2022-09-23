<?php

use LaraMost\Formatter\MattermostFormatter;
use LaraMost\Handler\MattermostWebhookHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class MattermostWebhookHandlerTest extends TestCase
{
    private MattermostFormatter $formatter;
    private int $level;

    public function setUp(): void
    {
        parent::setUp();
        $this->formatter = new MattermostFormatter();
        $this->level = Logger::DEBUG;
    }

    /**
     * @test
     */
    public function it_can_send_request_correctly()
    {
        $record = [
            'formatted' => 'Hello World',
            'message' => 'Hello World',
            'level' => \Monolog\Logger::DEBUG,
            'extra' => '',
            'datetime' => new Monolog\DateTimeImmutable(false),
            'context' => [],
        ];

        $client = $this->getMockBuilder(\GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://your-mattermost.com',
                [
                    'form_params' => [
                        'payload' => json_encode($this->getFormattedRecord($record))
                    ]
                ]
            );

        $mattermostHandler = new MattermostWebhookHandler(
            'https://your-mattermost.com',
            $this->level,
            true
        );

        $reflection = new ReflectionClass($mattermostHandler);
        $handlerClient = $reflection->getProperty('client');
        $handlerClient->setValue($mattermostHandler, $client);
        $mattermostHandler->setFormatter($this->formatter);

        $mattermostHandler->handle($record);
    }

    private function getFormattedRecord(array $record): array
    {
        $formatted = $this->formatter->format($record);
        $formatted['icon_emoji'] = $this->formatter->getMarkdownEmoji($this->level);
        $formatted['attachments'][0]['color'] = $this->formatter->getHexColor($this->level);
        $formatted['attachments'][0]['fields'][] = [
            'title' => 'Level',
            'value' => Logger::getLevelName($this->level),
            'short' => true
        ];

        return $formatted;
    }
}
