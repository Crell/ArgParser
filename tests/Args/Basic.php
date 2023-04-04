<?php

declare(strict_types=1);

namespace Crell\ArgParser\Args;

use Crell\ArgParser\Attributes\Argument;

class Basic
{
    public function __construct(
        #[Argument(shortName: 'a')]
        public readonly string $about,
        #[Argument]
        public readonly string $b = 'B',
    ) {}
}
