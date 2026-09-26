import { Link } from '@inertiajs/react';
import { ArrowLeft, MailOpen, Paperclip, ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { SelectedMessage } from '@/types/mail';

export function ReadingPane({ message }: { message: SelectedMessage | null }) {
    if (!message) {
        return (
            <div className="bg-muted/10 hidden flex-1 flex-col items-center justify-center gap-3 px-8 text-center 2xl:flex">
                <MailOpen className="text-muted-foreground/40 size-9" />
                <p className="text-sm font-medium">Room to focus</p>
                <p className="text-muted-foreground max-w-48 text-xs leading-5">
                    Choose a message to read it here.
                </p>
            </div>
        );
    }
    return (
        <article
            aria-label="Message reading pane"
            className="bg-background flex min-h-0 min-w-0 flex-1 flex-col"
        >
            <div className="flex h-14 shrink-0 items-center gap-3 border-b px-5">
                <Button variant="ghost" size="icon" asChild>
                    <Link
                        href={dashboard({ mergeQuery: { message: null } })}
                        preserveState
                        aria-label="Close message"
                    >
                        <ArrowLeft />
                    </Link>
                </Button>
                <span className="text-muted-foreground text-xs">
                    Reading from your local cache
                </span>
            </div>
            <div className="flex-1 overflow-y-auto p-6 lg:p-8">
                <h2 className="text-2xl leading-snug font-medium break-words">
                    {message.subject || '(No subject)'}
                </h2>
                <div className="mt-6 flex items-start gap-3">
                    <div
                        aria-hidden="true"
                        className="flex size-10 shrink-0 items-center justify-center rounded-full bg-teal-100 font-semibold text-teal-800 dark:bg-teal-950 dark:text-teal-200"
                    >
                        {(message.from_name || message.from_email || '?')
                            .slice(0, 1)
                            .toUpperCase()}
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="text-sm font-semibold break-words">
                            {message.from_name ||
                                message.from_email ||
                                'Unknown sender'}
                        </p>
                        <p className="text-muted-foreground text-xs break-all">
                            {message.from_email}
                        </p>
                        <p className="text-muted-foreground mt-1 text-xs break-words">
                            {message.recipients
                                .filter(
                                    (recipient) =>
                                        recipient.kind === 'to' ||
                                        recipient.kind === 'cc',
                                )
                                .map(
                                    (recipient) =>
                                        `${recipient.kind}: ${recipient.name || recipient.email}`,
                                )
                                .join(', ')}
                        </p>
                    </div>
                </div>
                <time
                    dateTime={message.received_at}
                    className="text-muted-foreground mt-3 block text-xs"
                >
                    {new Date(message.received_at).toLocaleString()}
                </time>
                <div className="mt-8 text-sm leading-7 break-words whitespace-pre-wrap">
                    {message.text ??
                        'The plain-text body is not available in the local cache yet.'}
                </div>
                {message.attachments.length > 0 && (
                    <section
                        aria-label="Attachments"
                        className="mt-8 border-t pt-5"
                    >
                        <h3 className="mb-3 text-xs font-semibold">
                            {message.attachments.length} attachments
                        </h3>
                        <div className="flex flex-wrap gap-2">
                            {message.attachments.map((attachment) => (
                                <div
                                    key={attachment.id}
                                    className="flex max-w-full items-center gap-3 rounded-lg border px-3 py-2"
                                >
                                    <Paperclip className="text-muted-foreground size-4 shrink-0" />
                                    <span className="min-w-0">
                                        <span className="block truncate text-sm">
                                            {attachment.filename}
                                        </span>
                                        <span className="text-muted-foreground block text-xs">
                                            {Math.ceil(attachment.size / 1024)}{' '}
                                            KB · Not downloaded
                                        </span>
                                    </span>
                                </div>
                            ))}
                        </div>
                    </section>
                )}
                <p className="text-muted-foreground mt-10 flex items-center gap-2 text-xs">
                    <ShieldCheck className="size-3.5 shrink-0" />
                    Plain-text view · Remote content is blocked
                </p>
            </div>
        </article>
    );
}
