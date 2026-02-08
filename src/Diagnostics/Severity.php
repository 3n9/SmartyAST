<?php

declare(strict_types=1);

namespace Dev\Smarty\Diagnostics;

enum Severity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
