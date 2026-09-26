import { Head, Link, useHttp } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Plug, ShieldCheck } from 'lucide-react';
import { useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { ServerSettings } from '@/components/accounts/server-settings';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import WorkspaceLayout from '@/layouts/workspace-layout';
import { create as addAccount } from '@/routes/accounts';
import { store } from '@/routes/connections/imap';

type ImapFields = {
    imap_host: string;
    imap_port: string;
    imap_encryption: string;
    imap_username: string;
    imap_password: string;
};

type ConnectionResult = {
    successful: boolean;
    reason: string | null;
    summary: string;
};

export default function TestImap() {
    const formRef = useRef<HTMLFormElement>(null);
    const [result, setResult] = useState<ConnectionResult | null>(null);
    const [requestError, setRequestError] = useState<string | null>(null);
    const http = useHttp<ImapFields, ConnectionResult>({
        imap_host: '',
        imap_port: '993',
        imap_encryption: 'tls',
        imap_username: '',
        imap_password: '',
    });

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setResult(null);
        setRequestError(null);
        try {
            await http.post(store.url(), {
                onSuccess: (response) => {
                    http.setDefaults('imap_password', '');
                    setResult(response);
                },
                onHttpException: (response) =>
                    setRequestError(
                        response.status === 429
                            ? 'Too many attempts. Wait a minute before trying again.'
                            : 'The test could not finish. Refresh this page and try again.',
                    ),
                onNetworkError: () =>
                    setRequestError(
                        'The app could not be reached. Check that it is still running and try again.',
                    ),
            });
        } catch {
            // The request callbacks show safe errors; never display raw transport data.
        } finally {
            http.setData('imap_password', '');
            const password =
                formRef.current?.elements.namedItem('imap_password');
            if (password instanceof HTMLInputElement) password.value = '';
        }
    }

    return (
        <WorkspaceLayout>
            <Head title="Test IMAP connection" />
            <Link
                href={addAccount()}
                className="text-muted-foreground mb-6 inline-flex items-center gap-2 text-sm"
            >
                <ArrowLeft className="size-4" />
                Add an email account
            </Link>
            <h1 className="text-3xl font-semibold tracking-tight">
                Test IMAP connection
            </h1>
            <p className="text-muted-foreground mt-3 mb-6 text-sm leading-6">
                Check that your incoming mail server accepts your username and
                password. SMTP settings are not needed for this test.
            </p>
            <form
                ref={formRef}
                onSubmit={submit}
                onChange={(event) => {
                    const target = event.target;
                    if (
                        target instanceof HTMLInputElement ||
                        target instanceof HTMLSelectElement
                    ) {
                        http.setData(
                            target.name as keyof ImapFields,
                            target.value,
                        );
                        setResult(null);
                    }
                }}
                className="bg-background grid gap-6 rounded-2xl border p-6 shadow-sm"
            >
                <fieldset disabled={http.processing} className="min-w-0">
                    <ServerSettings
                        protocol="imap"
                        errors={http.errors}
                        passwordEntryAllowed
                    />
                </fieldset>
                <p className="text-muted-foreground flex gap-2 text-xs leading-5">
                    <ShieldCheck className="size-4 shrink-0" />
                    This test checks a real server over a secure connection.
                    Your password is used for this attempt only and cleared
                    afterward. No account is saved or mail downloaded.
                </p>
                <p className="text-muted-foreground text-xs leading-5">
                    Use the settings supplied by your email provider. If it
                    requires an app password, enter that here. Accounts
                    requiring OAuth sign-in are not supported by this test.
                </p>
                <Button
                    type="submit"
                    disabled={http.processing}
                    className="justify-self-start bg-teal-800 text-white hover:bg-teal-900"
                >
                    {http.processing ? <Spinner /> : <Plug />}
                    {http.processing
                        ? 'Checking secure connection and login…'
                        : 'Test IMAP connection'}
                </Button>
                {requestError && (
                    <p role="alert" className="text-destructive text-sm">
                        {requestError}
                    </p>
                )}
                {Object.keys(http.errors).length > 0 && (
                    <p role="alert" className="text-destructive text-sm">
                        Check the highlighted settings and re-enter your
                        password.
                    </p>
                )}
                {result && (
                    <div role="status" className="rounded-xl border p-4">
                        <h2 className="flex items-center gap-2 font-semibold">
                            {result.successful && (
                                <CheckCircle2 className="size-5 text-teal-600" />
                            )}
                            {result.successful
                                ? 'IMAP login succeeded'
                                : 'Could not connect'}
                        </h2>
                        <p className="mt-2 text-sm leading-6">
                            {result.successful
                                ? 'Your server accepted the login. You can now add this account using the same settings. Mail synchronization is not available yet.'
                                : result.summary}
                        </p>
                        {!result.successful && (
                            <p className="text-muted-foreground mt-2 text-xs">
                                Correct the settings, re-enter your password,
                                and try again.
                            </p>
                        )}
                        {result.successful && (
                            <Link
                                href={addAccount()}
                                className="mt-3 inline-block text-sm font-medium underline"
                            >
                                Add this email account
                            </Link>
                        )}
                    </div>
                )}
            </form>
        </WorkspaceLayout>
    );
}
