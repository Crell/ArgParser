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
     * @param array $argv
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
            throw new \InvalidArgumentException('Too many args');
        }

        $args += $def->getDefaults();

        return $this->createObject($to, $args, $def, []);
    }

    public function translateShortNames(array $args, ArgumentDefinition $def): array
    {
        // @todo Why is this not getting picked up automatically?
        /** @var Argument $argument */
        foreach ($def->arguments as $argument) {
            if ($argument->shortName && array_key_exists($argument->shortName, $args)) {
                $args[$argument->longName] = $args[$argument->shortName];
                unset($args[$argument->shortName]);
            }
        }
        return $args;
    }

    private function createObject(string $class, array $args, ArgumentDefinition $def, array $callbacks): object
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
                $populator->call($new, $argument->phpName, $val);
            }
        }

        $methodCaller = fn(string $fn) => $this->$fn();

        // Invoke any post-load callbacks, even if they're private.
        $invoker = $methodCaller->bindTo($new, $new);
        // bindTo() technically could return null on error, but there's no
        // indication of when that would happen. So this is really just to
        // keep static analyzers happy.
        if ($invoker) {
            foreach ($callbacks as $fn) {
                $invoker($fn);
            }
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
     * @param string $val
     *   The value to normalize.
     * @return int|float|string|bool
     *   The passed value, but now with the correct type.
     */
    private function typeNormalize(string|array $val, Argument $argument): int|float|string|bool|array
    {
        return match ($argument->phpType) {
            'string' => $val,
            'float' => is_numeric($val)
                ? (float) $val
                : throw TypeMismatch::create($argument->longName, $argument->phpType, get_debug_type($val)),
            'int' => (is_numeric($val) && floor((float) $val) === (float) $val)
                ? (int) $val
                : throw TypeMismatch::create($argument->longName, $argument->phpType, get_debug_type($val)),
            'bool' => in_array(strtolower($val), [1, '1', 'true', 'yes', 'on'], false),
            'array' => is_array($val)
                ? $val
                : [$val],
            default => throw TypeMismatch::create($argument->longName, $argument->phpType, get_debug_type($val)),
        };
    }
}
