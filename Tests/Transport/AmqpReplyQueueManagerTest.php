<?php

declare(strict_types=1);

namespace AmqpReply\AmqpReply\Tests\Transport;

use AmqpReply\AmqpReply\Transport\AmqpReplyQueueManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Exception\TransportException;

class AmqpReplyQueueManagerTest extends TestCase
{
    private AmqpReplyQueueManager $queueManager;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->queueManager = new AmqpReplyQueueManager($this->connection);
    }

    public function testCreateTemporaryQueue(): void
    {
        $queueName = 'reply_temp_queue';

        $queueMock = $this->createMock(\AMQPQueue::class);
        $exchangeMock = $this->createMock(\AMQPExchange::class);

        $exchangeMock->method('getName')->willReturn('amqp_exchange');

        $queueMock->expects($this->once())->method('setFlags')->with(AMQP_AUTODELETE);
        $queueMock->expects($this->once())->method('declare');
        $queueMock->expects($this->once())->method('bind')->with('amqp_exchange', $queueName);

        $this->connection->expects($this->once())->method('queue')->with($queueName)->willReturn($queueMock);
        $this->connection->expects($this->once())->method('exchange')->willReturn($exchangeMock);

        $result = $this->queueManager->createTemporaryQueue($queueName);
        $this->assertEquals($queueName, $result);
    }

    public function testWaitForResponseReturnsMessage(): void
    {
        $queueName = 'reply_queue';
        $queueMock = $this->createMock(\AMQPQueue::class);
        $messageMock = $this->createMock(\AMQPEnvelope::class);

        $messageMock->expects($this->once())->method('getBody')->willReturn('response_message');
        $queueMock->expects($this->once())->method('delete');

        $this->connection->expects($this->once())->method('queue')->with($queueName)->willReturn($queueMock);
        $this->connection->expects($this->any())->method('get')->with($queueName)->willReturn($messageMock);

        $result = $this->queueManager->waitForResponse($queueName, 2);
        $this->assertEquals('response_message', $result);
    }

    public function testWaitForResponseThrowsOnTimeout(): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Timeout waiting for response on queue: reply_queue');

        $queueName = 'reply_queue';
        $queueMock = $this->createMock(\AMQPQueue::class);
        $queueMock->expects($this->once())->method('delete');

        $this->connection->expects($this->once())->method('queue')->with($queueName)->willReturn($queueMock);
        $this->connection->expects($this->any())->method('get')->with($queueName)->willReturn(null);

        $this->queueManager->waitForResponse($queueName, 1);
    }

    public function testPublishOnQueue(): void
    {
        $encodedMessage = ['body' => 'test_message', 'headers' => ['key' => 'value']];
        $queueRequestName = 'reply_queue';

        $this->connection->expects($this->once())
            ->method('publish')
            ->with('test_message', ['key' => 'value'], 0, new AmqpStamp($queueRequestName));

        $this->queueManager->publishOnQueue($encodedMessage, $queueRequestName);
    }

    public function testPublishOnQueueThrowsException(): void
    {
        $this->expectException(TransportException::class);

        $encodedMessage = ['body' => 'test_message', 'headers' => ['key' => 'value']];
        $queueRequestName = 'reply_queue';

        $this->connection->expects($this->once())
            ->method('publish')
            ->willThrowException(new \AMQPException('Publish error'));

        $this->queueManager->publishOnQueue($encodedMessage, $queueRequestName);
    }
}
