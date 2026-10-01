<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class SourceSpan
{
    public function __construct(
        public readonly Position $start,
        public readonly Position $end,
    ) {
    }

    /** Locate a byte range within source beginning at this span's start. */
    public function slice(string $source, int $offset, ?int $length = null): self
    {
        $position = function (int $at) use ($source): Position {
            $prefix = substr($source, 0, $at);
            $lines = substr_count($prefix, "\n");
            $lastNewline = strrpos($prefix, "\n");
            return new Position(
                $this->start->offset + $at,
                $this->start->line + $lines,
                $lastNewline === false ? $this->start->column + $at : $at - $lastNewline,
            );
        };

        return new self($position($offset), $position($offset + ($length ?? strlen($source) - $offset)));
    }
}
