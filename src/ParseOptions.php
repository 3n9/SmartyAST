<?php

declare(strict_types=1);

namespace Dev\Smarty;

use Dev\Smarty\Comments\CommentParserInterface;
use Dev\Smarty\Comments\PhpDocTemplateAnnotationParser;

final class ParseOptions
{
    /** @param list<CommentParserInterface> $commentParsers */
    public function __construct(
        public readonly string $leftDelimiter = '{',
        public readonly string $rightDelimiter = '}',
        public readonly bool $recoverErrors = true,
        public readonly bool $collectTokens = false,
        public readonly array $commentParsers = [new PhpDocTemplateAnnotationParser()],
    ) {
    }
}
