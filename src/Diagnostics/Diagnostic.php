<?php

declare(strict_types=1);

namespace SmartyAst\Diagnostics;

use SmartyAst\Ast\SourceSpan;

final class Diagnostic
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
        public readonly Severity $severity,
        public readonly SourceSpan $span,
        public readonly bool $recoverable = true,
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'severity' => $this->severity->value,
            'recoverable' => $this->recoverable,
            'span' => [
                'start' => [
                    'offset' => $this->span->start->offset,
                    'line' => $this->span->start->line,
                    'column' => $this->span->start->column,
                ],
                'end' => [
                    'offset' => $this->span->end->offset,
                    'line' => $this->span->end->line,
                    'column' => $this->span->end->column,
                ],
            ],
        ];
    }
}
