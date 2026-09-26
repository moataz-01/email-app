<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_accounts', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'provider', 'email']);
        });
        Schema::table('mail_accounts', function (Blueprint $table): void {
            $table->renameColumn('user_id', 'legacy_user_id');
        });
        Schema::table('mail_accounts', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Local workspace ownership cannot be safely reversed. Restore a pre-migration backup to restore application-user ownership.');
    }
};
