<?php

/** A disposable local IMAP server for opt-in socket tests; all credentials are synthetic. */
$context = stream_context_create(['ssl' => ['local_cert' => $argv[1], 'local_pk' => $argv[2]]]);
$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
if ($server === false) {
    exit(2);
}
fwrite(STDOUT, stream_socket_get_name($server, false)."\n");
fflush(STDOUT);
$client = stream_socket_accept($server, 5);
if ($client === false) {
    exit(3);
}
stream_set_timeout($client, 2);
$mode = $argv[3];

if ($mode === 'starttls') {
    fwrite($client, "* OK Test server\r\n");
    fgets($client);
    fwrite($client, "* CAPABILITY IMAP4rev1 STARTTLS\r\na1 OK done\r\n");
    fgets($client);
    fwrite($client, "a2 OK start TLS\r\n");
}
if (@stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_TLS_SERVER) !== true) {
    fclose($client);
    fclose($server);
    exit(0);
}
if ($mode === 'oversized') {
    @fwrite($client, '* OK '.str_repeat('x', 9000)."\r\n");
} elseif ($mode === 'truncated') {
    fwrite($client, '* OK incomplete');
} elseif ($mode === 'slow') {
    fwrite($client, "* OK Ready\r\n");
    fgets($client);
    for ($i = 0; $i < 10; $i++) {
        if (@fwrite($client, "* OK wait\r\n") === false) {
            break;
        }
        usleep(50_000);
    }
} else {
    if ($mode !== 'starttls') {
        fwrite($client, "* OK Test server\r\n");
    }
    $line = fgets($client);
    $tag = explode(' ', $line ?: '')[0];
    fwrite($client, "* CAPABILITY IMAP4rev1 AUTH=PLAIN\r\n{$tag} OK done\r\n");
    $line = fgets($client);
    $tag = explode(' ', $line ?: '')[0];
    if (trim($line ?: '') !== "{$tag} AUTHENTICATE PLAIN") {
        exit(4);
    }
    fwrite($client, "+ \r\n");
    $credentials = base64_decode(trim(fgets($client) ?: ''), true);
    if ($credentials !== "\0test-user\0test-password") {
        fwrite($client, "{$tag} NO rejected\r\n");
    } else {
        fwrite($client, "{$tag} OK authenticated\r\n");
        $tag = explode(' ', fgets($client) ?: '')[0];
        @fwrite($client, "* BYE closing\r\n{$tag} OK logged out\r\n");
    }
}
fclose($client);
fclose($server);
