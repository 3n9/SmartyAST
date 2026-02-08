<?php

declare(strict_types=1);

namespace Dev\Smarty\Parser;

use Dev\Smarty\Ast\ArrayAccessExpressionNode;
use Dev\Smarty\Ast\ArrayExpressionNode;
use Dev\Smarty\Ast\BinaryExpressionNode;
use Dev\Smarty\Ast\CallExpressionNode;
use Dev\Smarty\Ast\ErrorExpressionNode;
use Dev\Smarty\Ast\ExpressionNode;
use Dev\Smarty\Ast\IdentifierExpressionNode;
use Dev\Smarty\Ast\LiteralExpressionNode;
use Dev\Smarty\Ast\ModifierChainExpressionNode;
use Dev\Smarty\Ast\ModifierNode;
use Dev\Smarty\Ast\PropertyFetchExpressionNode;
use Dev\Smarty\Ast\SourceSpan;
use Dev\Smarty\Ast\TernaryExpressionNode;
use Dev\Smarty\Ast\UnaryExpressionNode;
use Dev\Smarty\Ast\VariableExpressionNode;
use Dev\Smarty\Diagnostics\Diagnostic;
use Dev\Smarty\Diagnostics\Severity;

final class ExpressionParser
{
    /** @var list<ExpressionToken> */
    private array $tokens = [];
    private int $index = 0;

    /** @var list<Diagnostic> */
    private array $diagnostics = [];

    public function __construct(
        private readonly ExpressionLexer $lexer = new ExpressionLexer(),
    ) {
    }

    public function parse(string $source, SourceSpan $containerSpan): ExpressionParseResult
    {
        $this->tokens = $this->lexer->tokenize($source, $containerSpan->start->offset, $containerSpan->start->line, $containerSpan->start->column);
        $this->index = 0;
        $this->diagnostics = [];

        $expression = $this->parseExpression(0);
        if ($this->current()->type !== 'eof') {
            $this->diagnostics[] = new Diagnostic('EXPR001', 'Unexpected token in expression.', Severity::Error, $this->current()->span, true);
        }

        return new ExpressionParseResult($expression, $this->diagnostics);
    }

    /**
     * @return array{0:list<array{name:?string,value:ExpressionNode,span:SourceSpan}>,1:list<Diagnostic>}
     */
    public function parseArguments(string $source, SourceSpan $containerSpan): array
    {
        $tokens = $this->lexer->tokenize($source, $containerSpan->start->offset, $containerSpan->start->line, $containerSpan->start->column);
        $index = 0;
        $args = [];
        $diagnostics = [];

        while (($tokens[$index] ?? null) !== null && $tokens[$index]->type !== 'eof') {
            while (($tokens[$index] ?? null) !== null && $tokens[$index]->type === 'punct' && $tokens[$index]->value === ',') {
                $index++;
            }
            if (($tokens[$index] ?? null) === null || $tokens[$index]->type === 'eof') {
                break;
            }

            $name = null;
            $start = $tokens[$index]->span->start;
            if (($tokens[$index]->type === 'identifier' || $tokens[$index]->type === 'variable') && ($tokens[$index + 1] ?? null)?->value === '=') {
                $name = ltrim($tokens[$index]->value, '$');
                $index += 2;
            }

            $sliceStart = $index;
            $depth = 0;
            while (($tokens[$index] ?? null) !== null && $tokens[$index]->type !== 'eof') {
                $token = $tokens[$index];
                if ($token->value === '(' || $token->value === '[') {
                    $depth++;
                }
                if ($token->value === ')' || $token->value === ']') {
                    $depth = max(0, $depth - 1);
                }
                if ($depth === 0 && $token->type === 'punct' && $token->value === ',') {
                    break;
                }
                if ($depth === 0 && $token->type === 'identifier' && ($tokens[$index + 1] ?? null)?->value === '=') {
                    break;
                }
                $index++;
            }

            $slice = array_slice($tokens, $sliceStart, $index - $sliceStart);
            if ($slice === []) {
                break;
            }
            $slice[] = new ExpressionToken('eof', '', end($slice)->span);

            $parser = new self($this->lexer);
            $parser->tokens = $slice;
            $parser->index = 0;
            $expression = $parser->parseExpression(0);
            $diagnostics = array_merge($diagnostics, $parser->diagnostics);

            $end = end($slice)->span->end;
            $args[] = [
                'name' => $name,
                'value' => $expression,
                'span' => new SourceSpan($start, $end),
            ];

            if (($tokens[$index] ?? null)?->value === ',') {
                $index++;
            }
        }

        return [$args, $diagnostics];
    }

