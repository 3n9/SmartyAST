<?php

declare(strict_types=1);

namespace Dev\Smarty\Tests\Parser;

use Dev\Smarty\Parser\SmartyParser;
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
                        'expression' => [
                            'kind' => 'ModifierChainExpression',
                            'span' => [
                                'start' => ['offset' => 0, 'line' => 1, 'column' => 1],
                                'end' => ['offset' => 10, 'line' => 1, 'column' => 11],
                            ],
                            'base' => [
                                'kind' => 'VariableExpression',
                                'span' => [
                                    'start' => ['offset' => 0, 'line' => 1, 'column' => 1],
                                    'end' => ['offset' => 2, 'line' => 1, 'column' => 3],
                                ],
                                'name' => 'a',
                            ],
                            'modifiers' => [
                                [
                                    'kind' => 'Modifier',
                                    'span' => [
                                        'start' => ['offset' => 2, 'line' => 1, 'column' => 3],
                                        'end' => ['offset' => 10, 'line' => 1, 'column' => 11],
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
