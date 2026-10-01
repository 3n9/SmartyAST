<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Parser;

use SmartyAst\Parser\SmartyParser;
use PHPUnit\Framework\TestCase;

final class AstGenerationTest extends TestCase
{
    public function testAstForVariableWithModifier(): void
    {
        $parser = new SmartyParser();
        $result = $parser->parseString('{$a|toUpper}');

        $this->assertSame(
            [
                'kind' => 'Document',
                'span' => [
                    'start' => ['offset' => 0, 'line' => 1, 'column' => 1],
                    'end' => ['offset' => 12, 'line' => 1, 'column' => 13],
                ],
                'children' => [
                    [
                        'kind' => 'Print',
                        'span' => [
                            'start' => ['offset' => 0, 'line' => 1, 'column' => 1],
                            'end' => ['offset' => 12, 'line' => 1, 'column' => 13],
                        ],
                        'trimLeft' => false,
                        'trimRight' => false,
                        'expression' => [
                            'kind' => 'ModifierChainExpression',
                            'span' => [
                                'start' => ['offset' => 1, 'line' => 1, 'column' => 2],
                                'end' => ['offset' => 11, 'line' => 1, 'column' => 12],
                            ],
                            'base' => [
                                'kind' => 'VariableExpression',
                                'span' => [
                                    'start' => ['offset' => 1, 'line' => 1, 'column' => 2],
                                    'end' => ['offset' => 3, 'line' => 1, 'column' => 4],
                                ],
                                'name' => 'a',
                            ],
                            'modifiers' => [
                                [
                                    'kind' => 'Modifier',
                                    'span' => [
                                        'start' => ['offset' => 3, 'line' => 1, 'column' => 4],
                                        'end' => ['offset' => 11, 'line' => 1, 'column' => 12],
                                    ],
                                    'name' => 'toUpper',
                                    'arguments' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $result->ast->toArray(),
        );
    }
}
