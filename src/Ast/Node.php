<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

abstract class Node
{
    public function __construct(
        public readonly string $kind,
        public readonly SourceSpan $span,
    ) {
    }

    /**
     * @return list<Node>
     */
    public function children(): array
    {
        return [];
    }

    /**
     * @return array<string,mixed>
     */
    abstract public function toArray(): array;

    public function toJson(int $flags = 0): string
    {
        return (string) json_encode($this->toArray(), $flags);
    }
}
