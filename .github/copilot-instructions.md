# Copilot Instructions

## Commands

```bash
composer test                          # run full test suite
vendor/bin/phpunit -c phpunit.xml --filter testMethodName   # run single test
vendor/bin/phpunit -c phpunit.xml tests/Parser/BuiltinTagsTest.php  # run single file
```

## Architecture

The pipeline is: **source string → TemplateLexer → TemplateParser → (ExpressionParser for inline expressions) → ParseResult**

- `SmartyAstParser` — thin public facade; delegates to `SmartyParser`
- `Parser/SmartyParser` — orchestrates the full pipeline; returns `ParseResult` (ast + diagnostics + optional tokens)
- `Lexer/TemplateLexer` — tokenizes template text into `TemplateToken[]` (text, tag, comment, eof, etc.)
- `Parser/TemplateParser` — converts token stream into the AST; uses a stack to handle nested block tags (`{if}…{/if}`, `{foreach}…{/foreach}`)
- `Parser/ExpressionParser` + `Parser/ExpressionLexer` — called by `TemplateParser` to parse inline expressions inside `{…}` delimiters
- `Ast/` — all node types; every node extends `Node` and carries a `kind` string, a `SourceSpan`, and a `toArray()` method
- `Diagnostics/` — `Diagnostic` (code, message, span, severity) and `Severity` enum
- `Comments/` — pluggable comment annotation parsing via `CommentParserInterface`; built-in: `PhpDocTemplateAnnotationParser`

### Key node types

| Node | When used |
|---|---|
| `DocumentNode` | root; holds `list<Node> $children` |
| `TextNode` | raw template text between tags |
| `PrintNode` | `{$expr}` — wraps an `ExpressionNode` |
| `TagNode` | single tag like `{include file='x'}` |
| `BlockTagNode` | paired tag like `{if}…{/if}`; has `openTag`, `children`, `elseBranches`, `closeSpan` |
| `ElseBranchNode` | `{else}` / `{elseif …}` inside a block |
| `CommentNode` | `{* … *}`; holds parsed `annotations` after comment plugins run |
| `ForeachIterationPropertyNode` | postfix `$item@first` / `@last` / etc.; has `target: ExpressionNode` and `property: string` |

## Conventions

- All classes use `declare(strict_types=1)` and are `final` unless designed for extension
- Constructor property promotion with `readonly` is used throughout
- Namespaces: `SmartyAst\` → `src/`, `SmartyAst\Tests\` → `tests/`
- Tests use `SmartyParser` directly, not the `SmartyAstParser` facade
- Tests use PHPUnit 13 `#[DataProvider]` attribute syntax (not the older `@dataProvider` docblock)
- Fixture `.tpl` files live in `tests/fixtures/valid/` and `tests/fixtures/invalid/`
- Every `Node` subclass implements `toArray()` returning a plain array — tests assert against this shape
- `ParseOptions` is the single config object passed through the entire pipeline (delimiters, `recoverErrors`, `collectTokens`, `commentParsers`)
- Diagnostics from both the lexer and the parser are merged and returned together in `ParseResult`
