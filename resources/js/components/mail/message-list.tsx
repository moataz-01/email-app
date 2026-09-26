import { Link } from '@inertiajs/react';
import { Inbox, Paperclip, Star } from 'lucide-react';
import { useState } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { create as addAccount } from '@/routes/accounts';
import type { MailFilters, MailMessage } from '@/types/mail';

export function MessageList({
    messages,
    filters,
    selectedId,
    hasAccounts,
}: {
    messages: MailMessage[];
    filters: MailFilters;
    selectedId?: number;
    hasAccounts: boolean;
}) {
    const [selected, setSelected] = useState<number[]>([]);
    if (messages.length === 0) {
        return (
            <div className="flex min-h-80 flex-1 flex-col items-center justify-center gap-4 px-8 py-16 text-center">
                <div className="flex size-16 items-center justify-center rounded-2xl bg-teal-50 text-teal-700 dark:bg-teal-950 dark:text-teal-300">
                    <Inbox className="size-7" />
                </div>
                <div>
                    <h2 className="text-lg font-semibold">
                        {!hasAccounts
                            ? 'A quieter place for your mail'
                            : filters.q
                              ? 'No matching messages'
                              : 'Nothing here yet'}
                    </h2>
                    <p className="text-muted-foreground mt-2 max-w-sm text-sm leading-6">
                        {!hasAccounts
                            ? 'Add your email account to get started.'
                            : filters.q
                              ? 'Try a different sender or subject, or choose another folder.'
                              : 'Messages cached in this folder will appear here.'}
                    </p>
                </div>
                {!hasAccounts && (
                    <Link
                        href={addAccount()}
                        className="rounded-lg bg-teal-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-teal-900"
                    >
                        Add your email
                    </Link>
                )}
            </div>
        );
    }
    return (
        <div className="min-h-0 flex-1 overflow-y-auto">
            <div className="text-muted-foreground flex h-10 items-center gap-4 border-b px-4 text-xs">
                <Checkbox
                    aria-label="Select all messages on this page"
                    checked={
                        selected.length === messages.length
                            ? true
                            : selected.length > 0
                              ? 'indeterminate'
                              : false
                    }
                    onCheckedChange={(checked) =>
                        setSelected(
                            checked
                                ? messages.map((message) => message.id)
                                : [],
                        )
                    }
                />
                <span role="status">
                    {selected.length > 0
                        ? `${selected.length} selected · Bulk actions coming with synchronization`
                        : 'Newest first'}
                </span>
            </div>
            <ul aria-label="Messages" className="divide-border/60 divide-y">
                {messages.map((message) => (
                    <li
                        key={message.id}
                        className={cn(
                            'group hover:bg-accent/70 flex items-center gap-3 px-4 transition-colors',
                            message.is_read ? 'bg-muted/30' : 'bg-background',
                            (selected.includes(message.id) ||
                                selectedId === message.id) &&
                                'bg-teal-50 dark:bg-teal-950/50',
                        )}
                    >
                        <Checkbox
                            aria-label={`Select ${message.subject || 'message without a subject'}`}
                            checked={selected.includes(message.id)}
                            onCheckedChange={(checked) =>
                                setSelected((current) =>
                                    checked
                                        ? [...current, message.id]
                                        : current.filter(
                                              (id) => id !== message.id,
                                          ),
                                )
                            }
                        />
                        <span
                            aria-label={
                                message.is_starred ? 'Starred' : 'Not starred'
                            }
                        >
                            <Star
                                className={cn(
                                    'text-muted-foreground/50 size-4',
                                    message.is_starred &&
                                        'fill-amber-400 text-amber-500',
                                )}
                            />
                        </span>
                        <Link
                            href={dashboard({
                                mergeQuery: { ...filters, message: message.id },
                            })}
                            preserveState
                            className="focus-visible:ring-ring flex min-w-0 flex-1 items-center gap-3 py-3.5 focus-visible:rounded focus-visible:ring-2"
                            aria-current={
                                selectedId === message.id ? 'true' : undefined
                            }
                        >
                            <span
                                className={cn(
                                    'w-28 shrink-0 truncate text-sm lg:w-36',
                                    !message.is_read && 'font-semibold',
                                )}
                            >
                                {message.from_name ||
                                    message.from_email ||
                                    'Unknown sender'}
                            </span>
                            <span className="min-w-0 flex-1 truncate text-sm">
                                <span
                                    className={cn(
                                        !message.is_read && 'font-semibold',
                                    )}
                                >
                                    {message.subject || '(No subject)'}
                                </span>
                                <span className="text-muted-foreground">
                                    {' '}
                                    — {message.snippet}
                                </span>
                            </span>
                            {message.has_attachments && (
                                <Paperclip
                                    aria-label="Has attachments"
                                    className="text-muted-foreground size-3.5 shrink-0"
                                />
                            )}
                            <time
                                dateTime={message.received_at}
                                title={new Date(
                                    message.received_at,
                                ).toLocaleString()}
                                className={cn(
                                    'shrink-0 text-xs',
                                    !message.is_read && 'font-semibold',
                                )}
                            >
                                {new Date(
                                    message.received_at,
                                ).toLocaleDateString(undefined, {
                                    month: 'short',
                                    day: 'numeric',
                                })}
                            </time>
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}
