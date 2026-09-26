<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('upgrading from user ownership preserves cached mail and allows accounts without users', function () {
    $originalConnection = DB::getDefaultConnection();
    config(['database.connections.legacy_upgrade' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
    ]]);
    DB::setDefaultConnection('legacy_upgrade');

    try {
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_09_09_125821_create_mail_cache_tables.php'))->up();
        $userId = DB::table('users')->insertGetId(['name' => 'Legacy', 'email' => 'legacy@example.test', 'password' => 'legacy-hash']);
        $accountId = DB::table('mail_accounts')->insertGetId([
            'user_id' => $userId, 'provider' => 'imap_smtp', 'name' => 'Work', 'email' => 'work@example.test',
        ]);
        $messageId = DB::table('mail_messages')->insertGetId([
            'mail_account_id' => $accountId, 'provider_id' => 'legacy-message', 'received_at' => '2026-09-01 12:00:00',
        ]);
        DB::table('mail_message_bodies')->insert(['mail_message_id' => $messageId, 'text' => 'Keep this cached message.']);

        (require database_path('migrations/2026_09_09_133912_detach_mail_accounts_from_application_users.php'))->up();

        expect(Schema::hasColumn('mail_accounts', 'user_id'))->toBeFalse();
        expect(DB::table('mail_accounts')->where('id', $accountId)->value('legacy_user_id'))->toBe($userId);
        DB::table('users')->where('id', $userId)->delete();
        expect(DB::table('mail_messages')->where('id', $messageId)->value('mail_account_id'))->toBe($accountId);
        expect(DB::table('mail_message_bodies')->value('text'))->toBe('Keep this cached message.');
        DB::table('mail_accounts')->insert(['provider' => 'imap_smtp', 'name' => 'New', 'email' => 'work@example.test']);
        expect(DB::table('mail_accounts')->count())->toBe(2);
    } finally {
        DB::setDefaultConnection($originalConnection);
        DB::purge('legacy_upgrade');
    }
});
