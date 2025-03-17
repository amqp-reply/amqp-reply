<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Tests\Transport;

use AmqpReply\AmqpReply\Stamp\ReplyQueueNameStamp;
use AmqpReply\AmqpReply\Transport\AmqpReplyQueueManager;
use AmqpReply\AmqpReply\Transport\AmqpReplyRequester;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpReplyRequesterTest extends TestCase
{
    public function testItReturnsHandledEnvelope(): void
    {
        $connection = $this->createMock(Connection::class);
        $serializer = $this->createMock(SerializerInterface::class);
        $queueManager = $this->createMock(AmqpReplyQueueManager::class);

        $requester = new AmqpReplyRequester($connection, ['reply' => ['timeout' => 10]], $serializer, $queueManager);
        $message = new Envelope(new \stdClass(), [new ReplyQueueNameStamp('reply_queue')]);

        $serializer->method('decode')->with(['body' => 'message'])->willReturn($message->withoutStampsOfType(ReplyQueueNameStamp::class));
        $queueManager->method('createTemporaryQueue')->with('reply_queue')->willReturn('temp_queue');
        $queueManager->method('waitForResponse')->with('temp_queue', 10)->willReturn('message');

        $response = $requester->request($message);
        $this->assertInstanceOf(Envelope::class, $response);
    }

    public function testItThrowsALogicExceptionIfNotExists(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No ReplyQueueNameStamp found');
        $connection = $this->createMock(Connection::class);
        $serializer = $this->createMock(SerializerInterface::class);
        $queueManager = $this->createMock(AmqpReplyQueueManager::class);

        $requester = new AmqpReplyRequester($connection, ['reply' => ['timeout' => 10]], $serializer, $queueManager);
        $message = new Envelope(new \stdClass());

        $response = $requester->request($message);
    }
}
