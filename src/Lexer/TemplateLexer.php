<?php

declare(strict_types=1);

namespace SmartyAst\Lexer;

use SmartyAst\Ast\Position;
use SmartyAst\Ast\SourceSpan;
use SmartyAst\Diagnostics\Diagnostic;
use SmartyAst\Diagnostics\Severity;
use SmartyAst\ParseOptions;

final class TemplateLexer
{
    public function __construct(
        private readonly ParseOptions $options,
    ) {
    }

    public function tokenize(string $source): LexResult
    {
        $tokens = [];
        $diagnostics = [];
        $offset = 0;
        $line = 1;
        $column = 1;
        $length = strlen($source);
        $ld = $this->options->leftDelimiter;
        $rd = $this->options->rightDelimiter;

        while ($offset < $length) {
            $tagStart = strpos($source, $ld, $offset);
            if ($tagStart === false) {
                $raw = substr($source, $offset);
                if ($raw !== '') {
                    $span = $this->spanFromRaw($offset, $line, $column, $raw);
                    $tokens[] = new TemplateToken('text', $raw, $raw, $span);
                    [$line, $column] = $this->advance($raw, $line, $column);
                }
                break;
            }

            if ($tagStart > $offset) {
                $raw = substr($source, $offset, $tagStart - $offset);
                $span = $this->spanFromRaw($offset, $line, $column, $raw);
                $tokens[] = new TemplateToken('text', $raw, $raw, $span);
                [$line, $column] = $this->advance($raw, $line, $column);
                $offset = $tagStart;
            }

            if (substr($source, $offset, 2) === $ld . '*') {
                $endPos = strpos($source, '*' . $rd, $offset + 2);
                if ($endPos === false) {
                    $raw = substr($source, $offset);
                    $span = $this->spanFromRaw($offset, $line, $column, $raw);
                    $tokens[] = new TemplateToken('comment', $raw, substr($raw, 2), $span);
                    $diagnostics[] = new Diagnostic(
                        'LEX001',
                        'Unterminated Smarty comment.',
                        Severity::Error,
                        $span,
                        true,
                    );
                    break;
                }

                $raw = substr($source, $offset, $endPos + 2 - $offset);
                $content = substr($raw, 2, -2);
                $span = $this->spanFromRaw($offset, $line, $column, $raw);
                $tokens[] = new TemplateToken('comment', $raw, $content, $span);
                [$line, $column] = $this->advance($raw, $line, $column);
                $offset = $endPos + 2;
                continue;
            }

            $endPos = $this->findTagEnd($source, $offset + strlen($ld), $ld, $rd);
            if ($endPos === null) {
                $raw = substr($source, $offset);
                $span = $this->spanFromRaw($offset, $line, $column, $raw);
                $tokens[] = new TemplateToken('text', $raw, $raw, $span);
                $diagnostics[] = new Diagnostic(
                    'LEX002',
                    'Unterminated Smarty tag.',
                    Severity::Error,
                    $span,
                    true,
                );
                break;
            }

            $raw = substr($source, $offset, $endPos + strlen($rd) - $offset);
            $inner = substr($raw, strlen($ld), -strlen($rd));
            $trimLeft = str_starts_with($inner, '-') && strlen(ltrim($inner, '-')) > 0;
            $trimRight = str_ends_with($inner, '-') && strlen(rtrim($inner, '-')) > 0;
            if ($trimLeft) {
                $inner = substr($inner, 1);
            }
            if ($trimRight) {
                $inner = substr($inner, 0, -1);
            }
            $content = trim($inner);
            $span = $this->spanFromRaw($offset, $line, $column, $raw);

            if ($this->isConfigShorthand($content)) {
                $name = substr($content, 1, -1);
                $tokens[] = new TemplateToken('print', $raw, '$smarty.config.' . $name, $span, $trimLeft, $trimRight);
            } elseif ($this->isPrintExpression($content)) {
                $tokens[] = new TemplateToken('print', $raw, $content, $span, $trimLeft, $trimRight);
            } elseif (str_starts_with($content, '/')) {
                $tokens[] = new TemplateToken('close_tag', $raw, trim(substr($content, 1)), $span, $trimLeft, $trimRight);
            } else {
                $tokens[] = new TemplateToken('tag', $raw, $content, $span, $trimLeft, $trimRight);
            }

            [$line, $column] = $this->advance($raw, $line, $column);
            $offset = $endPos + strlen($rd);
        }

        $eof = new SourceSpan(
            new Position($length, $line, $column),
            new Position($length, $line, $column),
        );
        $tokens[] = new TemplateToken('eof', '', '', $eof);

        return new LexResult($tokens, $diagnostics);
    }

    private function findTagEnd(string $source, int $offset, string $ld, string $rd): ?int
    {
        $inSingle = false;
        $inDouble = false;
        $nestedDepth = 0;
        $length = strlen($source);
        $ldLength = strlen($ld);
        $rdLength = strlen($rd);

        for ($i = $offset; $i < $length; $i++) {
            $char = $source[$i];

            if ($char === '\\') {
                $i++;
                continue;
            }

            if (!$inDouble && $char === "'") {
                $inSingle = !$inSingle;
                continue;
            }

            if (!$inSingle && $char === '"') {
                $inDouble = !$inDouble;
                continue;
            }

            if (!$inSingle && !$inDouble && substr($source, $i, $ldLength) === $ld) {
                $nestedDepth++;
                $i += $ldLength - 1;
                continue;
            }

            if (!$inSingle && !$inDouble && substr($source, $i, $rdLength) === $rd) {
                if ($nestedDepth > 0) {
                    $nestedDepth--;
                    $i += $rdLength - 1;
                    continue;
                }
                return $i;
            }
        }

        return null;
    }

    private function spanFromRaw(int $offset, int $line, int $column, string $raw): SourceSpan
    {
        [$endLine, $endColumn] = $this->advance($raw, $line, $column);

        return new SourceSpan(
            new Position($offset, $line, $column),
            new Position($offset + strlen($raw), $endLine, $endColumn),
        );
    }

    /** @return array{0:int,1:int} */
    private function advance(string $raw, int $line, int $column): array
    {
        $length = strlen($raw);
        for ($i = 0; $i < $length; $i++) {
            if ($raw[$i] === "\n") {
                $line++;
                $column = 1;
            } else {
                $column++;
            }
        }

        return [$line, $column];
    }

    private function isConfigShorthand(string $content): bool
    {
        return preg_match('/^#[A-Za-z_][A-Za-z0-9_]*#$/', $content) === 1;
    }

    private function isPrintExpression(string $content): bool
    {
        if ($content === '') {
            return false;
        }

        $first = $content[0];
        if ($first === '$' || $first === '"' || $first === "'" || $first === '`') {
            return true;
        }

        // identifier( with no intervening space = function-call print, e.g. {count($arr)}
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*\(/', $content) === 1;
    }
}
