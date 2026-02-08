# Smarty AST Parser

Parse Smarty templates into a typed AST with source positions and recoverable diagnostics.

## Install

This project is currently configured as a local package (`dev/smarty`). In another tool/project, add it via Composer (path/VCS as needed), then:

```bash
composer install
```

## Quick Start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Dev\Smarty\SmartyAstParser;

$parser = new SmartyAstParser();
$result = $parser->parseString("{if $user.active}{$user.name}{else}Guest{/if}");

$ast = $result->ast;
$diagnostics = $result->diagnostics;
```

## API

- `Dev\Smarty\SmartyAstParser::parseString(string $source, ?ParseOptions $options = null): ParseResult`
- `Dev\Smarty\SmartyAstParser::parseFile(string $path, ?ParseOptions $options = null): ParseResult`

`ParseResult`:

- `ast` (`DocumentNode`)
- `diagnostics` (`Diagnostic[]`)
- `tokens` (optional, when `collectTokens=true`)

## Parse Options

```php
use Dev\Smarty\ParseOptions;

$options = new ParseOptions(
    leftDelimiter: '{',
    rightDelimiter: '}',
    recoverErrors: true,
    collectTokens: false,
    commentParsers: [] // or custom parsers
);
```

## Diagnostics for Linters

Use diagnostics directly for rule output:

```php
foreach ($result->diagnostics as $d) {
    printf(
        "[%s] %s at %d:%d\n",
        $d->code,
        $d->message,
        $d->span->start->line,
        $d->span->start->column
    );
}
```

## Node Positions

Every AST node has a full source span:

- `span->start->offset`, `span->start->line`, `span->start->column`
- `span->end->offset`, `span->end->line`, `span->end->column`

This is suitable for:

- precise linter ranges
- IDE highlights/code actions
- file diff annotations in CI

## AST Traversal Example

```php
use Dev\Smarty\Ast\BlockTagNode;
use Dev\Smarty\Ast\Node;
use Dev\Smarty\Ast\TagNode;

/** @param list<Node> $nodes */
function walk(array $nodes): void {
    foreach ($nodes as $node) {
        if ($node instanceof TagNode) {
            echo "Tag: {$node->name}\n";
        }

        if ($node instanceof BlockTagNode) {
            echo "Block: {$node->openTag->name}\n";
            walk($node->children);
            foreach ($node->elseBranches as $branch) {
                walk($branch->children);
            }
        }
    }
}

walk($result->ast->children);
```

## Full Tags and Shorthand Tags

The parser supports both:

- full/named arguments: `{include file='header.tpl'}`
- shorthand/positional arguments: `{include 'header.tpl'}`

`TagNode::$isShorthand` is set when positional shorthand syntax is used.

## Comment Annotation Plugins

Comment parsing is pluggable via `CommentParserInterface`.

Built-in parser:

- `Dev\Smarty\Comments\PhpDocTemplateAnnotationParser`
- Parses phpDoc-like annotations in Smarty comments (`{* ... *}`), such as `@var`, `@param`, and custom tags.

Custom plugin example:

```php
use Dev\Smarty\Ast\CommentNode;
use Dev\Smarty\Comments\CommentParseContext;
use Dev\Smarty\Comments\CommentParseResult;
use Dev\Smarty\Comments\CommentParserInterface;

final class MyCommentParser implements CommentParserInterface
{
    public function parse(CommentNode $comment, CommentParseContext $context): CommentParseResult
    {
        // Inspect $comment->text and return parsed annotations/diagnostics.
        return new CommentParseResult();
    }
}
```

Register plugin:

```php
use Dev\Smarty\ParseOptions;

$options = new ParseOptions(commentParsers: [new MyCommentParser()]);
$result = (new Dev\Smarty\SmartyAstParser())->parseFile('path/to/template.tpl', $options);
```

## Integrating in Other Tools

### 1) CLI Linter

- Parse each `.tpl` file with `parseFile()`
- Emit parser diagnostics
- Run custom rules over AST nodes
- Exit non-zero if violations exist

### 2) CI Checks

- Run parser in a job over changed templates
- Convert diagnostics to CI annotations (line/column from node spans)

### 3) Editor/Language Tooling

- Parse on file save or debounce while typing
- Use `recoverErrors=true` for partial AST during invalid intermediate states
- Use spans for squiggles, hover ranges, quick fixes

## Testing

Run test suite:

```bash
composer test
# or
vendor/bin/phpunit -c phpunit.xml
```

Current suite includes built-in tags, shorthand forms, else variants, comment annotation parsing, interpolation cases, operator compatibility, assignment/array parsing, and recovery cases.

## Expression Syntax Support

The parser supports Smarty/PHP-like expression forms used in tags and print expressions:

- variable/object/array access:
  - `{$foo}`
  - `{$foo[4]}`
  - `{$foo.bar}`
  - `{$foo.$bar}`
  - `{$foo->bar()}`
  - `{$object->method1($x)->method2($y)}`
- assignment expressions:
  - `{$foo=$bar+2}`
  - `{$foo.bar=1}`
  - `{$foo[]=1}`
- arrays (including multiline):
  - `{assign var="arr" value=[1,2,3]}`
  - `{assign var="arr" value=['k'=>'v', 'b'=>'c']}`
  - multiline values in `assign` and print assignments
- string interpolation:
  - double-quoted variable interpolation (`$foo`, `$foo_bar`)
  - backtick interpolation (for complex embedded expressions)
  - embedded `{...}` expressions inside double-quoted strings
- modern operators:
  - ternary: `a ? b : c`
  - shorthand ternary (elvis): `a ?: c`
  - null coalescing: `a ?? c`
- comparison/operator aliases:
  - `eq`, `ne`, `neq`, `gt`, `lt`, `gte`, `ge`, `lte`, `le`, `mod`
- Smarty predicates:
  - `is [not] div by`
  - `is [not] even`
  - `is [not] even by`
  - `is [not] odd`
  - `is [not] odd by`
  - `is [not] in`

## Config Shorthand

Config shorthand is supported:

- `{#foo#}`
- `{$smarty.config.foo}` (equivalent)
