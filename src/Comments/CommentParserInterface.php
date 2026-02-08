<?php

declare(strict_types=1);

namespace Dev\Smarty\Comments;

use Dev\Smarty\Ast\CommentNode;

interface CommentParserInterface
{
    public function parse(CommentNode $comment, CommentParseContext $context): CommentParseResult;
}
