<?php

namespace App\Http\Controllers;

use App\Contracts\ImapConnectionTester;
use App\Http\Requests\TestImapConnectionRequest;
use App\Services\Mail\ConnectionTesting\ImapConnectionSettings;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ImapConnectionTestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('accounts/test-imap');
    }

    public function store(TestImapConnectionRequest $request, ImapConnectionTester $tester): JsonResponse
    {
        $result = $tester->test(new ImapConnectionSettings(
            host: $request->validated('imap_host'),
            port: (int) $request->validated('imap_port'),
            encryption: $request->validated('imap_encryption'),
            username: $request->validated('imap_username'),
            password: $request->validated('imap_password'),
        ));

        return response()->json([
            'successful' => $result->successful,
            'reason' => $result->reason?->value,
            'summary' => $result->summary,
        ])->header('Cache-Control', 'no-store, private');
    }
}
