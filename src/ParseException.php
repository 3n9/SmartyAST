<?php

declare(strict_types=1);

namespace SmartyAst;

use SmartyAst\Diagnostics\Diagnostic;

/** Raised for the first error diagnostic when recovery is disabled. */
final class ParseException extends \RuntimeException
{
    public function __construct(public readonly Diagnostic $diagnostic)
    {
        parent::__construct(sprintf('[%s] %s at %d:%d', $diagnostic->code, $diagnostic->message, $diagnostic->span->start->line, $diagnostic->span->start->column));
    }
}
