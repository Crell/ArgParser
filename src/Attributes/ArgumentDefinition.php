<?php

declare(strict_types=1);

namespace Crell\ArgParser\Attributes;

use Attribute;
use Crell\ArgParser\NoDefaultValue;
use Crell\AttributeUtils\ParseMethods;
use Crell\AttributeUtils\ParseProperties;
use function Crell\fp\afilter;
use function Crell\fp\amap;
use function Crell\fp\pipe;

#[Attribute(Attribute::TARGET_CLASS)]
class ArgumentDefinition implements ParseProperties, ParseMethods
{
    /** @var array<string, Argument>  */
    public readonly array $arguments;

    /**
     * The names of any methods that should be invoked after the object is hydrated.
     *
     * @var array array<string>
     */
    public readonly array $postLoad;

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

    public function setMethods(array $methods): void
    {
        $this->postLoad = array_keys($methods);
    }

    public function includeMethodsByDefault(): bool
    {
        return false;
    }

    public function methodAttribute(): string
    {
        return PostLoad::class;
    }
}
