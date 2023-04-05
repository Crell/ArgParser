<?php

declare(strict_types=1);

namespace Crell\ArgParser\Args;

use Crell\ArgParser\Attributes\Argument;

class Multivalue
{
    public function __construct(
        #[Argument]
        public array $file = [],
    ) {}
}
