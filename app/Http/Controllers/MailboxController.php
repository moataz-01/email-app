<?php

namespace App\Http\Controllers;

use App\Actions\Mail\ReadMailbox;
use App\Http\Requests\MailboxRequest;
use Inertia\Inertia;
use Inertia\Response;

class MailboxController extends Controller
{
    public function index(MailboxRequest $request, ReadMailbox $mailbox): Response
    {
        return Inertia::render('mail/index', $mailbox->read(
            $request->filled('account') ? $request->integer('account') : null,
            $request->filled('mailbox') ? $request->integer('mailbox') : null,
            $request->filled('message') ? $request->integer('message') : null,
            $request->string('view')->toString() ?: 'inbox',
            trim($request->string('q')->toString()),
            $request->filled('cursor') ? $request->string('cursor')->toString() : null,
        ));
    }
}