    private function parseExpression(int $minPrecedence): ExpressionNode
    {
        $left = $this->parsePrefix();
        $left = $this->parsePostfix($left);

        while (true) {
            $token = $this->current();
            if ($token->type === 'operator' && $token->value === '?') {
                $this->consume();
                $ifTrue = $this->parseExpression(0);
                if ($this->current()->value !== ':') {
                    $this->diagnostics[] = new Diagnostic('EXPR002', 'Expected : in ternary expression.', Severity::Error, $token->span, true);
                    return $left;
                }
                $colon = $this->consume();
                $ifFalse = $this->parseExpression(0);
                $left = new TernaryExpressionNode(
                    new SourceSpan($left->span->start, $ifFalse->span->end),
                    $left,
                    $ifTrue,
                    $ifFalse,
                );
                continue;
            }

            $precedence = $this->precedence($token);
            if ($precedence < $minPrecedence) {
                break;
            }

            $operator = $token->value;
            $this->consume();
            $right = $this->parseExpression($precedence + 1);
            $left = new BinaryExpressionNode(
                new SourceSpan($left->span->start, $right->span->end),
                $operator,
                $left,
                $right,
            );
        }

        return $left;
    }

    private function parsePrefix(): ExpressionNode
    {
        $token = $this->current();
        if ($token->type === 'operator' && in_array($token->value, ['!', '-', '+'], true)) {
            $operator = $this->consume();
            $right = $this->parseExpression(100);

            return new UnaryExpressionNode(new SourceSpan($operator->span->start, $right->span->end), $operator->value, $right);
        }

        if ($token->type === 'identifier' && strtolower($token->value) === 'not') {
            $operator = $this->consume();
            $right = $this->parseExpression(100);

            return new UnaryExpressionNode(new SourceSpan($operator->span->start, $right->span->end), 'not', $right);
        }

        if ($token->type === 'variable') {
            $var = $this->consume();
            return new VariableExpressionNode($var->span, substr($var->value, 1));
        }

        if ($token->type === 'number') {
            $num = $this->consume();
            $value = str_contains($num->value, '.') ? (float)$num->value : (int)$num->value;
            return new LiteralExpressionNode($num->span, 'number', $value);
        }

        if ($token->type === 'string') {
            $str = $this->consume();
            return new LiteralExpressionNode($str->span, 'string', stripslashes(substr($str->value, 1, -1)));
        }

        if ($token->type === 'identifier') {
            $id = $this->consume();
            $name = strtolower($id->value);
            if ($name === 'true' || $name === 'false') {
                return new LiteralExpressionNode($id->span, 'bool', $name === 'true');
            }
            if ($name === 'null') {
                return new LiteralExpressionNode($id->span, 'null', null);
            }
            if ($name === 'array' && $this->current()->value === '(') {
                return $this->parseArrayLike($id->span->start);
            }

            return new IdentifierExpressionNode($id->span, $id->value);
        }

        if ($token->value === '(') {
            $open = $this->consume();
            $expr = $this->parseExpression(0);
            if ($this->current()->value === ')') {
                $close = $this->consume();
                return new UnaryExpressionNode(new SourceSpan($open->span->start, $close->span->end), 'group', $expr);
            }

            return $expr;
        }

        $this->diagnostics[] = new Diagnostic('EXPR003', 'Unexpected token in expression.', Severity::Error, $token->span, true);
        $bad = $this->consume();
        return new ErrorExpressionNode($bad->span, 'Unexpected token');
    }

