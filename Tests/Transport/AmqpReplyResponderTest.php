<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Tests\Transport;

use AmqpReply\AmqpReply\Stamp\ReplyQueueNameStamp;
use AmqpReply\AmqpReply\Transport\AmqpReplyQueueManager;
use AmqpReply\AmqpReply\Transport\AmqpReplyRequester;
use AmqpReply\AmqpReply\Transport\AmqpReplyResponder;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;


class AmqpReplyResponderTest extends TestCase
{

    public function testItReturnsRequestToQueue(): void
    {
        $connection = $this->createMock(Connection::class);
        $serializer = $this->createMock(SerializerInterface::class);
        $queueManager = $this->createMock(AmqpReplyQueueManager::class);

        $responder = new AmqpReplyResponder($connection, ['reply' => ['timeout' => 10]], $serializer, $queueManager);
        $message = new Envelope(new \stdClass(), [new ReplyQueueNameStamp('reply_queue')]);

        $serializer
            ->method('encode')
            ->with($this->callback(function (Envelope $envelope) {
                return !$envelope->last(ReplyQueueNameStamp::class);
            }))
            ->willReturn(['body' => 'encoded_message']);

        $queueManager->expects($this->once())
            ->method('publishOnQueue')
            ->with(['body' => 'encoded_message'], 'reply_queue');

        $responder->respond($message);
    }


    public function testItThrowsLogicExceptionIfReplyQueueNameStampNotFound(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No ReplyQueueNameStamp found');

        $connection = $this->createMock(Connection::class);
        $serializer = $this->createMock(SerializerInterface::class);
        $queueManager = $this->createMock(AmqpReplyQueueManager::class);

        $responder = new AmqpReplyResponder($connection, ['reply' => ['timeout' => 10]], $serializer, $queueManager);
        $message = new Envelope(new \stdClass());

        $responder->respond($message);
    }
}
