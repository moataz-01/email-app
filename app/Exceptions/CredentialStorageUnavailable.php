<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

class CredentialStorageUnavailable extends RuntimeException implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('Secure credential storage is unavailable. Open the Windows desktop app and try again.');
    }
}
