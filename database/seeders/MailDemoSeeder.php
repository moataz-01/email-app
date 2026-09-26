<?php

namespace Database\Seeders;

use App\Models\MailAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MailDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (['Studio' => 'studio@example.test', 'Personal' => 'personal@example.test'] as $name => $email) {
                $account = MailAccount::query()->firstOrCreate([
                    'provider' => 'imap_smtp', 'email' => $email,
                ], ['name' => $name, 'status' => 'demo']);
                if (! $account->wasRecentlyCreated) {
                    continue;
                }

                $inbox = $account->mailboxes()->create(['provider_id' => 'INBOX', 'name' => 'Inbox', 'kind' => 'inbox']);
                foreach (['Sent' => 'sent', 'Drafts' => 'drafts', 'Archive' => 'archive', 'Spam' => 'spam', 'Trash' => 'trash', 'Projects' => 'custom'] as $folder => $kind) {
                    $account->mailboxes()->create(['provider_id' => $folder, 'name' => $folder, 'kind' => $kind]);
                }

                $samples = [
                    ['Maya Chen', 'A little space for your next big idea', 'The updated project brief is ready. I kept the direction simple: thoughtful details, a little more breathing room, and a clear next step.\n\nLet me know what you think when you have a moment.\n\nMaya'],
                    ['Oliver Grant', 'Friday plans — coffee and a catch-up?', 'How does Friday morning look for you? There is a new coffee place around the corner.\n\nIt would be good to catch up.\n\nOliver'],
                    ['Design Weekly', 'This week: make room for the important things', 'A few ideas worth saving for later.\n\n1. Start with the essentials.\n2. Make every interaction feel considered.\n3. Leave room to think.\n\nThanks for reading.'],
                    ['Nora Williams', 'Notes from our conversation', 'Thanks for the thoughtful conversation today. I have attached the notes for reference.\n\nNext steps:\nReview the brief, gather feedback, and reconnect next week.\n\nNora'],
                ];
                foreach ($samples as $index => [$sender, $subject, $body]) {
                    $body = str_replace('\\n', "\n", $body);
                    $message = $account->messages()->create([
                        'provider_id' => 'sample-'.($index + 1),
                        'subject' => $subject, 'from_name' => $sender,
                        'from_email' => 'sender'.($index + 1).'@example.test',
                        'snippet' => mb_substr(str_replace("\n", ' ', $body), 0, 160),
                        'received_at' => now()->subHours($index * 5),
                        'is_read' => $index > 1, 'is_starred' => $index === 0,
                    ]);
                    $inbox->messages()->attach($message, ['mail_account_id' => $account->id]);
                    $message->body()->create(['text' => $body]);
                    $message->recipients()->create(['kind' => 'to', 'name' => $name, 'email' => $email]);
                    if ($index === 3) {
                        $message->attachments()->create(['provider_id' => 'notes', 'filename' => 'Conversation notes.pdf', 'mime_type' => 'application/pdf', 'size' => 48128]);
                    }
                }
            }
        });
    }
}
