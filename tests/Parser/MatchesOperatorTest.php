<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Parser;

use SmartyAst\Ast\CallExpressionNode;
use SmartyAst\Ast\IdentifierExpressionNode;
use SmartyAst\Ast\LiteralExpressionNode;
use SmartyAst\Ast\VariableExpressionNode;
use SmartyAst\Parser\SmartyParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MatchesOperatorTest extends TestCase
{
    private SmartyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SmartyParser();
    }

    // ------------------------------------------------------------------
    // Parse-without-errors
    // ------------------------------------------------------------------

    #[DataProvider('matchesExpressions')]
    public function testMatchesExpressionParsesWithoutErrors(string $expression): void
    {
        $template = "{if $expression}ok{/if}";
        $result = $this->parser->parseString($template);
        $errors = array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error');

        $this->assertCount(0, $errors, "Expected no parse errors for: $expression");
    }

    public static function matchesExpressions(): array
    {
        return [
            ['$a matches $b'],
            ['$email matches "/^[^@]+@[^@]+\.[^@]+$/"'],
            ['"HELLO" matches "/hello/i"'],
            ['$a matches $b && $c matches $d'],
            ['$a matches $b || $c matches $d'],
            ['$a matches "/^[0-9]+$/" || $a matches "/^[a-z]+$/"'],
            ['$a == $b && $a matches "/pattern/"'],
        ];
    }

    // ------------------------------------------------------------------
    // AST shape: lowers to preg_match($pattern, $subject)
    // ------------------------------------------------------------------

    public function testMatchesLowersToPregMatchCall(): void
    {
        $condition = $this->parseIfCondition('$a matches $b');

        $this->assertInstanceOf(CallExpressionNode::class, $condition);
        $this->assertInstanceOf(IdentifierExpressionNode::class, $condition->callee);
        $this->assertSame('preg_match', $condition->callee->name);
        $this->assertCount(2, $condition->arguments);
    }

    public function testMatchesFirstArgumentIsPattern(): void
    {
        $condition = $this->parseIfCondition('$subject matches $pattern');

        $this->assertInstanceOf(CallExpressionNode::class, $condition);
        // preg_match($pattern, $subject) — pattern is first argument
        $this->assertInstanceOf(VariableExpressionNode::class, $condition->arguments[0]);
        $this->assertSame('pattern', $condition->arguments[0]->name);
    }

    public function testMatchesSecondArgumentIsSubject(): void
    {
        $condition = $this->parseIfCondition('$subject matches $pattern');

        $this->assertInstanceOf(CallExpressionNode::class, $condition);
        // preg_match($pattern, $subject) — subject is second argument
        $this->assertInstanceOf(VariableExpressionNode::class, $condition->arguments[1]);
        $this->assertSame('subject', $condition->arguments[1]->name);
    }

    public function testMatchesWithStringLiteralPattern(): void
    {
        $condition = $this->parseIfCondition('$email matches "/^[^@]+@[^@]+/"');

        $this->assertInstanceOf(CallExpressionNode::class, $condition);
        $this->assertSame('preg_match', $condition->callee->name);
        // first arg is the pattern string literal
        $this->assertInstanceOf(LiteralExpressionNode::class, $condition->arguments[0]);
        $this->assertSame('string', $condition->arguments[0]->literalType);
        // second arg is the subject variable
        $this->assertInstanceOf(VariableExpressionNode::class, $condition->arguments[1]);
        $this->assertSame('email', $condition->arguments[1]->name);
    }

    public function testMatchesInPrintContextParsesWithoutErrors(): void
    {
        $result = $this->parser->parseString('{$a matches $b}');
        $errors = array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error');
        $this->assertCount(0, $errors);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function parseIfCondition(string $expression): mixed
    {
        $template = "{if $expression}ok{/if}";
        $result = $this->parser->parseString($template);
        $errors = array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error');
        $this->assertCount(0, $errors, "Unexpected parser errors for expression: $expression");

        $block = $result->ast->children[0] ?? null;
        $this->assertNotNull($block);
        $this->assertNotEmpty($block->openTag->arguments);

        return $block->openTag->arguments[0]->value;
    }
}
