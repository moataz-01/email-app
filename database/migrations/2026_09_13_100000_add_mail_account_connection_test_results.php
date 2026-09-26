<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_accounts', function (Blueprint $table): void {
            $table->string('imap_status')->nullable()->after('imap_username');
            $table->string('smtp_status')->nullable()->after('smtp_username');
            $table->timestamp('last_connection_tested_at')->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('mail_accounts', function (Blueprint $table): void {
            $table->dropColumn(['imap_status', 'smtp_status', 'last_connection_tested_at']);
        });
    }
};
