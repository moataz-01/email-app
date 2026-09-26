<?php

namespace App\Actions\Mail;

use App\Contracts\ImapConnectionTester;
use App\Contracts\SmtpConnectionTester;
use App\Models\MailAccount;
use App\Services\Credentials\CredentialStore;
use App\Services\Mail\ConnectionTesting\ImapConnectionSettings;
use App\Services\Mail\ConnectionTesting\MailAccountConnectionTestOutcome;
use App\Services\Mail\ConnectionTesting\SmtpConnectionSettings;

class TestMailAccountConnection
{
    public function __construct(
        private CredentialStore $credentials,
        private ImapConnectionTester $imap,
        private SmtpConnectionTester $smtp,
    ) {}

    public function handle(MailAccount $account): MailAccountConnectionTestOutcome
    {
        $secrets = $this->credentials->get($account);

        $imapResult = $this->imap->test(new ImapConnectionSettings(
            host: $account->imap_host,
            port: $account->imap_port,
            encryption: $account->imap_encryption,
            username: $account->imap_username,
            password: $secrets['imap_password'],
        ));

        $smtpResult = $this->smtp->test(new SmtpConnectionSettings(
            host: $account->smtp_host,
            port: $account->smtp_port,
            encryption: $account->smtp_encryption,
            username: $account->smtp_username,
            password: $secrets['smtp_password'],
        ));

        $account->update([
            'imap_status' => $imapResult->successful ? 'connected' : 'failed',
            'smtp_status' => $smtpResult->successful ? 'connected' : 'failed',
            'status' => ($imapResult->successful && $smtpResult->successful) ? 'connected' : 'connection_failed',
            'last_connection_tested_at' => now(),
        ]);

        return new MailAccountConnectionTestOutcome($imapResult, $smtpResult);
    }
}
