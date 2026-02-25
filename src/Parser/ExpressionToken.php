<?php

declare(strict_types=1);

namespace SmartyAst\Parser;

use SmartyAst\Ast\SourceSpan;

final class ExpressionToken
{
    public function __construct(
        public readonly string $type,
        public readonly string $value,
        public readonly SourceSpan $span,
    ) {
    }
}
