<?php

declare(strict_types=1);

namespace Dev\Smarty\Parser;

use Dev\Smarty\Lexer\TemplateLexer;
use Dev\Smarty\ParseOptions;
use Dev\Smarty\ParseResult;

final class SmartyParser
{
    public function parseString(string $source, ?ParseOptions $options = null): ParseResult
    {
        $options ??= new ParseOptions();

        $lexer = new TemplateLexer($options);
        $lexResult = $lexer->tokenize($source);

        $templateParser = new TemplateParser();
        [$ast, $parserDiagnostics] = $templateParser->parse($lexResult->tokens, $options);

        $tokens = [];
        if ($options->collectTokens) {
            $tokens = array_map(static fn ($token) => $token->toArray(), $lexResult->tokens);
        }

        return new ParseResult(
            $ast,
            array_merge($lexResult->diagnostics, $parserDiagnostics),
            $tokens,
        );
    }

    public function parseFile(string $path, ?ParseOptions $options = null): ParseResult
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Cannot read file: %s', $path));
        }

        return $this->parseString($content, $options);
    }
}
