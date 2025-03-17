<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Serialization;

use AmqpReply\AmqpReply\Stamp\FlexibleHandledStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\VarDumper\Cloner\Data;

class FlexiblePhpSerializer extends PhpSerializer
{
    public function decode(array $encodedEnvelope): Envelope
    {
        $envelope = parent::decode($encodedEnvelope);
        if ($responseStamp = $envelope->last(FlexibleHandledStamp::class)) {
            return $envelope
                ->withoutStampsOfType(FlexibleHandledStamp::class)
                ->with($responseStamp->toHandledStamp());
        }

        if ($errorStamp = $envelope->last(ErrorDetailsStamp::class)) {
            return $envelope->with($errorStamp);
        }

        throw new \LogicException('No FlexibleHandledStamp or ErrorDetailsStamp found on the Envelope.');
    }

    public function encode(Envelope $envelope): array
    {
        if ($handledStamp = $envelope->last(HandledStamp::class)) {
            $responseStamp = FlexibleHandledStamp::fromHandledStamp($handledStamp);
        } else if ($errorStamp = $envelope->last(ErrorDetailsStamp::class)) {
            $responseStamp = $this->sanitizeErrorDetailsStamp($errorStamp);
        } else {
            throw new \LogicException('No HandledStamp or ErrorDetailsStamp found on the Envelope.');
        }

        $envelope = $envelope->withoutStampsOfType(HandledStamp::class)->with($responseStamp);
        return parent::encode($envelope);
    }

    private function sanitizeErrorDetailsStamp(ErrorDetailsStamp $errorStamp): ErrorDetailsStamp
    {
        return new ErrorDetailsStamp(
            $errorStamp->getExceptionClass(),
            $errorStamp->getExceptionCode(),
            $errorStamp->getExceptionMessage(),
            $errorStamp->getFlattenException()->setDataRepresentation(new Data([]))
        );
    }

}
