<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mailbox_messages', function (Blueprint $table) {
            $table->index(['mail_message_id', 'mailbox_id'], 'mailbox_messages_message_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mailbox_messages', function (Blueprint $table) {
            $table->dropIndex('mailbox_messages_message_lookup');
        });
    }
};
