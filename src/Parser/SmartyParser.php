<?php

declare(strict_types=1);

namespace SmartyAst\Parser;

use SmartyAst\Lexer\TemplateLexer;
use SmartyAst\ParseOptions;
use SmartyAst\ParseResult;
use SmartyAst\ParseException;
use SmartyAst\Diagnostics\Severity;

final class SmartyParser
{
    public function parseString(string $source, ?ParseOptions $options = null): ParseResult
    {
        $options ??= new ParseOptions();

        $lexer = new TemplateLexer($options);
        $lexResult = $lexer->tokenize($source);
        $this->checkRecovery($lexResult->diagnostics, $options);

        $templateParser = new TemplateParser();
        [$ast, $parserDiagnostics] = $templateParser->parse($lexResult->tokens, $options);
        $this->checkRecovery($parserDiagnostics, $options);

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

    /** @param list<\SmartyAst\Diagnostics\Diagnostic> $diagnostics */
    private function checkRecovery(array $diagnostics, ParseOptions $options): void
    {
        if (!$options->recoverErrors) {
            foreach ($diagnostics as $diagnostic) {
                if ($diagnostic->severity === Severity::Error) {
                    throw new ParseException($diagnostic);
                }
            }
        }
    }
}
