<?php

declare(strict_types=1);

namespace SmartyAst\Parser;

use SmartyAst\Ast\ExpressionNode;
use SmartyAst\Diagnostics\Diagnostic;

final class ExpressionParseResult
{
    /** @param list<Diagnostic> $diagnostics */
    public function __construct(
        public readonly ExpressionNode $expression,
        public readonly array $diagnostics = [],
    ) {
    }
}
