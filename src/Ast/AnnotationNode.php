<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class AnnotationNode extends Node
{
    /** @param array<string,mixed> $data */
    public function __construct(
        SourceSpan $span,
        public string $name,
        public array $data,
    ) {
        parent::__construct('Annotation', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'name' => $this->name,
            'data' => $this->data,
        ];
    }
}
