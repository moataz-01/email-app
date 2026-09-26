import { Form, Head, Link, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    CircleHelp,
    Feather,
    Menu,
    Moon,
    Pencil,
    RefreshCw,
    Search,
    Settings,
    Sun,
    WifiOff,
} from 'lucide-react';
import { useEffect, useState, useSyncExternalStore } from 'react';
import { MailSidebar } from '@/components/mail/mail-sidebar';
import { MessageList } from '@/components/mail/message-list';
import { ReadingPane } from '@/components/mail/reading-pane';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as accountSettings } from '@/routes/accounts';
import type { MailPage } from '@/types/mail';

function subscribeConnectivity(callback: () => void) {
    window.addEventListener('online', callback);
    window.addEventListener('offline', callback);
    return () => {
        window.removeEventListener('online', callback);
        window.removeEventListener('offline', callback);
    };
}

export default function MailIndex({
    accounts,
    mailboxes,
    filters,
    messages,
    selectedMessage,
}: MailPage) {
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [notice, setNotice] = useState<'compose' | 'help' | null>(null);
    const [loading, setLoading] = useState(false);
    const [loadError, setLoadError] = useState(false);
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const online = useSyncExternalStore(
        subscribeConnectivity,
        () => navigator.onLine,
        () => true,
    );
    useEffect(() => {
        const start = router.on('start', () => {
            setLoading(true);
            setLoadError(false);
        });
        const finish = router.on('finish', () => setLoading(false));
        const networkError = router.on('networkError', (event) => {
            event.preventDefault();
            setLoading(false);
            setLoadError(true);
        });
        return () => {
            start();
            finish();
            networkError();
        };
    }, []);
    const currentAccount = accounts.find(
        (account) => account.id === filters.account,
    );
    const title =
        mailboxes.find((mailbox) => mailbox.id === filters.mailbox)?.name ??
        (filters.view === 'starred'
            ? 'Starred'
            : filters.view === 'all'
              ? 'All mail'
              : 'Inbox');
    return (
        <div className="text-foreground flex h-dvh min-h-96 flex-col overflow-hidden bg-[#f3f6f8] dark:bg-[#141b20]">
            <Head title={`${title} · Letter`} />
            <a
                href="#mail-content"
                className="bg-background sr-only z-50 rounded px-4 py-2 focus:not-sr-only focus:absolute"
            >
                Skip to messages
            </a>
            <header className="flex h-20 shrink-0 items-center gap-3 px-4 md:gap-6">
                <div
                    className={cn(
                        'flex shrink-0 items-center gap-3',
                        sidebarOpen && 'md:w-52',
                    )}
                >
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Toggle mail navigation"
                        aria-controls="mail-sidebar"
                        aria-expanded={sidebarOpen}
                        onClick={() => setSidebarOpen(!sidebarOpen)}
                    >
                        <Menu className="size-5" />
                    </Button>
                    <Link
                        href={dashboard()}
                        className="flex items-center gap-2.5"
                        aria-label="Letter inbox"
                    >
                        <span className="flex size-9 items-center justify-center rounded-xl bg-teal-800 text-white">
                            <Feather className="size-5" />
                        </span>
                        <span className="hidden text-2xl font-semibold tracking-tight sm:block">
                            Letter<span className="text-teal-600">.</span>
                        </span>
                    </Link>
                </div>
                <Form
                    key={`${filters.account}-${filters.mailbox}-${filters.view}-${filters.q}`}
                    {...dashboard.form()}
                    className="focus-within:bg-background dark:bg-muted flex h-12 min-w-0 flex-1 items-center gap-3 rounded-2xl bg-[#e5ecef] px-4 transition-shadow focus-within:shadow-sm"
                    role="search"
                >
                    <input
                        type="hidden"
                        name="account"
                        value={filters.account ?? ''}
                    />
                    <input
                        type="hidden"
                        name="mailbox"
                        value={filters.mailbox ?? ''}
                    />
                    <input type="hidden" name="view" value={filters.view} />
                    <Search className="text-muted-foreground size-5 shrink-0" />
                    <input
                        aria-label="Search cached mail by sender or subject"
                        name="q"
                        defaultValue={filters.q}
                        maxLength={200}
                        placeholder="Search your mail"
                        className="placeholder:text-muted-foreground min-w-0 flex-1 bg-transparent text-sm outline-none"
                    />
                    <button
                        type="submit"
                        className="text-muted-foreground hover:text-foreground focus-visible:ring-ring rounded px-2 py-1 text-xs font-medium focus-visible:ring-2"
                    >
                        Search
                    </button>
                </Form>
                <div className="flex shrink-0 items-center gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="hidden sm:inline-flex"
                        aria-label="About this workspace"
                        onClick={() => setNotice('help')}
                    >
                        <CircleHelp className="size-5" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Toggle color theme"
                        onClick={() =>
                            updateAppearance(
                                resolvedAppearance === 'dark'
                                    ? 'light'
                                    : 'dark',
                            )
                        }
                    >
                        {resolvedAppearance === 'dark' ? (
                            <Sun className="size-5" />
                        ) : (
                            <Moon className="size-5" />
                        )}
                    </Button>
                    <Button variant="ghost" size="icon" asChild>
                        <Link
                            href={accountSettings()}
                            aria-label="Open settings"
                        >
                            <Settings className="size-5" />
                        </Link>
                    </Button>
                </div>
            </header>
            {!online && (
                <div
                    role="status"
                    className="flex items-center justify-center gap-2 bg-amber-50 px-4 py-2 text-xs text-amber-900 dark:bg-amber-950 dark:text-amber-100"
                >
                    <WifiOff className="size-4" />
                    You appear to be offline. Cached messages are still
                    available.
                </div>
            )}
            {loadError && (
                <div
                    role="alert"
                    className="bg-destructive/10 text-destructive px-6 py-3 text-sm"
                >
                    Could not load your local mailbox.{' '}
                    <button
                        className="underline"
                        onClick={() => router.reload()}
                    >
                        Try again
                    </button>
                </div>
            )}
            <div className="flex min-h-0 flex-1">
                {sidebarOpen && (
                    <aside
                        id="mail-sidebar"
                        aria-label="Mailbox sidebar"
                        className="flex w-56 shrink-0 flex-col pb-4 max-md:absolute max-md:inset-y-20 max-md:left-0 max-md:z-20 max-md:bg-[#f3f6f8] max-md:shadow-xl dark:max-md:bg-[#141b20]"
                    >
                        <div className="px-4 pb-3">
                            <Button
                                onClick={() => setNotice('compose')}
                                className="h-14 gap-3 rounded-2xl bg-teal-100 px-6 text-teal-950 shadow-none hover:bg-teal-200 dark:bg-teal-900 dark:text-teal-50 dark:hover:bg-teal-800"
                            >
                                <Pencil className="size-5" />
                                Compose
                            </Button>
                        </div>
                        <div className="min-h-0 flex-1">
                            <MailSidebar
                                accounts={accounts}
                                mailboxes={mailboxes}
                                filters={filters}
                            />
                        </div>
                    </aside>
                )}
                <main
                    id="mail-content"
                    aria-busy={loading}
                    className="border-border/50 bg-background mr-4 mb-4 ml-4 flex min-w-0 flex-1 overflow-hidden rounded-2xl border shadow-sm md:ml-0"
                >
                    <section
                        aria-label="Mailbox"
                        className={cn(
                            'flex min-w-0 flex-1 flex-col',
                            selectedMessage && 'hidden 2xl:flex',
                            '2xl:max-w-[60%] 2xl:border-r',
                        )}
                    >
                        <div className="flex h-20 shrink-0 items-center justify-between gap-4 border-b px-6">
                            <div className="min-w-0">
                                <h1 className="truncate text-xl font-semibold tracking-tight">
                                    {title}
                                </h1>
                                <p className="text-muted-foreground mt-1 truncate text-xs">
                                    {currentAccount?.email ??
                                        'All your accounts, together'}
                                    {filters.q && ` · “${filters.q}”`}
                                </p>
                            </div>
                            <span className="bg-muted text-muted-foreground flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px]">
                                <span className="size-1.5 rounded-full bg-slate-400" />
                                Local cache
                            </span>
                        </div>
                        <div className="flex h-12 shrink-0 items-center justify-between px-3">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Reload cached messages"
                                title="Reload cached messages"
                                disabled={loading}
                                onClick={() => router.reload()}
                            >
                                <RefreshCw
                                    className={cn(
                                        'size-4',
                                        loading && 'animate-spin',
                                    )}
                                />
                            </Button>
                            <div className="flex items-center gap-2">
                                <span className="text-muted-foreground pr-2 text-xs">
                                    {messages.data.length} messages on this page
                                </span>
                                {[
                                    {
                                        cursor: messages.prev_cursor,
                                        label: 'Previous page',
                                        icon: ChevronLeft,
                                    },
                                    {
                                        cursor: messages.next_cursor,
                                        label: 'Next page',
                                        icon: ChevronRight,
                                    },
                                ].map(({ cursor, label, icon: Icon }) =>
                                    cursor ? (
                                        <Button
                                            key={label}
                                            variant="ghost"
                                            size="icon"
                                            asChild
                                        >
                                            <Link
                                                aria-label={label}
                                                href={dashboard({
                                                    query: {
                                                        ...filters,
                                                        cursor,
                                                    },
                                                })}
                                                preserveState
                                            >
                                                <Icon className="size-4" />
                                            </Link>
                                        </Button>
                                    ) : (
                                        <Button
                                            key={label}
                                            variant="ghost"
                                            size="icon"
                                            disabled
                                            aria-label={label}
                                        >
                                            <Icon className="size-4" />
                                        </Button>
                                    ),
                                )}
                            </div>
                        </div>
                        {loading && messages.data.length === 0 ? (
                            <div
                                role="status"
                                aria-label="Loading messages"
                                className="flex flex-col gap-4 p-6"
                            >
                                {[1, 2, 3, 4, 5].map((row) => (
                                    <Skeleton
                                        key={row}
                                        className="h-10 w-full"
                                    />
                                ))}
                            </div>
                        ) : (
                            <MessageList
                                key={`${filters.account}-${filters.mailbox}-${filters.view}-${filters.q}-${messages.data[0]?.id}`}
                                messages={messages.data}
                                filters={filters}
                                selectedId={selectedMessage?.id}
                                hasAccounts={accounts.length > 0}
                            />
                        )}
                        <footer className="text-muted-foreground flex shrink-0 items-center justify-between gap-2 border-t px-5 py-3 text-[11px]">
                            <span>
                                {accounts.some(
                                    (account) => account.status === 'demo',
                                )
                                    ? 'Sample workspace · No provider connected'
                                    : 'Provider synchronization is not configured'}
                            </span>
                            <span className="hidden lg:inline">
                                Private by design
                            </span>
                        </footer>
                    </section>
                    <ReadingPane message={selectedMessage} />
                </main>
            </div>
            <Dialog
                open={notice !== null}
                onOpenChange={(open) => {
                    if (!open) setNotice(null);
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {notice === 'compose'
                                ? 'Compose is not available yet'
                                : 'Your local mail workspace'}
                        </DialogTitle>
                        <DialogDescription>
                            {notice === 'compose'
                                ? 'Sending and draft saving will be enabled after secure account connections are implemented. You can browse and search your cached messages now.'
                                : 'This first version reads mail already stored on this device. Account setup, provider synchronization, and message actions are the next steps. Reload refreshes the local cache view.'}
                        </DialogDescription>
                    </DialogHeader>
                </DialogContent>
            </Dialog>
        </div>
    );
}
