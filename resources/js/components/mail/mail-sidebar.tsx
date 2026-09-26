import { Link, router } from '@inertiajs/react';
import {
    Archive,
    File,
    Folder,
    Inbox,
    Layers,
    Mail,
    Send,
    ShieldAlert,
    Star,
    Trash2,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { create as addAccount } from '@/routes/accounts';
import type { MailAccount, Mailbox, MailFilters } from '@/types/mail';

const folderIcons: Record<string, typeof Inbox> = {
    inbox: Inbox,
    sent: Send,
    drafts: File,
    archive: Archive,
    trash: Trash2,
    spam: ShieldAlert,
};

export function MailSidebar({
    accounts,
    mailboxes,
    filters,
}: {
    accounts: MailAccount[];
    mailboxes: Mailbox[];
    filters: MailFilters;
}) {
    return (
        <div className="flex h-full flex-col gap-6 py-3">
            <div className="px-4">
                <label
                    htmlFor="mail-account"
                    className="text-muted-foreground mb-2 block text-xs font-medium"
                >
                    Your workspace
                </label>
                <select
                    id="mail-account"
                    value={filters.account ?? ''}
                    onChange={(event) =>
                        router.visit(
                            dashboard({
                                query: { account: event.target.value || null },
                            }),
                        )
                    }
                    className="border-border/60 bg-background focus-visible:ring-ring w-full rounded-xl border px-3 py-2.5 text-sm focus-visible:ring-2"
                >
                    <option value="">All accounts</option>
                    {accounts.map((account) => (
                        <option key={account.id} value={account.id}>
                            {account.email}
                        </option>
                    ))}
                </select>
            </div>
            <Link
                href={addAccount()}
                className="text-muted-foreground hover:bg-accent mx-4 rounded-lg border border-dashed px-3 py-2 text-center text-xs"
            >
                + Add email account
            </Link>
            <nav
                aria-label="Mail navigation"
                className="flex flex-col gap-1 pr-3"
            >
                {(
                    [
                        { view: 'inbox', label: 'Inbox', icon: Inbox },
                        { view: 'starred', label: 'Starred', icon: Star },
                        { view: 'all', label: 'All mail', icon: Layers },
                    ] as const
                ).map(({ view, label, icon: Icon }) => (
                    <Link
                        key={view}
                        href={dashboard({
                            query: { account: filters.account, view },
                        })}
                        preserveState
                        aria-current={
                            !filters.mailbox && filters.view === view
                                ? 'page'
                                : undefined
                        }
                        className={cn(
                            'hover:bg-accent focus-visible:ring-ring flex items-center gap-4 rounded-r-full px-6 py-2.5 text-sm transition-colors focus-visible:ring-2',
                            !filters.mailbox &&
                                filters.view === view &&
                                'bg-teal-100 font-semibold text-teal-950 dark:bg-teal-950 dark:text-teal-100',
                        )}
                    >
                        <Icon className="size-4" />
                        {label}
                    </Link>
                ))}
            </nav>
            <div className="min-h-0 flex-1 overflow-y-auto">
                <h2 className="text-muted-foreground px-6 pb-3 text-xs font-semibold tracking-wide">
                    FOLDERS & LABELS
                </h2>
                {mailboxes.length === 0 ? (
                    <p className="text-muted-foreground px-6 text-xs leading-5">
                        Your folders will appear here when an account is
                        connected.
                    </p>
                ) : (
                    <nav
                        aria-label="Folders and labels"
                        className="flex flex-col gap-1 pr-3"
                    >
                        {mailboxes.map((mailbox) => {
                            const Icon = folderIcons[mailbox.kind] ?? Folder;
                            const account = accounts.find(
                                (item) => item.id === mailbox.mail_account_id,
                            );
                            return (
                                <Link
                                    key={mailbox.id}
                                    href={dashboard({
                                        query: {
                                            account: filters.account,
                                            mailbox: mailbox.id,
                                        },
                                    })}
                                    preserveState
                                    aria-current={
                                        filters.mailbox === mailbox.id
                                            ? 'page'
                                            : undefined
                                    }
                                    className={cn(
                                        'hover:bg-accent focus-visible:ring-ring flex items-center gap-4 rounded-r-full px-6 py-2 text-sm focus-visible:ring-2',
                                        filters.mailbox === mailbox.id &&
                                            'bg-teal-100 font-semibold text-teal-950 dark:bg-teal-950 dark:text-teal-100',
                                    )}
                                >
                                    <Icon className="size-4 shrink-0" />
                                    <span className="min-w-0">
                                        <span className="block truncate">
                                            {mailbox.name}
                                        </span>
                                        {!filters.account && (
                                            <span className="text-muted-foreground block truncate text-[11px] font-normal">
                                                {account?.email}
                                            </span>
                                        )}
                                    </span>
                                </Link>
                            );
                        })}
                    </nav>
                )}
            </div>
            <div className="border-border/60 text-muted-foreground mx-4 flex items-start gap-3 rounded-xl border p-3 text-xs">
                <Mail className="mt-0.5 size-4 shrink-0" />
                <p className="leading-5">
                    Cached mail is available offline.
                    <br />
                    {accounts.length}{' '}
                    {accounts.length === 1 ? 'account' : 'accounts'} in this
                    workspace.
                </p>
            </div>
        </div>
    );
}
