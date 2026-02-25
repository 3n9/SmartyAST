<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

interface TagLike
{
    public function resolveTag(): TagNode;
}
