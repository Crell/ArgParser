<?php

declare(strict_types=1);

namespace Crell\ArgParser;

use Crell\ArgParser\Args\Basic;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @test
     * @dataProvider exampleSuccessArgs()
     */
    public function success(int $argc, array $argv, string $class, object $expected): void
    {
        $parser = new Parser();
        $result = $parser->parse($argv, to: $class);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider exampleErrorArgs()
     */
    public function errors(int $argc, array $argv, string $class, string $expectedException): void
    {
        $this->expectException($expectedException);
        $parser = new Parser();
        $parser->parse($argv, to: $class);
    }

    public function exampleSuccessArgs(): iterable
    {
        yield [
            'argc' => 1,
            'argv' => ['script.php', '--a=A'],
            'class' => Basic::class,
            'expected' => new Basic('A'),
        ];
    }
    public function exampleErrorArgs(): iterable
    {
        yield [
            'argc' => 1,
            'argv' => ['script.php', '--a=A', '--C'],
            'class' => Basic::class,
            'expectedException' => \InvalidArgumentException::class,
        ];
    }
}
