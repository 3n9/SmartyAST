<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class TagNode extends Node implements TagLike
{
    /** @param list<TagArgumentNode> $arguments */
    public function __construct(
        SourceSpan $span,
        public string $name,
        public array $arguments,
        public bool $isShorthand,
        public string $raw,
        public bool $trimLeft = false,
        public bool $trimRight = false,
    ) {
        parent::__construct('Tag', $span);
    }

    public function resolveTag(): TagNode
    {
        return $this;
    }

    public function children(): array
    {
        return $this->arguments;
    }

    /**
     * Finds a named argument by name (case-insensitive), or—when the tag uses
     * shorthand syntax—returns the first positional argument as a fallback.
     */
    public function findArgument(string $name): ?TagArgumentNode
    {
        foreach ($this->arguments as $index => $argument) {
            if ($argument->name !== null && strtolower($argument->name) === strtolower($name)) {
                return $argument;
            }
            if ($this->isShorthand && $index === 0) {
                return $argument;
            }
        }
        return null;
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'name' => $this->name,
            'isShorthand' => $this->isShorthand,
            'trimLeft' => $this->trimLeft,
            'trimRight' => $this->trimRight,
            'raw' => $this->raw,
            'arguments' => array_map(static fn (TagArgumentNode $argument) => $argument->toArray(), $this->arguments),
        ];
    }
}
