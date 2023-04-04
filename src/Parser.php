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
    ) {}

    /**
     *
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

        $args = $this->parseArgv($argv);

        $args = $this->translateShortNames($args, $def);

        $excessArgs = array_diff(array_keys($args), array_keys($def->arguments));
        if (count($excessArgs)) {
            throw new \InvalidArgumentException('Too many args');
        }

        $args += $def->getDefaults();

        $obj = $this->createObject($to, $args, []);

        return $obj;
    }

    private function translateShortNames(array $args, ArgumentDefinition $def): array
    {
        // @todo Why is this not getting picked up automatically?
        /** @var Argument $argument */
        foreach ($def->arguments as $argument) {
            if ($argument->shortName && array_key_exists($argument->shortName, $args)) {
                $args[$argument->phpName] = $args[$argument->shortName];
                unset($args[$argument->shortName]);
            }
        }
        return $args;
    }

    private function parseArgv(array $argv): array
    {
        $ret = [];

        $i = 0;
        while ($i < count($argv)) {
            if (str_starts_with($argv[$i], '--')) {
                // It's a long-form argument.
                $name = substr($argv[$i], 2);

                if (str_contains($argv[$i], '=')) {
                    [$name, $value] = \explode('=', $argv[$i]);
                    $name = substr($name, 2);
                } else {
                    $name = substr($name, 2);
                    $value = null;
                }

                // If the next arg exists and is not a new switch (denoted by -), assume it is a value for this argument.
                //$value = !str_starts_with($argv[$i + 1] ?? '', '-') ? $argv[$i + 1] : null;
                // $i++;

                $ret[$name] = $value;
            } elseif (str_starts_with($argv[$i], '-')) {
                $name = substr($argv[$i], 1);

                if (str_contains($argv[$i], '=')) {
                    [$name, $value] = \explode('=', $argv[$i]);
                    $name = substr($name, 1);
                } else {
                    $name = substr($name, 1);
                    $value = null;
                }
                $ret[$name] = $value;
            } else {
                $name = substr($name, 2);
                $ret[$name] = null;
            }
            $i++;
        }

        return $ret;
    }

    private function createObject(string $class, array $props, array $callbacks): object
    {
        // Make an empty instance of the target class.
        $rClass = new \ReflectionClass($class);
        $new = $rClass->newInstanceWithoutConstructor();

        $populator = function (array $props) {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        };

        $methodCaller = fn(string $fn) => $this->$fn();

        // Call the populator with the scope of the new object.
        $populator->call($new, $props);

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

}
