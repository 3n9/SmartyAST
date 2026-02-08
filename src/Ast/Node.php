<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

abstract class Node
{
    public function __construct(
        public readonly string $kind,
        public readonly SourceSpan $span,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    abstract public function toArray(): array;
}
