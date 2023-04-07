<?php

declare(strict_types=1);

namespace Crell\ArgParser\Args;

use Crell\ArgParser\Attributes\Argument;

class Typed
{
    public function __construct(
        #[Argument(shortName: 'i')]
        public readonly int $int = 0,
        #[Argument]
        public readonly string $string = 'hello',
        #[Argument]
        public readonly float $float = 3.14,
        #[Argument]
        public readonly array $array = [],
    ) {}
}
