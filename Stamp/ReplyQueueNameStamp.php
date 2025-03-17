<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class ReplyQueueNameStamp implements StampInterface
{
    public function __construct(
        private string $name
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
