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

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'ast' => $this->ast->toArray(),
            'diagnostics' => array_map(static fn (Diagnostic $d) => $d->toArray(), $this->diagnostics),
            'tokens' => $this->tokens,
        ];
    }

    public function toJson(int $flags = 0): string
    {
        return (string) json_encode($this->toArray(), $flags);
    }
}
