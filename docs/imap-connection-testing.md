# Test your IMAP account on Windows

From the project directory in your Windows terminal:

```powershell
npm run build
php artisan native:migrate --no-interaction
php artisan native:run
```

Use the existing Windows project installation and its configured NativePHP runtime. These commands do not reinitialize the project. The migration command applies pending migrations to the NativePHP development database without clearing existing mail.

1. On **Add your email**, select **Test IMAP connection first**. If accounts already exist, open **Accounts → Test IMAP without saving an account**.
2. Enter your provider's IMAP hostname, port, connection security, username, and password or app password. Follow the provider's settings; typical ports are **993 with SSL/TLS** or **143 with STARTTLS**. SMTP details are not needed for this test.
3. Click **Test IMAP connection**. The app opens a real encrypted socket and attempts authentication. It does not save the password, create an account, send mail, or download messages. The password field clears after every attempt.
4. **IMAP login succeeded** means the provider accepted that login. To configure a saved account, return to **Add your email**, enter IMAP and SMTP settings, and save. Windows secure storage must be available. You can then test both protocols from **Accounts → Test connection**.

The test supports password/app-password authentication via SASL PLAIN or IMAP LOGIN, always inside verified TLS. OAuth-only accounts need the later OAuth sign-in feature. A successful test does not populate the inbox; mailbox discovery and email synchronization are still pending.

## Understanding failures

| Result                                           | Check                                                                                                                                           |
| ------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| Username or password rejected                    | Correct username, provider-required app password, and whether IMAP access is enabled.                                                           |
| Authentication method unsupported                | The server may require OAuth sign-in; entering a different normal password will not add OAuth support.                                          |
| Secure connection could not be established       | Hostname, encryption/port pair, certificate validity and trust. Keep certificate verification enabled. NativePHP supplies its CA bundle to PHP. |
| Server could not be reached / connection refused | Hostname, port, internet connectivity and firewall policy.                                                                                      |
| Server did not respond in time                   | Retry once connectivity returns. Socket operations have a 20-second overall budget; DNS lookup additionally depends on the OS resolver.         |
| Too many attempts                                | Wait a minute before retrying; the diagnostic endpoint allows six attempts per minute.                                                          |

## Optional terminal test

The same IMAP tester is available without a saved account:

```powershell
php artisan mail:test-imap --host=imap.example.com --port=993 --encryption=tls --username=you@example.com
```

Replace the example host and username. Enter your password only at the hidden prompt, never in shell arguments, environment variables, or chat. The command returns exit code 0 on success and 1 on failure; it creates no account. Use `--encryption=starttls --port=143` if your provider specifies STARTTLS.

## Verification boundaries

Automated feature/protocol tests substitute fakes. Opt-in socket tests run a disposable local TLS server with generated test certificates and synthetic credentials, verifying both TLS modes, certificate/hostname rejection, bad-password handling, bounded responses and timeout behavior. No automated test uses an external email account.

Run them in PowerShell with:

```powershell
$env:RUN_IMAP_SOCKET_TESTS = '1'
php artisan test --compact
Remove-Item Env:RUN_IMAP_SOCKET_TESTS
```

The development environment used for implementation is Linux. Windows native-window behavior, Windows credential persistence after restart, and your provider's authenticated round trip must still be checked on your machine.
