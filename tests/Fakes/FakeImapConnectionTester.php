<?php

namespace Tests\Fakes;

use App\Contracts\ImapConnectionTester;
use App\Services\Mail\ConnectionTesting\ConnectionTestResult;
use App\Services\Mail\ConnectionTesting\ImapConnectionSettings;

class FakeImapConnectionTester implements ImapConnectionTester
{
    /** @var list<ImapConnectionSettings> */
    public array $calls = [];

    public function __construct(private ?ConnectionTestResult $result = null) {}

    public function test(ImapConnectionSettings $settings): ConnectionTestResult
    {
        $this->calls[] = $settings;

        return $this->result ??= ConnectionTestResult::success();
    }
}
