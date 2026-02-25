<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

abstract class ExpressionNode extends Node
{
    /**
     * @return list<ExpressionNode>
     */
    public function childExpressions(): array
    {
        return [];
    }
}
