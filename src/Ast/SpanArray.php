<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class SpanArray
{
    /** @return array<string,mixed> */
    public static function from(SourceSpan $span): array
    {
        return [
            'start' => [
                'offset' => $span->start->offset,
                'line' => $span->start->line,
                'column' => $span->start->column,
            ],
            'end' => [
                'offset' => $span->end->offset,
                'line' => $span->end->line,
                'column' => $span->end->column,
            ],
        ];
    }
}
