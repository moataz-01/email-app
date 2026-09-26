<?php

namespace App\Console\Commands;

use Database\Seeders\MailDemoSeeder;
use Illuminate\Console\Command;

class SeedDemoMail extends Command
{
    protected $signature = 'mail:demo';

    protected $description = 'Add an optional local sample mailbox without connecting to a provider';

    public function handle(MailDemoSeeder $seeder): int
    {
        $seeder->run();
        $this->info('Sample mail is available in the inbox. No external account was connected.');

        return self::SUCCESS;
    }
}
