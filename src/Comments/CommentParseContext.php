<?php

declare(strict_types=1);

namespace SmartyAst\Comments;

use SmartyAst\ParseOptions;

final class CommentParseContext
{
    public function __construct(
        public readonly ParseOptions $options,
    ) {
    }
}
