<?php

declare(strict_types=1);

namespace Crell\ArgParser;

class DefaultValue
{
    private static NoDefaultValue $noValue;

    private function __construct(public readonly mixed $value) {}

    public static function Value(mixed $value): self
    {
        return new self($value);
    }

    public static function NoValue(): NoDefaultValue
    {
        return self::$noValue ??= new NoDefaultValue();
    }
}
