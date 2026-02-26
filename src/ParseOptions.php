<?php

declare(strict_types=1);

namespace SmartyAst;

use SmartyAst\Comments\CommentParserInterface;
use SmartyAst\Comments\PhpDocTemplateAnnotationParser;

final class ParseOptions
{
    /** @param list<CommentParserInterface> $commentParsers */
    public function __construct(
        public readonly string $leftDelimiter = '{',
        public readonly string $rightDelimiter = '}',
        public readonly bool $recoverErrors = true,
        public readonly bool $collectTokens = false,
        public readonly array $commentParsers = [new PhpDocTemplateAnnotationParser()],
        public readonly string $phpVersion = '8.1',
    ) {
    }
}
