<?php

namespace App\Contracts;

use App\Services\Mail\ConnectionTesting\ConnectionTestResult;
use App\Services\Mail\ConnectionTesting\ImapConnectionSettings;

interface ImapConnectionTester
{
    public function test(ImapConnectionSettings $settings): ConnectionTestResult;
}
