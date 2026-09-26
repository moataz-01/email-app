<?php

namespace App\Http\Controllers;

use App\Actions\Mail\ConfigureMailAccount;
use App\Actions\Mail\TestMailAccountConnection;
use App\Contracts\CredentialCipher;
use App\Exceptions\CredentialStorageUnavailable;
use App\Http\Requests\StoreMailAccountRequest;
use App\Models\MailAccount;
use App\Services\Mail\ConnectionTesting\MailAccountConnectionTestOutcome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MailAccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('accounts/index', [
            'accounts' => MailAccount::query()->orderBy('id')->get([
                'id', 'name', 'email', 'provider', 'status', 'last_synced_at',
                'imap_host', 'imap_port', 'imap_encryption', 'imap_status',
                'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_status',
                'last_connection_tested_at',
            ]),
        ]);
    }

    public function create(CredentialCipher $cipher): Response
    {
        return Inertia::render('accounts/create', [
            'hasAccounts' => MailAccount::query()->exists(),
            'secureStorageAvailable' => $cipher->available(),
        ]);
    }

    public function store(StoreMailAccountRequest $request, ConfigureMailAccount $configure): RedirectResponse
    {
        try {
            $configure->create($request->validated());
        } catch (CredentialStorageUnavailable $exception) {
            throw ValidationException::withMessages(['credentials' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account settings saved. Test the connection to make sure it works.']);

        return to_route('accounts.index');
    }

    public function test(MailAccount $account, TestMailAccountConnection $action): RedirectResponse
    {
        try {
            $outcome = $action->handle($account);
        } catch (CredentialStorageUnavailable $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return to_route('accounts.index');
        }

        Inertia::flash('toast', [
            'type' => $outcome->successful ? 'success' : 'error',
            'message' => $this->connectionTestMessage($outcome),
        ]);

        return to_route('accounts.index');
    }

    private function connectionTestMessage(MailAccountConnectionTestOutcome $outcome): string
    {
        if ($outcome->successful) {
            return 'Connection test succeeded for IMAP and SMTP.';
        }

        $failures = [];
        if (! $outcome->imap->successful) {
            $failures[] = "IMAP: {$outcome->imap->summary}";
        }
        if (! $outcome->smtp->successful) {
            $failures[] = "SMTP: {$outcome->smtp->summary}";
        }

        return 'Connection test failed. '.implode(' ', $failures);
    }
}
