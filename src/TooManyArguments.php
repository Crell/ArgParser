<?php

declare(strict_types=1);

namespace Crell\ArgParser;

class TooManyArguments extends \InvalidArgumentException
{
    /** @var string[] */
    public readonly array $args;

    public static function create(array $args): self
    {
        $new = new self();
        $new->args = $args;

        $message = sprintf('Undefined arguments: %s', implode(', ', $args));

        $new->message = $message;

        return $new;
    }
}
