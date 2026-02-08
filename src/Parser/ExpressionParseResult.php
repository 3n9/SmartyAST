<?php

declare(strict_types=1);

namespace Dev\Smarty\Parser;

use Dev\Smarty\Ast\ExpressionNode;
use Dev\Smarty\Diagnostics\Diagnostic;

final class ExpressionParseResult
{
    /** @param list<Diagnostic> $diagnostics */
    public function __construct(
        public readonly ExpressionNode $expression,
        public readonly array $diagnostics = [],
    ) {
    }
}
