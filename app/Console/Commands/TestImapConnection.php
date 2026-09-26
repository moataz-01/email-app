<?php

namespace App\Console\Commands;

use App\Contracts\ImapConnectionTester;
use App\Services\Mail\ConnectionTesting\ImapConnectionSettings;
use App\Services\Mail\ConnectionTesting\MailServerRules;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Throwable;

#[Signature('mail:test-imap {--host= : IMAP hostname} {--port= : Port, defaults to 993 for TLS or 143 for STARTTLS} {--encryption=tls : tls or starttls} {--username= : IMAP username}')]
#[Description('Test a real IMAP login without saving credentials or downloading mail')]
class TestImapConnection extends Command
{
    public function handle(ImapConnectionTester $tester): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Run this command interactively so the password can be entered securely.');

            return self::FAILURE;
        }

        $settings = [
            'imap_host' => $this->option('host') ?? $this->ask('IMAP hostname'),
            'imap_username' => $this->option('username') ?? $this->ask('IMAP username'),
            'imap_encryption' => $this->option('encryption'),
            'imap_port' => $this->option('port') ?? ($this->option('encryption') === 'starttls' ? 143 : 993),
        ];
        $rules = MailServerRules::forProtocol('imap');
        unset($rules['imap_password']);
        $validator = Validator::make($settings, $rules);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        try {
            $password = $this->secret('IMAP password or app password', fallback: false);
        } catch (Throwable) {
            $this->error('Hidden password input is unavailable. Use an interactive terminal.');

            return self::FAILURE;
        }
        if (Validator::make(['imap_password' => $password], ['imap_password' => MailServerRules::forProtocol('imap')['imap_password']])->fails()) {
            $this->error('Enter a nonempty password without control characters, up to 4096 characters.');

            return self::FAILURE;
        }

        $result = $tester->test(new ImapConnectionSettings(
            host: $settings['imap_host'], port: (int) $settings['imap_port'],
            encryption: $settings['imap_encryption'], username: $settings['imap_username'], password: $password,
        ));
        unset($password);
        if (! $result->successful) {
            $this->error($result->summary);

            return self::FAILURE;
        }
        $this->info('IMAP login succeeded. No credentials were saved and no messages were downloaded.');

        return self::SUCCESS;
    }
}
