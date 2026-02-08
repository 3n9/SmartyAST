<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

final class TagNode extends Node
{
    /** @param list<TagArgumentNode> $arguments */
    public function __construct(
        SourceSpan $span,
        public string $name,
        public array $arguments,
        public bool $isShorthand,
        public string $raw,
    ) {
        parent::__construct('Tag', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'name' => $this->name,
            'isShorthand' => $this->isShorthand,
            'raw' => $this->raw,
            'arguments' => array_map(static fn (TagArgumentNode $argument) => $argument->toArray(), $this->arguments),
        ];
    }
}
