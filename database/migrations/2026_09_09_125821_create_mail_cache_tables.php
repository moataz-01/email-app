<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('provider', ['gmail', 'imap_smtp']);
            $table->string('name');
            $table->string('email');
            $table->string('credential_reference')->nullable();
            $table->string('status')->default('not_connected');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'provider', 'email']);
        });

        Schema::create('mailboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_account_id')->constrained()->cascadeOnDelete();
            $table->string('provider_id');
            $table->string('name');
            $table->string('kind')->default('custom');
            $table->timestamps();
            $table->unique(['mail_account_id', 'provider_id']);
            $table->unique(['id', 'mail_account_id']);
            $table->index(['mail_account_id', 'kind']);
        });

        Schema::create('mail_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_account_id')->constrained()->cascadeOnDelete();
            $table->string('provider_id')->nullable();
            $table->string('subject')->default('');
            $table->timestamps();
            $table->unique(['mail_account_id', 'provider_id']);
            $table->unique(['id', 'mail_account_id']);
        });

        Schema::create('mail_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mail_thread_id')->nullable();
            $table->string('provider_id');
            $table->text('message_id_header')->nullable();
            $table->string('subject')->default('');
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('snippet', 500)->default('');
            $table->timestamp('received_at');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->timestamps();
            $table->unique(['mail_account_id', 'provider_id']);
            $table->unique(['id', 'mail_account_id']);
            $table->foreign(['mail_thread_id', 'mail_account_id'])->references(['id', 'mail_account_id'])->on('mail_threads')->cascadeOnDelete();
            $table->index(['mail_account_id', 'received_at', 'id'], 'mail_messages_account_date');
            $table->index(['mail_account_id', 'is_starred', 'received_at', 'id'], 'mail_messages_account_starred_date');
            $table->index(['received_at', 'id']);
        });

        Schema::create('mailbox_messages', function (Blueprint $table) {
            $table->foreignId('mail_account_id');
            $table->foreignId('mailbox_id');
            $table->foreignId('mail_message_id');
            $table->string('uid', 20)->nullable();
            $table->string('uid_validity', 20)->nullable();
            $table->primary(['mailbox_id', 'mail_message_id']);
            $table->unique(['mailbox_id', 'uid_validity', 'uid'], 'mailbox_messages_epoch_uid');
            $table->foreign(['mailbox_id', 'mail_account_id'])->references(['id', 'mail_account_id'])->on('mailboxes')->cascadeOnDelete();
            $table->foreign(['mail_message_id', 'mail_account_id'])->references(['id', 'mail_account_id'])->on('mail_messages')->cascadeOnDelete();
        });

        Schema::create('mail_message_bodies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_message_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('text')->nullable();
            $table->longText('html')->nullable();
            $table->timestamps();
        });

        Schema::create('mail_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_message_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', ['to', 'cc', 'bcc', 'reply_to']);
            $table->string('name')->nullable();
            $table->string('email');
            $table->timestamps();
            $table->index(['mail_message_id', 'kind']);
        });

        Schema::create('mail_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_message_id')->constrained()->cascadeOnDelete();
            $table->string('provider_id');
            $table->text('filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('content_id')->nullable();
            $table->timestamps();
            $table->unique(['mail_message_id', 'provider_id']);
        });

        Schema::create('mail_sync_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mailbox_id')->nullable();
            $table->string('scope');
            $table->text('cursor')->nullable();
            $table->string('uid_validity', 20)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['mail_account_id', 'scope']);
            $table->foreign(['mailbox_id', 'mail_account_id'])->references(['id', 'mail_account_id'])->on('mailboxes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['mail_sync_states', 'mail_attachments', 'mail_recipients', 'mail_message_bodies', 'mailbox_messages', 'mail_messages', 'mail_threads', 'mailboxes', 'mail_accounts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
