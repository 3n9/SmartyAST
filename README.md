# SmartyAst

Parse Smarty templates into a typed AST with source positions and recoverable diagnostics.

## Install

```bash
composer require 3n9/smarty-ast
```

## Quick Start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use SmartyAst\Parser\SmartyParser;

$parser = new SmartyParser();
$result = $parser->parseString("{if \$user.active}{\$user.name}{else}Guest{/if}");

$ast = $result->ast;          // DocumentNode
$diagnostics = $result->diagnostics; // Diagnostic[]
```

## API

- `SmartyAst\Parser\SmartyParser::parseString(string $source, ?ParseOptions $options = null): ParseResult`
- `SmartyAst\Parser\SmartyParser::parseFile(string $path, ?ParseOptions $options = null): ParseResult`

`SmartyAstParser` is a stateful facade over `SmartyParser` that accepts an optional custom parser instance — useful for dependency injection:

```php
use SmartyAst\SmartyAstParser;

$parser = new SmartyAstParser(); // wraps a default SmartyParser internally
$result = $parser->parseString('{$foo}');
```

`ParseResult`:

- `ast` — `DocumentNode`
- `diagnostics` — `Diagnostic[]`
- `tokens` — optional, when `collectTokens=true`

`ParseResult` also exposes convenience serialisation:

```php
$result->toArray(); // ['ast' => [...], 'diagnostics' => [...], 'tokens' => [...]]
$result->toJson(JSON_PRETTY_PRINT); // JSON string
```

Every `Node` likewise supports:

```php
$node->toArray();       // plain PHP array
$node->toJson();        // JSON string (passes optional $flags to json_encode)
```

## Parse Options

```php
use SmartyAst\Comments\PhpDocTemplateAnnotationParser;
use SmartyAst\ParseOptions;

$options = new ParseOptions(
    leftDelimiter: '{',
    rightDelimiter: '}',
    recoverErrors: true,
    collectTokens: false,
    commentParsers: [new PhpDocTemplateAnnotationParser()], // default; pass [] to disable
    phpVersion: '8.1', // gates PHP 8+ named-argument syntax (e.g. func(name: $val))
);
```

## Diagnostics

Parser errors and warnings are returned as `Diagnostic` objects alongside the AST:

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

Each diagnostic has:
- `code` — unique error code (e.g. `PARSE001`, `EXPR001`)
- `message` — human-readable description
- `severity` — `Severity::Error`, `Severity::Warning`, or `Severity::Info`
- `span` — source location
- `recoverable` — whether the parser produced a partial AST node

## Node Positions

Every AST node carries a full source span:

- `span->start->offset`, `span->start->line`, `span->start->column`
- `span->end->offset`, `span->end->line`, `span->end->column`

Suitable for precise linter ranges, IDE highlights, and CI annotations.

## AST Traversal

All nodes implement `children(): list<Node>` for recursive traversal:

```php
use SmartyAst\Ast\Node;
use SmartyAst\Ast\TagLike;

function walk(Node $node): void {
    if ($node instanceof TagLike) {
        $tag = $node->resolveTag();
        echo "{$tag->name} at line {$tag->span->start->line}\n";
    }

    foreach ($node->children() as $child) {
        walk($child);
    }
}

walk($result->ast);
```

`TagLike` is implemented by both `TagNode` (inline tags) and `BlockTagNode` (block tags with children/else branches). `resolveTag()` returns the underlying `TagNode` in both cases.

For expression-level traversal, `ExpressionNode` subclasses implement `childExpressions(): list<ExpressionNode>`.

## Full Tags and Shorthand Tags

The parser supports both named and positional arguments:

```smarty
{include file='header.tpl'}   {* named *}
{include 'header.tpl'}        {* shorthand *}
```

`TagNode::$isShorthand` is `true` when positional shorthand syntax is used.

## Comment Annotation Plugins

Comment parsing is pluggable via `CommentParserInterface`.

Built-in: `SmartyAst\Comments\PhpDocTemplateAnnotationParser` — parses phpDoc-style
annotations (`@var`, `@param`, custom tags) inside Smarty comments (`{* ... *}`).

Custom plugin:

```php
use SmartyAst\Ast\CommentNode;
use SmartyAst\Comments\CommentParseContext;
use SmartyAst\Comments\CommentParseResult;
use SmartyAst\Comments\CommentParserInterface;

