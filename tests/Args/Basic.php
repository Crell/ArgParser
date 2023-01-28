<?php

declare(strict_types=1);

namespace Crell\ArgParser\Args;

use Crell\ArgParser\Attributes\Argument;

class Basic
{
    public function __construct(
        #[Argument]
        public readonly string $a,
        #[Argument]
        public readonly string $b = 'B',
    ) {}
}
