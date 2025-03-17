<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Tests\Transport;

use AmqpReply\AmqpReply\Tests\Fixtures\DummyMessage;
use AmqpReply\AmqpReply\Transport\AmqpReplyRequester;
use AmqpReply\AmqpReply\Transport\AmqpReplyResponder;
use AmqpReply\AmqpReply\Transport\AmqpReplyTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpReplyTransportTest extends TestCase
{
    private AmqpReplyTransport $transport;
    private Connection $connection;
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->transport = new AmqpReplyTransport($this->connection, ['reply' => ['prefix' => 'reply_']], $this->serializer);
    }

    public function testSendCallsParentAndRequester(): void
    {
        $message = new Envelope(new \stdClass());

        $this->serializer
            ->method('encode')
            ->with($this->isInstanceOf(Envelope::class))
            ->willReturn(['body' => 'encoded_message']);

        $requester = $this->createMock(AmqpReplyRequester::class);
        $requester
            ->method('request')
            ->with($this->isInstanceOf(Envelope::class))
            ->willReturn($message);

        $transport = new AmqpReplyTransport($this->connection, ['reply' => ['prefix' => 'reply_']], $this->serializer);
        $reflection = new \ReflectionClass($transport);

        $property = $reflection->getProperty('requester');
        $property->setAccessible(true);
        $property->setValue($transport, $requester);

        $response = $transport->send($message);
        $this->assertInstanceOf(Envelope::class, $response);
    }

    public function testAckCallsResponder(): void
    {
        $message = new Envelope(new \stdClass(), [new ReceivedStamp('amqp'), new AmqpReceivedStamp($this->createAMQPEnvelope(), 'query_123')]);

        $responder = $this->createMock(AmqpReplyResponder::class);
        $responder->expects($this->once())->method('respond')->with($message);

        $transport = new AmqpReplyTransport($this->connection, ['reply' => ['prefix' => 'reply_']], $this->serializer);
        $reflection = new \ReflectionClass($transport);
        $property = $reflection->getProperty('responder');
        $property->setAccessible(true);
        $property->setValue($transport, $responder);

        $transport->ack($message);
    }

    public function testRejectCallsResponder(): void
    {
        $message = new Envelope(new \stdClass(), [new ReceivedStamp('amqp'), new AmqpReceivedStamp($this->createAMQPEnvelope(), 'query_123')]);

        $responder = $this->createMock(AmqpReplyResponder::class);
        $responder->expects($this->once())->method('respond')->with($message);

        $transport = new AmqpReplyTransport($this->connection, ['reply' => ['prefix' => 'reply_']], $this->serializer);
        $reflection = new \ReflectionClass($transport);
        $property = $reflection->getProperty('responder');
        $property->setAccessible(true);
        $property->setValue($transport, $responder);

        $transport->reject($message);
    }

    private function createAMQPEnvelope(): \AMQPEnvelope
    {
        $envelope = $this->createMock(\AMQPEnvelope::class);
        $envelope->method('getBody')->willReturn('{"message": "Hi"}');
        $envelope->method('getHeaders')->willReturn([
            'type' => DummyMessage::class,
        ]);

        return $envelope;
    }
}