final class MyCommentParser implements CommentParserInterface
{
    public function parse(CommentNode $comment, CommentParseContext $context): CommentParseResult
    {
        // Inspect $comment->text and return parsed annotations/diagnostics.
        return new CommentParseResult();
    }
}
```

Register it via `ParseOptions`:

```php
use SmartyAst\ParseOptions;
use SmartyAst\Parser\SmartyParser;

$options = new ParseOptions(commentParsers: [new MyCommentParser()]);
$result = (new SmartyParser())->parseFile('path/to/template.tpl', $options);
```

## Integrating in Other Tools

### CLI Linter

- Parse each `.tpl` file with `parseFile()`
- Emit `$result->diagnostics` for parser errors
- Walk the AST with custom rules
- Exit non-zero if any issues exist

### CI Checks

- Run the parser over changed templates
- Convert `Diagnostic` spans to CI annotations (GitHub, GitLab, etc.)

### Editor / Language Tooling

- Parse on save or with debounce while typing
- Use `recoverErrors: true` for a partial AST during invalid intermediate states
- Use node spans for squiggles, hover ranges, and quick fixes

## Testing

```bash
composer test
```

## Expression Syntax Support

The parser handles Smarty/PHP-style expressions in tags and print statements:

- **variable / property / array access**
  - `{$foo}`, `{$foo[4]}`, `{$foo.bar}`, `{$foo.$bar}`
  - `{$foo->bar()}`, `{$object->method1($x)->method2($y)}`
  - static access: `{Cls::method()}`, `{Cls::$prop}`, `{Cls::CONST}`
- **assignment**
  - `{$foo=$bar+2}`, `{$foo.bar=1}`, `{$foo[]=1}`
- **arrays** (including multiline)
  - `[1, 2, 3]`, `['k' => 'v', 'b' => 'c']`
- **spread operator** (`...`)
  - in calls: `{func(...$args)}`
  - in array literals: `[...$a, ...$b]`
- **modifiers**
  - `{$foo|upper}`, `{$foo|truncate:80:'...'}`, chained: `{$foo|escape:'html'|nl2br}`
  - represented as `ModifierChainExpressionNode` wrapping the base expression and a list of `ModifierNode`s
- **string interpolation**
  - double-quoted: `"hello $name"`, `` `$foo` ``
  - embedded blocks: `"status is {if $ok}ok{/if}"`
  - single-quoted strings are **not** interpolated
- **bitshift operators** — `>>` and `<<`
- **PHP 8 named arguments** (when `phpVersion >= '8.0'`)
  - `{func(name: $val, other: 42)}`; each argument becomes a `NamedArgumentExpressionNode` with `name: string` and `value: ExpressionNode`
- **operators**
  - ternary: `a ? b : c`, elvis: `a ?: c`, null coalescing: `a ?? c`
  - symbolic: `&&`, `||`, `!==`, `===`, `!=`, `==`, `<`, `>`, `<=`, `>=`
  - word aliases: `and`, `or`, `eq`, `ne`/`neq`, `gt`, `lt`, `gte`/`ge`, `lte`/`le`, `mod`
  - `matches` — `$subject matches $pattern` lowers to `preg_match($pattern, $subject)`; produces a `CallExpressionNode` (not a binary expression) with callee `preg_match` and arguments `[$pattern, $subject]`
- **Smarty predicates**
  - `is [not] div by`, `is [not] even [by]`, `is [not] odd [by]`, `is [not] in`

## Whitespace Control

Leading and trailing whitespace around a tag can be trimmed by placing `-` inside the delimiter:

```smarty
{- $foo -}     {* trims whitespace before and after *}
{- include file='x.tpl'}   {* trims only leading whitespace *}
{if $x -}content{- /if}    {* trim after open-tag and before close-tag *}
```

`TagNode` and `PrintNode` gain `trimLeft: bool` / `trimRight: bool`.
`BlockTagNode` gains `closeTrimLeft: bool` / `closeTrimRight: bool` for the close tag.

## Raw Content Blocks

`{literal}…{/literal}` and `{php}…{/php}` are raw-content block tags — their inner content is captured as `TextNode` children without any template parsing. Useful for JavaScript snippets or legacy inline PHP.

```smarty
{literal}
  var x = {a: 1};  {* braces not parsed *}
{/literal}

{php}
  echo "hello";    {* inner content is raw text *}
{/php}
```
## Config Shorthand

- `{#foo#}` — equivalent to `{$smarty.config.foo}`
