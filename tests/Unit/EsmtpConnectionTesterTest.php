<?php

use App\Services\Mail\ConnectionTesting\ConnectionFailureReason;
use App\Services\Mail\ConnectionTesting\SmtpConnectionSettings;
use App\Services\Mail\Smtp\EsmtpConnectionTester;

test('connecting to a closed local port is classified as connection refused', function () {
    $server = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($server, false);
    fclose($server);
    [$host, $port] = explode(':', $address);

    $result = (new EsmtpConnectionTester)->test(new SmtpConnectionSettings(
        host: $host,
        port: (int) $port,
        encryption: 'starttls',
        username: 'alice',
        password: 'secret12',
        timeoutSeconds: 2.0,
    ));

    expect($result->successful)->toBeFalse();
    expect($result->reason)->toBe(ConnectionFailureReason::ConnectionRefused);
});
