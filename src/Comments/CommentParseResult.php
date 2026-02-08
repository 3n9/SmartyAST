<?php

declare(strict_types=1);

namespace Dev\Smarty\Comments;

use Dev\Smarty\Ast\AnnotationNode;
use Dev\Smarty\Diagnostics\Diagnostic;

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
