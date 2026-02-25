<?php

declare(strict_types=1);

namespace SmartyAst\Comments;

use SmartyAst\Ast\AnnotationNode;
use SmartyAst\Diagnostics\Diagnostic;

final class CommentParseResult
{
    /** @param list<AnnotationNode> $annotations
     *  @param list<Diagnostic> $diagnostics
     */
    public function __construct(
        public readonly array $annotations = [],
        public readonly array $diagnostics = [],
    ) {
    }
}
