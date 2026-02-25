<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

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
