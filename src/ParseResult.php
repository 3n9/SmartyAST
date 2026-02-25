<?php

declare(strict_types=1);

namespace SmartyAst;

use SmartyAst\Ast\DocumentNode;
use SmartyAst\Diagnostics\Diagnostic;

final class ParseResult
{
    /** @param list<Diagnostic> $diagnostics
     *  @param list<array<string,mixed>> $tokens
     */
    public function __construct(
        public readonly DocumentNode $ast,
        public readonly array $diagnostics,
        public readonly array $tokens = [],
    ) {
    }
}
