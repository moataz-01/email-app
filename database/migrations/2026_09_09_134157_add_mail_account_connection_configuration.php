<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_accounts', function (Blueprint $table): void {
            foreach (['imap', 'smtp'] as $protocol) {
                $table->string($protocol.'_host')->nullable();
                $table->unsignedSmallInteger($protocol.'_port')->nullable();
                $table->enum($protocol.'_encryption', ['tls', 'starttls'])->nullable();
                $table->string($protocol.'_username')->nullable();
            }
        });
        Schema::create('mail_credentials', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('mail_account_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('ciphertext');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_credentials');
        Schema::table('mail_accounts', function (Blueprint $table): void {
            $table->dropColumn(['imap_host', 'imap_port', 'imap_encryption', 'imap_username', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username']);
        });
    }
};
