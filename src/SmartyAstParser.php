<?php

declare(strict_types=1);

namespace Dev\Smarty;

use Dev\Smarty\Parser\SmartyParser;

final class SmartyAstParser
{
    private SmartyParser $parser;

    public function __construct(?SmartyParser $parser = null)
    {
        $this->parser = $parser ?? new SmartyParser();
    }

    public function parseString(string $source, ?ParseOptions $options = null): ParseResult
    {
        return $this->parser->parseString($source, $options);
    }

    public function parseFile(string $path, ?ParseOptions $options = null): ParseResult
    {
        return $this->parser->parseFile($path, $options);
    }
}
