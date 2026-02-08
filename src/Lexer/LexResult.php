<?php

declare(strict_types=1);

namespace Dev\Smarty\Lexer;

use Dev\Smarty\Diagnostics\Diagnostic;

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
