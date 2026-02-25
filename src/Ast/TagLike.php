<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

interface TagLike
{
    public function resolveTag(): TagNode;
}
