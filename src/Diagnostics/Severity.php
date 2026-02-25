<?php

declare(strict_types=1);

namespace SmartyAst\Diagnostics;

enum Severity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
