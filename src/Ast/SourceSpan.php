<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class SourceSpan
{
    public function __construct(
        public readonly Position $start,
        public readonly Position $end,
    ) {
    }
}
