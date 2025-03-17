<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Tests\Transport;

use AmqpReply\AmqpReply\Stamp\ReplyQueueNameStamp;
use AmqpReply\AmqpReply\Transport\AmqpReplyQueueManager;
use AmqpReply\AmqpReply\Transport\AmqpReplyRequester;
use AmqpReply\AmqpReply\Transport\AmqpReplyResponder;
use AmqpReply\AmqpReply\Transport\AmqpReplyTransport;
use AmqpReply\AmqpReply\Transport\AmqpReplyTransportFactory;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpReplyTransportFactoryTest extends TestCase
{
    public function testItCreatesTheAmqpReplyTransport()
    {
        $factory = new AmqpReplyTransportFactory();
        $serializer = $this->createMock(SerializerInterface::class);

        $expectedTransport = new AmqpReplyTransport(
            Connection::fromDsn('amqp://localhost', ['host' => 'localhost']),
            ['host' => 'localhost', 'reply' => null],
            $serializer
        );

        $this->assertEquals(
            $expectedTransport,
            $factory->createTransport('amqp://localhost', ['host' => 'localhost', 'reply' => null], $serializer)
        );
    }

    public function testItCreatesTheAmqpTransport()
    {
        $factory = new AmqpReplyTransportFactory();
        $serializer = $this->createMock(SerializerInterface::class);

        $expectedTransport = new AmqpTransport(
            Connection::fromDsn('amqp://localhost', ['host' => 'localhost']),
            $serializer
        );

        $this->assertEquals(
            $expectedTransport,
            $factory->createTransport('amqp://localhost', ['host' => 'localhost'], $serializer)
        );
    }


}
