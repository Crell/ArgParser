<?php

declare(strict_types=1);

namespace Crell\ArgParser\Attributes;

use Attribute;
use Crell\ArgParser\NoDefaultValue;
use Crell\AttributeUtils\ParseProperties;
use function Crell\fp\afilter;
use function Crell\fp\amap;
use function Crell\fp\pipe;

#[Attribute(Attribute::TARGET_CLASS)]
class ArgumentDefinition implements ParseProperties
{
    /** @var array<string, Argument>  */
    public readonly array $arguments;

    public function getDefaults(): array
    {
        return pipe($this->arguments,
            afilter(fn(Argument $arg) => ! $arg->default instanceof NoDefaultValue),
            amap(fn(Argument $arg) => $arg->default->value),
        );
    }

    public function setProperties(array $properties): void
    {
        $this->arguments = $properties;
    }

    public function includePropertiesByDefault(): bool
    {
        return false;
    }

    public function propertyAttribute(): string
    {
        return Argument::class;
    }
}
