<?php

namespace App\Contracts;

use App\Services\Mail\ConnectionTesting\ConnectionTestResult;
use App\Services\Mail\ConnectionTesting\SmtpConnectionSettings;

interface SmtpConnectionTester
{
    public function test(SmtpConnectionSettings $settings): ConnectionTestResult;
}
