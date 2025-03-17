<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Tests\Serialization;

use AmqpReply\AmqpReply\Serialization\FlexiblePhpSerializer;
use AmqpReply\AmqpReply\Stamp\FlexibleHandledStamp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class FlexiblePhpSerializerTest extends TestCase
{
    private FlexiblePhpSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new FlexiblePhpSerializer();
    }

    public function testDecodeWithFlexibleHandledStamp(): void
    {
        $message = new \stdClass();
        $handledStamp = new FlexibleHandledStamp($message, 'handler_name', 'response');
        $envelope = new Envelope($message, [$handledStamp]);

        $encoded = (new PhpSerializer())->encode($envelope);
        $decodedEnvelope = $this->serializer->decode($encoded);

        $this->assertInstanceOf(Envelope::class, $decodedEnvelope);
        $this->assertInstanceOf(HandledStamp::class, $decodedEnvelope->last(HandledStamp::class));
    }

    public function testDecodeWithErrorDetailsStamp(): void
    {
        $message = new \stdClass();
        $errorStamp = new ErrorDetailsStamp(\RuntimeException::class, 123, 'Error message');
        $envelope = new Envelope($message, [$errorStamp]);

        $encoded = (new PhpSerializer())->encode($envelope);
        $decodedEnvelope = $this->serializer->decode($encoded);

        $this->assertInstanceOf(Envelope::class, $decodedEnvelope);
        $this->assertInstanceOf(ErrorDetailsStamp::class, $decodedEnvelope->last(ErrorDetailsStamp::class));
        $this->assertEquals('Error message', $decodedEnvelope->last(ErrorDetailsStamp::class)->getExceptionMessage());
    }

    public function testDecodeThrowsExceptionWhenNoStampIsPresent(): void
    {
        $message = new \stdClass();
        $envelope = new Envelope($message);

        $encoded = (new PhpSerializer())->encode($envelope);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No FlexibleHandledStamp or ErrorDetailsStamp found on the Envelope.');

        $this->serializer->decode($encoded);
    }

    public function testEncodeWithHandledStamp(): void
    {
        $message = new \stdClass();
        $handledStamp = new HandledStamp($message, 'handler_name');
        $envelope = new Envelope($message, [$handledStamp]);

        $encoded = $this->serializer->encode($envelope);
        $decodedEnvelope = (new PhpSerializer())->decode($encoded);

        $this->assertInstanceOf(FlexibleHandledStamp::class, $decodedEnvelope->last(FlexibleHandledStamp::class));
    }

    public function testEncodeWithErrorDetailsStamp(): void
    {
        $message = new \stdClass();
        $errorStamp = new ErrorDetailsStamp(\RuntimeException::class, 123, 'Error message', FlattenException::createFromThrowable(new \RuntimeException('Error message', 123)));
        $envelope = new Envelope($message, [$errorStamp]);

        $encoded = $this->serializer->encode($envelope);
        $decodedEnvelope = (new PhpSerializer())->decode($encoded);

        $this->assertInstanceOf(ErrorDetailsStamp::class, $decodedEnvelope->last(ErrorDetailsStamp::class));
        $this->assertEquals('Error message', $decodedEnvelope->last(ErrorDetailsStamp::class)->getExceptionMessage());
    }

    public function testEncodeThrowsExceptionWhenNoStampIsPresent(): void
    {
        $message = new \stdClass();
        $envelope = new Envelope($message);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No HandledStamp or ErrorDetailsStamp found on the Envelope.');

        $this->serializer->encode($envelope);
    }
}
