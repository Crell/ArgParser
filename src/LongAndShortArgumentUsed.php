<?php

declare(strict_types=1);

namespace Crell\ArgParser;

use Crell\ArgParser\Attributes\Argument;

class LongAndShortArgumentUsed extends \InvalidArgumentException
{
    public readonly Argument $argument;

    public static function create(Argument $argument): self
    {
        $new = new self();
        $new->argument = $argument;

        $message = sprintf('You may not use both the long name (%s) and short name (%s) of an argument at the same time.', $argument->longName, $argument->shortName);
        $new->message = $message;

        return $new;
    }
}
