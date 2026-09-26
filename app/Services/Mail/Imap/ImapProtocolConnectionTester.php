<?php

namespace App\Services\Mail\Imap;

use App\Contracts\ImapConnectionTester;
use App\Services\Mail\ConnectionTesting\ConnectionFailureReason;
use App\Services\Mail\ConnectionTesting\ConnectionTestResult;
use App\Services\Mail\ConnectionTesting\ImapConnectionSettings;
use App\Services\Mail\Imap\Exceptions\ImapConnectionException;
use Throwable;

class ImapProtocolConnectionTester implements ImapConnectionTester
{
    public function test(ImapConnectionSettings $settings): ConnectionTestResult
    {
        $client = new RawImapClient(new StreamImapSocket);

        try {
            $client->connectAndAuthenticate($settings);

            return ConnectionTestResult::success();
        } catch (ImapConnectionException $exception) {
            return ConnectionTestResult::failure($exception->reason());
        } catch (Throwable) {
            return ConnectionTestResult::failure(ConnectionFailureReason::Unknown);
        }
    }
}
