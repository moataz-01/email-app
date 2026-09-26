<?php

namespace App\Services\Mail\Imap\Exceptions;

use App\Services\Mail\ConnectionTesting\ConnectionFailureReason;
use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

abstract class ImapConnectionException extends RuntimeException implements ShouldntReport
{
    public function __construct(private readonly ConnectionFailureReason $failureReason)
    {
        parent::__construct($failureReason->summary());
    }

    public function reason(): ConnectionFailureReason
    {
        return $this->failureReason;
    }
}
