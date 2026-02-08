<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

final class TagArgumentNode extends Node
{
    public function __construct(
        SourceSpan $span,
        public ?string $name,
        public ExpressionNode $value,
    ) {
        parent::__construct('TagArgument', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'name' => $this->name,
            'value' => $this->value->toArray(),
        ];
    }
}
