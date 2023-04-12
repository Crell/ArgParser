<?php

declare(strict_types=1);

namespace Crell\ArgParser\Args;


use Crell\ArgParser\Attributes\Argument;

class ABoolean
{
    public function __construct(
        #[Argument(shortName: 'f')]
        public readonly bool $flag = false,
    ) {}
}
