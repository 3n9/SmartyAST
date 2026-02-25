<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Parser;

use SmartyAst\Parser\SmartyParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OperatorSupportTest extends TestCase
{
    private SmartyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SmartyParser();
    }

    #[DataProvider('operatorExpressions')]
    public function testOperatorExpressionParsesWithoutErrors(string $expression): void
    {
        $template = "{if $expression}ok{/if}";
        $result = $this->parser->parseString($template);
        $errors = array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error');

        $this->assertCount(0, $errors, "Expected no parse errors for: $expression");
    }

    public static function operatorExpressions(): array
    {
        return [
            ['\$a == \$b'],
            ['\$a eq \$b'],
            ['\$a != \$b'],
            ['\$a ne \$b'],
            ['\$a neq \$b'],
            ['\$a > \$b'],
            ['\$a gt \$b'],
            ['\$a < \$b'],
            ['\$a lt \$b'],
            ['\$a >= \$b'],
            ['\$a gte \$b'],
            ['\$a ge \$b'],
            ['\$a <= \$b'],
            ['\$a lte \$b'],
            ['\$a le \$b'],
            ['\$a === 0'],
            ['not \$a'],
            ['!\$a'],
            ['\$a % \$b'],
            ['\$a mod \$b'],
            ['\$a is div by 4'],
            ['\$a is not div by 4'],
            ['\$a is even'],
            ['\$a is not even'],
            ['\$a is even by \$b'],
            ['\$a is not even by \$b'],
            ['\$a is odd'],
            ['\$a is not odd'],
            ['\$a is odd by \$b'],
            ['\$a is not odd by \$b'],
            ['\$a is in \$b'],
            ['\$a is not in \$b'],
            ['\$a && \$b'],
            ['\$a || \$b'],
            ['\$a and \$b'],
            ['\$a or \$b'],
            ['\$a !== \$b'],
            ['\$a matches \$b'],
            ['(\$a < 1) or (\$b && (\$c is not in \$d))'],
        ];
    }
}
