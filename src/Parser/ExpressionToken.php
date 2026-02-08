<?php

declare(strict_types=1);

namespace Dev\Smarty\Parser;

use Dev\Smarty\Ast\SourceSpan;

final class ExpressionToken
{
    public function __construct(
        public readonly string $type,
        public readonly string $value,
        public readonly SourceSpan $span,
    ) {
    }
}
