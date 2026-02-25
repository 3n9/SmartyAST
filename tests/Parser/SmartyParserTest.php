<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Parser;

use SmartyAst\Parser\SmartyParser;
use PHPUnit\Framework\TestCase;

final class SmartyParserTest extends TestCase
{
    public function testParsesBlockAndShorthandTags(): void
    {
        $source = <<<'TPL'
{assign var='title' value='Hello'}
{include 'header.tpl'}
{if $user.active}
  {$user.name|escape:'html'}
{else}
  Guest
{/if}
TPL;

        $result = (new SmartyParser())->parseString($source);

        self::assertNotEmpty($result->ast->children);
        self::assertCount(0, array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error'));

        $ifBlock = null;
        foreach ($result->ast->children as $node) {
            if ($node->kind === 'BlockTag' && $node->openTag->name === 'if') {
                $ifBlock = $node;
                break;
            }
        }

        self::assertNotNull($ifBlock);
        self::assertCount(1, $ifBlock->elseBranches);

        $includeTag = null;
        foreach ($result->ast->children as $node) {
            if ($node->kind === 'Tag' && $node->name === 'include') {
                $includeTag = $node;
                break;
            }
        }

        self::assertNotNull($includeTag);
        self::assertTrue($includeTag->isShorthand);
    }

    public function testParsesPhpDocAnnotationsFromSmartyComments(): void
    {
        $source = <<<'TPL'
{*
 * @var User $user Current user
 * @param string $title Page title
 * @custom anything
*}
{$user.name}
TPL;

        $result = (new SmartyParser())->parseString($source);

        $comment = $result->ast->children[0];
        self::assertSame('Comment', $comment->kind);
        self::assertCount(3, $comment->annotations);
        self::assertSame('var', $comment->annotations[0]->name);
        self::assertSame('user', $comment->annotations[0]->data['name']);

        foreach ($result->ast->children as $node) {
            self::assertGreaterThan(0, $node->span->start->line);
            self::assertGreaterThan(0, $node->span->start->column);
            self::assertGreaterThanOrEqual($node->span->start->offset, $node->span->end->offset);
        }
    }

    public function testRecoversFromUnclosedBlock(): void
    {
        $source = <<<'TPL'
{if $x > 1}
  Value
TPL;

        $result = (new SmartyParser())->parseString($source);

        self::assertNotEmpty($result->diagnostics);
        self::assertNotEmpty($result->ast->children);
        self::assertSame('BlockTag', $result->ast->children[0]->kind);
    }
}
