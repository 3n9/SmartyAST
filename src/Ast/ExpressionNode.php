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

    /**
     * @return list<Node>
     */
    public function children(): array
    {
        return $this->childExpressions();
    }

    /**
     * Collects all variable names (without $) referenced anywhere in this
     * expression tree, including nested property-fetch paths like "obj.prop".
     *
     * @return list<string>
     */
    public function collectVariableNames(): array
    {
        $paths = [];
        $this->doCollectVariableNames($this, $paths);
        return array_values(array_unique($paths));
    }

    /**
     * Counts the number of leaf operands in a binary-expression tree.
     * Parenthesised sub-expressions (UnaryExpressionNode) are unwrapped
     * transparently, so `($a && $b) || $c` counts as 3 operands.
     */
    public function countBinaryOperands(): int
    {
        return $this->doCountBinaryOperands($this);
    }

    /** @param list<string> &$paths */
    private function doCollectVariableNames(ExpressionNode $expr, array &$paths): void
    {
        if ($expr instanceof VariableExpressionNode) {
            $paths[] = $expr->name;
            return;
        }

        if ($expr instanceof PropertyFetchExpressionNode) {
            $path = $this->buildPropertyPath($expr);
            if ($path !== null) {
                $paths[] = $path;
            }
            // Still recurse so dynamically-computed parts are captured.
            $this->doCollectVariableNames($expr->target, $paths);
            return;
        }

        foreach ($expr->childExpressions() as $child) {
            $this->doCollectVariableNames($child, $paths);
        }
    }

    private function buildPropertyPath(PropertyFetchExpressionNode $expr): ?string
    {
        if ($expr->target instanceof VariableExpressionNode) {
            return $expr->target->name . '.' . $expr->property;
        }
        if ($expr->target instanceof PropertyFetchExpressionNode) {
            $root = $this->buildPropertyPath($expr->target);
            return $root !== null ? $root . '.' . $expr->property : null;
        }
        return null;
    }

    private function doCountBinaryOperands(ExpressionNode $expr): int
    {
        if ($expr instanceof BinaryExpressionNode) {
            return $this->doCountBinaryOperands($expr->left)
                 + $this->doCountBinaryOperands($expr->right);
        }
        // Unwrap grouping parentheses (UnaryExpressionNode wraps a sub-expression).
        if ($expr instanceof UnaryExpressionNode) {
            return $this->doCountBinaryOperands($expr->expression);
        }
        return 1;
    }
}
