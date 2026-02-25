<?php

declare(strict_types=1);

namespace SmartyAst\Comments;

use SmartyAst\Ast\CommentNode;

interface CommentParserInterface
{
    public function parse(CommentNode $comment, CommentParseContext $context): CommentParseResult;
}
