<?php

declare(strict_types=1);

namespace Crell\ArgParser\Args;

use Crell\ArgParser\Attributes\Argument;
use Crell\ArgParser\Attributes\PostLoad;

class Callback
{
    public readonly int $sum;

    public function __construct(
        #[Argument]
        public readonly int $a,
        #[Argument]
        public readonly int $b,
    ) {
        $this->calculate();
    }

    #[PostLoad]
    private function calculate(): void
    {
        $this->sum = $this->a + $this->b;
    }
}
