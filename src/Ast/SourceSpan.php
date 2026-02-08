<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

final class SourceSpan
{
    public function __construct(
        public readonly Position $start,
        public readonly Position $end,
    ) {
    }
}
