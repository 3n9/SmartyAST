<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

final class TextNode extends Node
{
    public function __construct(
        SourceSpan $span,
        public string $text,
    ) {
        parent::__construct('Text', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'text' => $this->text,
        ];
    }
}
