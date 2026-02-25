<?php

declare(strict_types=1);

namespace SmartyAst\Lexer;

use SmartyAst\Diagnostics\Diagnostic;

final class LexResult
{
    /** @param list<TemplateToken> $tokens
     *  @param list<Diagnostic> $diagnostics
     */
    public function __construct(
        public readonly array $tokens,
        public readonly array $diagnostics,
    ) {
    }
}
