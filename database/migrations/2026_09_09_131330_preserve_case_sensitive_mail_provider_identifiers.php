<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (['mailboxes', 'mail_threads', 'mail_messages', 'mail_attachments'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name): void {
                $table->binary('provider_id', length: 1020)
                    ->nullable($name === 'mail_threads')->change();
            });
        }
    }

    public function down(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (['mailboxes', 'mail_threads', 'mail_messages', 'mail_attachments'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name): void {
                $table->string('provider_id')->collation('utf8mb4_unicode_ci')
                    ->nullable($name === 'mail_threads')->change();
            });
        }
    }
};
