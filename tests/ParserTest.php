<?php

declare(strict_types=1);

namespace Crell\ArgParser;

use Crell\ArgParser\Args\ABoolean;
use Crell\ArgParser\Args\Basic;
use Crell\ArgParser\Args\Callback;
use Crell\ArgParser\Args\Missing;
use Crell\ArgParser\Args\Multivalue;
use Crell\ArgParser\Args\Typed;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @test
     * @dataProvider exampleSuccessArgs()
     */
    public function success(array $argv, string $class, object $expected): void
    {
        $parser = new Parser();
        $result = $parser->parse($argv, to: $class);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider exampleErrorArgs()
     */
    public function errors(array $argv, string $class, string $expectedException): void
    {
        $this->expectException($expectedException);
        $parser = new Parser();
        $parser->parse($argv, to: $class);
    }

    public function exampleSuccessArgs(): iterable
    {
        yield 'basic long-name parameter' => [
            'argv' => ['script.php', '--about=A'],
            'class' => Basic::class,
            'expected' => new Basic('A'),
        ];
        yield 'basic short-name parameter' => [
            'argv' => ['script.php', '-a=A'],
            'class' => Basic::class,
            'expected' => new Basic('A'),
        ];
        yield 'multi-value long parameter' => [
            'argv' => ['script.php', '--file=A', '--file=B'],
            'class' => Multivalue::class,
            'expected' => new Multivalue(['A', 'B']),
        ];
        yield 'typed arguments with only one array value' => [
            'argv' => ['script.php', '--int=5', '--string=world', '--float=2.7', '--array=val', '--doit'],
            'class' => Typed::class,
            'expected' => new Typed(5, 'world', 2.7, ['val'], true),
        ];
        yield 'typed arguments with multiple array values' => [
            'argv' => ['script.php', '--int=5', '--string=world', '--float=2.7', '--array=beep', '--array=boop'],
            'class' => Typed::class,
            'expected' => new Typed(5, 'world', 2.7, ['beep', 'boop']),
        ];
        yield 'typed arguments with safe into-to-float handling' => [
            'argv' => ['script.php', '--int=5', '--string=world', '--float=2', '--array=val'],
            'class' => Typed::class,
            'expected' => new Typed(5, 'world', 2.0, ['val']),
        ];
        yield 'typed arguments with safe float string handling' => [
            'argv' => ['script.php', '--int=5', '--string=3.14', '--float=2', '--array=val'],
            'class' => Typed::class,
            'expected' => new Typed(5, '3.14', 2.0, ['val']),
        ];
        yield 'default values are used' => [
            'argv' => ['script.php'],
            'class' => Typed::class,
            'expected' => new Typed(),
        ];
        yield 'callbacks are called' => [
            'argv' => ['script.php', '--a=3', '--b=4'],
            'class' => Callback::class,
            'expected' => new Callback(3, 4),
        ];
        yield 'extraneous values are ignored' => [
            'argv' => ['script.php', 'someCommand', '--file=A', '--file=B'],
            'class' => Multivalue::class,
            'expected' => new Multivalue(['A', 'B']),
        ];

        // The different kinds of boolean.

        foreach (['1', 'true', 'yes', 'on'] as $val) {
            yield "$val is true (long)" => [
                'argv' => ['script.php', '--flag=' . $val],
                'class' => ABoolean::class,
                'expected' => new ABoolean(true),
            ];

            yield "$val is true (short)" => [
                'argv' => ['script.php', '-f=' . $val],
                'class' => ABoolean::class,
                'expected' => new ABoolean(true),
            ];
        }
        foreach (['0', 'false', 'no', 'off', 'nope', 'narf'] as $val) {
            yield "$val is false (long)" => [
                'argv' => ['script.php', '--flag=' . $val],
                'class' => ABoolean::class,
                'expected' => new ABoolean(false),
            ];

            yield "$val is false (short)" => [
                'argv' => ['script.php', '-f=' . $val],
                'class' => ABoolean::class,
                'expected' => new ABoolean(false),
            ];
        }

        yield 'just defined is true (long)' => [
            'argv' => ['script.php', '--flag'],
            'class' => ABoolean::class,
            'expected' => new ABoolean(true),
        ];

        yield 'just defined is true (short)' => [
            'argv' => ['script.php', '-f'],
            'class' => ABoolean::class,
            'expected' => new ABoolean(true),
        ];

    }

    public function exampleErrorArgs(): iterable
    {
        yield [
            'argv' => ['script.php', '--about=A', '--C'],
            'class' => Basic::class,
            'expectedException' => TooManyArguments::class,
        ];

        yield 'float into int' => [
            'argv' => ['script.php', '--int=5.5', '--float=2.7'],
            'class' => Typed::class,
            'expectedException' => TypeMismatch::class,
        ];

        yield 'array into int' => [
            'argv' => ['script.php', '--int=5', '--int=7'],
            'class' => Typed::class,
            'expectedException' => TypeMismatch::class,
        ];

        yield 'duplicate values in long vs short name' => [
            'argv' => ['script.php', '-i=5', '--int=7'],
            'class' => Typed::class,
            'expectedException' => LongAndShortArgumentUsed::class,
        ];

        yield 'missing required argument' => [
            'argv' => ['script.php'],
            'class' => Missing::class,
            'expectedException' => MissingArgument::class,
        ];
    }
}
