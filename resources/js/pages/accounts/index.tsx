import { Form, Head, Link } from '@inertiajs/react';
import { ArrowRight, Mail, Plug, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import WorkspaceLayout from '@/layouts/workspace-layout';
import { dashboard } from '@/routes';
import { create, testConnection } from '@/routes/accounts';
import { create as testImap } from '@/routes/connections/imap';
import type { MailAccount } from '@/types/mail';

function connectionStatusLabel(account: MailAccount): string {
    if (account.status === 'demo') {
        return 'Sample account · No provider connected';
    }

    if (account.status === 'connected') {
        return account.last_connection_tested_at
            ? `Connection test passed · ${new Date(account.last_connection_tested_at).toLocaleString()}`
            : 'Connection test passed';
    }

    if (account.status === 'connection_failed') {
        const failedProtocols = [
            account.imap_status !== 'connected' ? 'IMAP' : null,
            account.smtp_status !== 'connected' ? 'SMTP' : null,
        ].filter(Boolean);

        return failedProtocols.length > 0
            ? `Connection failed · ${failedProtocols.join(' & ')}`
            : 'Connection failed';
    }

    return 'Settings saved · Not connected';
}

export default function Accounts({ accounts }: { accounts: MailAccount[] }) {
    return (
        <WorkspaceLayout>
            <Head title="Email accounts" />
            <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        Email accounts
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Your mail, together on this device.
                    </p>
                </div>
                <Button
                    asChild
                    className="bg-teal-800 text-white hover:bg-teal-900"
                >
                    <Link href={create()}>
                        <Plus />
                        Add email account
                    </Link>
                </Button>
            </div>
            <Link
                href={testImap()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium underline"
            >
                <Plug className="size-4" />
                Test IMAP without saving an account
            </Link>
            {accounts.length === 0 ? (
                <div className="bg-background flex flex-col items-center gap-4 rounded-2xl border p-12 text-center">
                    <Mail className="size-10 text-teal-700" />
                    <h2 className="text-lg font-semibold">
                        Start with your first email account
                    </h2>
                    <p className="text-muted-foreground max-w-sm text-sm leading-6">
                        Add your email address and server settings to prepare
                        your local inbox.
                    </p>
                    <Button asChild variant="outline">
                        <Link href={create()}>
                            Add your email
                            <ArrowRight />
                        </Link>
                    </Button>
                </div>
            ) : (
                <div className="grid gap-4">
                    {accounts.map((account) => (
                        <section
                            key={account.id}
                            className="bg-background flex flex-wrap items-center justify-between gap-4 rounded-2xl border p-6 shadow-sm"
                        >
                            <div className="flex min-w-0 items-start gap-4">
                                <div className="rounded-xl bg-teal-50 p-3 text-teal-700 dark:bg-teal-950 dark:text-teal-200">
                                    <Mail className="size-5" />
                                </div>
                                <div className="min-w-0">
                                    <h2 className="truncate font-semibold">
                                        {account.name}
                                    </h2>
                                    <p className="text-muted-foreground truncate text-sm">
                                        {account.email}
                                    </p>
                                    <p className="text-muted-foreground mt-2 text-xs">
                                        {connectionStatusLabel(account)}
                                    </p>
                                </div>
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                {account.status !== 'demo' && (
                                    <Form {...testConnection.form(account.id)}>
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                disabled={processing}
                                            >
                                                {processing ? (
                                                    <Spinner />
                                                ) : (
                                                    <Plug />
                                                )}
                                                {processing
                                                    ? 'Testing…'
                                                    : 'Test connection'}
                                            </Button>
                                        )}
                                    </Form>
                                )}
                                <Button asChild variant="outline">
                                    <Link
                                        href={dashboard({
                                            query: { account: account.id },
                                        })}
                                    >
                                        Open inbox
                                        <ArrowRight />
                                    </Link>
                                </Button>
                            </div>
                        </section>
                    ))}
                </div>
            )}
        </WorkspaceLayout>
    );
}
