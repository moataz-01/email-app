<?php

namespace Tests\Fakes;

use App\Contracts\SmtpConnectionTester;
use App\Services\Mail\ConnectionTesting\ConnectionTestResult;
use App\Services\Mail\ConnectionTesting\SmtpConnectionSettings;

class FakeSmtpConnectionTester implements SmtpConnectionTester
{
    /** @var list<SmtpConnectionSettings> */
    public array $calls = [];

    public function __construct(private ?ConnectionTestResult $result = null) {}

    public function test(SmtpConnectionSettings $settings): ConnectionTestResult
    {
        $this->calls[] = $settings;

        return $this->result ??= ConnectionTestResult::success();
    }
}
