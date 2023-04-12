<?php

declare(strict_types=1);

namespace Crell\ArgParser\Attributes;

use Attribute;
use Crell\AttributeUtils\ParseMethods;
use Crell\AttributeUtils\ParseProperties;

#[Attribute(Attribute::TARGET_CLASS)]
class ArgumentDefinition implements ParseProperties, ParseMethods
{
    /** @var array<string, Argument>  */
    public readonly array $arguments;

    /**
     * The names of any methods that should be invoked after the object is hydrated.
     *
     * @var array<string>
     */
    public readonly array $postLoad;


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
