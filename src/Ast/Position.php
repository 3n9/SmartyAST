<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class Position
{
    public function __construct(
        public readonly int $offset,
        public readonly int $line,
        public readonly int $column,
    ) {
    }
}
