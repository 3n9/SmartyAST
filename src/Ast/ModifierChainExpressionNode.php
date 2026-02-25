<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class ModifierChainExpressionNode extends ExpressionNode
{
    /** @param list<ModifierNode> $modifiers */
    public function __construct(
        SourceSpan $span,
        public ExpressionNode $base,
        public array $modifiers,
    ) {
        parent::__construct('ModifierChainExpression', $span);
    }

    public function childExpressions(): array
    {
        $modifierArgs = array_merge(...array_map(static fn (ModifierNode $m) => $m->arguments, $this->modifiers));
        return [$this->base, ...$modifierArgs];
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'base' => $this->base->toArray(),
            'modifiers' => array_map(static fn (ModifierNode $modifier) => $modifier->toArray(), $this->modifiers),
        ];
    }
}