    private function parsePostfix(ExpressionNode $left): ExpressionNode
    {
        while (true) {
            $token = $this->current();
            if ($token->value === '(') {
                $open = $this->consume();
                $args = [];
                while ($this->current()->type !== 'eof' && $this->current()->value !== ')') {
                    $args[] = $this->parseExpression(0);
                    if ($this->current()->value === ',') {
                        $this->consume();
                    }
                }
                $close = $this->current()->value === ')' ? $this->consume() : $open;
                $left = new CallExpressionNode(new SourceSpan($left->span->start, $close->span->end), $left, $args);
                continue;
            }

            if ($token->value === '[') {
                $open = $this->consume();
                $index = $this->parseExpression(0);
                $close = $this->current()->value === ']' ? $this->consume() : $open;
                $left = new ArrayAccessExpressionNode(new SourceSpan($left->span->start, $close->span->end), $left, $index);
                continue;
            }

            if ($token->value === '.' || $token->value === '->') {
                $objectAccess = $token->value === '->';
                $this->consume();
                $propertyToken = $this->consume();
                if (!in_array($propertyToken->type, ['identifier', 'variable'], true)) {
                    $this->diagnostics[] = new Diagnostic('EXPR004', 'Expected property name after access operator.', Severity::Error, $propertyToken->span, true);
                    return $left;
                }
                $left = new PropertyFetchExpressionNode(
                    new SourceSpan($left->span->start, $propertyToken->span->end),
                    $left,
                    ltrim($propertyToken->value, '$'),
                    $objectAccess,
                );
                continue;
            }

            if ($token->value === '|') {
                $left = $this->parseModifiers($left);
                continue;
            }

            break;
        }

        return $left;
    }

    private function parseModifiers(ExpressionNode $base): ExpressionNode
    {
        $modifiers = [];
        $end = $base->span->end;

        while ($this->current()->value === '|') {
            $pipe = $this->consume();
            $name = $this->consume();
            if ($name->type !== 'identifier') {
                $this->diagnostics[] = new Diagnostic('EXPR005', 'Expected modifier name after |.', Severity::Error, $name->span, true);
                break;
            }

            $arguments = [];
            while ($this->current()->value === ':') {
                $this->consume();
                $arguments[] = $this->parseExpression(80);
            }

            $modifiers[] = new ModifierNode(new SourceSpan($pipe->span->start, ($arguments !== [] ? end($arguments)->span : $name->span)->end), $name->value, $arguments);
            $end = $modifiers[array_key_last($modifiers)]->span->end;
        }

        return new ModifierChainExpressionNode(new SourceSpan($base->span->start, $end), $base, $modifiers);
    }

    private function parseArrayLike(\Dev\Smarty\Ast\Position $start): ExpressionNode
    {
        $open = $this->consume();
        if ($open->value !== '(') {
            return new ErrorExpressionNode($open->span, 'Expected ( after array');
        }

        $items = [];
        while ($this->current()->type !== 'eof' && $this->current()->value !== ')') {
            $items[] = $this->parseExpression(0);
            if ($this->current()->value === ',') {
                $this->consume();
            }
        }

        $close = $this->current()->value === ')' ? $this->consume() : $open;

        return new ArrayExpressionNode(new SourceSpan($start, $close->span->end), $items);
    }

    private function precedence(ExpressionToken $token): int
    {
        if ($token->type === 'identifier') {
            return match (strtolower($token->value)) {
                'or' => 1,
                'and' => 2,
                'eq', 'ne', 'lt', 'lte', 'gt', 'gte' => 4,
                default => -1,
            };
        }

        if ($token->type !== 'operator') {
            return -1;
        }

        return match ($token->value) {
            '||' => 1,
            '&&' => 2,
            '==', '!=', '>', '<', '>=', '<=', '??' => 4,
            '+', '-', '.' => 5,
            '*', '/', '%' => 6,
            default => -1,
        };
    }

    private function current(): ExpressionToken
    {
        return $this->tokens[$this->index] ?? $this->tokens[array_key_last($this->tokens)];
    }

    private function consume(): ExpressionToken
    {
        $token = $this->current();
        $this->index++;
        return $token;
    }
}
