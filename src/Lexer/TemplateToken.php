<?php

declare(strict_types=1);

namespace Dev\Smarty\Lexer;

use Dev\Smarty\Ast\SourceSpan;

final class TemplateToken
{
    public function __construct(
        public readonly string $type,
        public readonly string $raw,
        public readonly string $content,
        public readonly SourceSpan $span,
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'raw' => $this->raw,
            'content' => $this->content,
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
