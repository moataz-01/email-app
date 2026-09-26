<?php

namespace App\Services\Mail\ConnectionTesting;

final readonly class MailAccountConnectionTestOutcome
{
    public bool $successful;

    public function __construct(
        public ConnectionTestResult $imap,
        public ConnectionTestResult $smtp,
    ) {
        $this->successful = $imap->successful && $smtp->successful;
    }
}
