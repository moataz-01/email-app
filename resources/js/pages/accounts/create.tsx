import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Mail, ShieldCheck } from 'lucide-react';
import { ServerSettings } from '@/components/accounts/server-settings';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import WorkspaceLayout from '@/layouts/workspace-layout';
import { index, store } from '@/routes/accounts';
import { create as testImap } from '@/routes/connections/imap';

export default function CreateAccount({
    hasAccounts,
    secureStorageAvailable,
}: {
    hasAccounts: boolean;
    secureStorageAvailable: boolean;
}) {
    return (
        <WorkspaceLayout>
            <Head title="Add your email" />
            {hasAccounts && (
                <Link
                    href={index()}
                    className="text-muted-foreground hover:text-foreground mb-6 inline-flex items-center gap-2 text-sm"
                >
                    <ArrowLeft className="size-4" />
                    Back to accounts
                </Link>
            )}
            <div className="mb-8">
                <span className="mb-4 inline-flex rounded-full bg-teal-100 px-3 py-1 text-xs font-medium text-teal-900 dark:bg-teal-950 dark:text-teal-200">
                    {hasAccounts
                        ? 'One workspace for all your mail'
                        : 'Welcome to Letter'}
                </span>
                <h1 className="text-3xl font-semibold tracking-tight">
                    Add your email
                </h1>
                <p className="text-muted-foreground mt-3 max-w-xl text-sm leading-6">
                    Bring your inbox to this device. Use the server settings
                    provided by your email service to get started.
                </p>
            </div>
            <div className="mb-6 grid gap-3 sm:grid-cols-2">
                <div className="bg-background rounded-xl border-2 border-teal-600 p-4">
                    <Mail className="mb-3 size-5 text-teal-700 dark:text-teal-300" />
                    <h2 className="text-sm font-semibold">IMAP & SMTP</h2>
                    <p className="text-muted-foreground mt-1 text-xs leading-5">
                        Work, personal, or your own email server.
                    </p>
                </div>
                <div className="bg-background/50 rounded-xl border p-4">
                    <span className="mb-3 block text-sm font-semibold">
                        Gmail
                    </span>
                    <p className="text-muted-foreground text-xs leading-5">
                        Google sign-in is not available yet. Your Google
                        password is never needed.
                    </p>
                </div>
            </div>
            <Button asChild variant="outline" className="mb-6">
                <Link href={testImap()}>Test IMAP connection first</Link>
            </Button>
            {!secureStorageAvailable && (
                <div
                    role="status"
                    className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100"
                >
                    Open Letter for Windows to save your email passwords
                    securely. You can still test an IMAP connection without
                    saving credentials using the button above.
                </div>
            )}
            <Form
                {...store.form()}
                resetOnSuccess
                resetOnError={['imap_password', 'smtp_password']}
                className="bg-background grid gap-6 rounded-2xl border p-6 shadow-sm"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Account name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    placeholder="Personal or Work"
                                    maxLength={255}
                                    required
                                    aria-invalid={!!errors.name}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    placeholder="you@example.com"
                                    maxLength={255}
                                    autoComplete="email"
                                    required
                                    aria-invalid={!!errors.email}
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>
                        <ServerSettings
                            protocol="imap"
                            errors={errors}
                            passwordEntryAllowed={secureStorageAvailable}
                        />
                        <ServerSettings
                            protocol="smtp"
                            errors={errors}
                            passwordEntryAllowed={secureStorageAvailable}
                        />
                        <InputError message={errors.credentials} />
                        <div className="flex flex-wrap items-center justify-between gap-4 border-t pt-5">
                            <p className="text-muted-foreground flex max-w-sm items-start gap-2 text-xs leading-5">
                                <ShieldCheck className="mt-0.5 size-4 shrink-0" />
                                Passwords stay protected on this device. Saving
                                settings lets you test IMAP and SMTP from
                                Accounts. Mail synchronization is not available
                                yet.
                            </p>
                            <Button
                                type="submit"
                                disabled={processing || !secureStorageAvailable}
                                className="bg-teal-800 text-white hover:bg-teal-900"
                            >
                                {processing ? 'Saving…' : 'Save account'}
                                <ArrowRight className="size-4" />
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </WorkspaceLayout>
    );
}
