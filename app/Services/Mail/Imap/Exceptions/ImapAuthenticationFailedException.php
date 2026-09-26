<?php

namespace App\Services\Mail\Imap\Exceptions;

use App\Services\Mail\ConnectionTesting\ConnectionFailureReason;

class ImapAuthenticationFailedException extends ImapConnectionException
{
    public function __construct()
    {
        parent::__construct(ConnectionFailureReason::AuthenticationFailed);
    }
}
