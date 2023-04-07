<?php

declare(strict_types=1);

namespace Crell\ArgParser\Attributes;

use Attribute;
use Crell\ArgParser\DefaultValue;
use Crell\AttributeUtils\FromReflectionProperty;
use Crell\AttributeUtils\TypeDef;
use function Crell\fp\indexBy;
use function Crell\fp\method;
use function Crell\fp\pipe;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Argument implements FromReflectionProperty
{
    /**
     * The native PHP type, as the reflection system defines it.
     *
     * @var string|class-string
     */
    public readonly string $phpType;

    /**
     * The property name, not to be confused with the desired serialized $name.
     */
    public readonly string $phpName;

    /**
     * The argument name.
     *
     * This form will require a -- prefix.
     */
    public readonly string $longName;

    public readonly DefaultValue $default;

    public function __construct(
        public readonly ?string $shortName = null,
        ?string $longName = null,
    ) {
        if ($longName) {
            $this->longName = $longName;
        }
    }

    public function fromReflection(\ReflectionProperty $subject): void
    {
        $this->longName ??= $subject->name;
        $this->phpName = $subject->name;
        $typeDef = new TypeDef($subject->getType());
        if (!$typeDef->isSimple()) {
            throw new \InvalidArgumentException('Non-simple types not supported');
        }
        $this->phpType ??= $typeDef->getSimpleType();
        $this->default = $this->getDefaultValueFromConstructor($subject);
    }

    protected function getDefaultValueFromConstructor(\ReflectionProperty $subject): DefaultValue
    {
        /** @var array<string, \ReflectionParameter> $params */
        $params = pipe($subject->getDeclaringClass()->getConstructor()?->getParameters() ?? [],
            indexBy(method('getName')),
        );

        $param = $params[$subject->getName()] ?? null;

        return $param?->isDefaultValueAvailable()
            ? DefaultValue::Value($param->getDefaultValue())
            : DefaultValue::NoValue();
    }

}
