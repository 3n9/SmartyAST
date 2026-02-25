<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class CommentNode extends Node
{
    /** @param list<AnnotationNode> $annotations */
    public function __construct(
        SourceSpan $span,
        public string $text,
        public array $annotations = [],
    ) {
        parent::__construct('Comment', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'text' => $this->text,
            'annotations' => array_map(static fn (AnnotationNode $annotation) => $annotation->toArray(), $this->annotations),
        ];
    }
}
