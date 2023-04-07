<?php

declare(strict_types=1);

namespace Crell\ArgParser;

use Crell\ArgParser\Attributes\Argument;

class MissingArgument extends \InvalidArgumentException
{
    public readonly Argument $argument;

    public static function create(Argument $argument): self
    {
        $new = new self();
        $new->argument = $argument;

        if ($argument->shortName) {
            $message = sprintf('Required argument %s (or short name %s) not found.', $argument->longName, $argument->shortName);
        } else {
            $message = sprintf('Required argument %s not found.', $argument->longName);
        }

        $new->message = $message;

        return $new;
    }
}
