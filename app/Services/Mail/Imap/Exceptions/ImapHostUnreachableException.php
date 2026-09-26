<?php

namespace App\Services\Mail\Imap\Exceptions;

use App\Services\Mail\ConnectionTesting\ConnectionFailureReason;

class ImapHostUnreachableException extends ImapConnectionException
{
    public function __construct()
    {
        parent::__construct(ConnectionFailureReason::HostUnreachable);
    }
}
