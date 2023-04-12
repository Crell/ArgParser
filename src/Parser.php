<?php

declare(strict_types=1);

namespace Crell\ArgParser;

use Crell\ArgParser\Attributes\Argument;
use Crell\ArgParser\Attributes\ArgumentDefinition;
use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;

class Parser
{
    public function __construct(
        private readonly ClassAnalyzer $analyzer = new Analyzer(),
        private readonly ArgNormalizer $argNormalizer = new ArgNormalizer(),
    ) {}

    /**
     * Translates an $argv array to a defined class.
     *
     * @todo PHPStan isn't recognizing the template here, even though
     * it's the exact same syntax as used in AttributeUtils. I don't understand.
     *
     * @template T of object
     * @param string[] $argv
     *   The array of CLI arguments, as PHP defines it in $argv.
     * @param class-string<T> $to
     *   The fully qualified class name of the argument class.
     * @return T
     *   The objectified argument object.
     */
    public function parse(array $argv, string $to): object
    {
        $def = $this->analyzer->analyze($to, ArgumentDefinition::class);

        $scriptName = array_shift($argv);

        $args = $this->argNormalizer->parseArgv($argv);

        $args = $this->translateShortNames($args, $def);

        $excessArgs = array_diff(array_keys($args), array_keys($def->arguments));
        if (count($excessArgs)) {
            throw TooManyArguments::create($excessArgs);
        }

        return $this->createObject($to, $args, $def);
    }

    /**
     * @param array<string, string|array<string>|true> $args
     * @param ArgumentDefinition $def
     * @return array<string, string|array<string>|true>
     */
    public function translateShortNames(array $args, ArgumentDefinition $def): array
    {
        // @todo Why is this not getting picked up automatically?
        /** @var Argument $argument */
        foreach ($def->arguments as $argument) {
            if ($argument->shortName && array_key_exists($argument->shortName, $args)) {
                if (array_key_exists($argument->longName, $args)) {
                    throw LongAndShortArgumentUsed::create($argument);
                }
                $args[$argument->longName] = $args[$argument->shortName];
                unset($args[$argument->shortName]);
            }
        }
        return $args;
    }

    /**
     * @param string $class
     * @param array<string, string|array<string>|true> $args
     * @param ArgumentDefinition $def
     * @return object
     * @throws \ReflectionException
     */
    private function createObject(string $class, array $args, ArgumentDefinition $def): object
    {
        // Make an empty instance of the target class.
        $rClass = new \ReflectionClass($class);
        $new = $rClass->newInstanceWithoutConstructor();

        $populator = fn(string $prop, mixed $val) => $this->$prop = $val;

        /** @var Argument $argument */
        foreach ($def->arguments as $argument) {
            if (array_key_exists(key: $argument->longName, array: $args)) {
                // @Todo Figure out array count mismatches.
                $val = $this->typeNormalize($args[$argument->longName], $argument);
            } else {
                $val = $argument->default->value ?? throw MissingArgument::create($argument);
            }
            $populator->call($new, $argument->phpName, $val);
        }

        // Invoke any post-load callbacks, even if they're private.
        $methodCaller = fn(string $fn) => $this->$fn();
        $invoker = $methodCaller->bindTo($new, $new);
        foreach ($def->postLoad as $fn) {
            $invoker($fn);
        }

        return $new;
    }

    /**
     * Normalizes a scalar value to its most-restrictive type.
     *
     * CLI values are always imported as strings, but if we want to
     * push them into well-typed fields we need to cast them
     * appropriately.
     *
     * @param string|array<string>|bool $val
     *   The value to normalize.
     * @param Argument $argument
     *   The argument definition attribute.
     * @return int|float|string|bool|array<string|int>
     *   The passed value, but now with the correct type.
     */
    private function typeNormalize(string|array|bool $val, Argument $argument): int|float|string|bool|array
    {
        return match ($argument->phpType) {
            'string' => $val,
            'float' => is_numeric($val)
                ? (float) $val
                : throw TypeMismatch::create($argument->longName, $argument->phpType, get_debug_type($val)),
            'int' => (is_numeric($val) && floor((float) $val) === (float) $val)
                ? (int) $val
                : throw TypeMismatch::create($argument->longName, $argument->phpType, get_debug_type($val)),
            'array' => is_array($val)
                ? $val
                : [$val],
            'bool' => match (get_debug_type($val)) {
                    'string' => in_array(strtolower($val), ['1', 'true', 'yes', 'on'], false),
                    'int' => (bool) $val,
                    'bool' => $val,
                },
            default => throw TypeMismatch::create($argument->longName, $argument->phpType, get_debug_type($val)),
        };
    }
}
