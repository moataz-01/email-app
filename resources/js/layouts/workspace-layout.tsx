import { Link } from '@inertiajs/react';
import { Feather, Inbox, Settings2, Palette } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { dashboard, home } from '@/routes';
import { index } from '@/routes/accounts';
import { edit } from '@/routes/appearance';

export default function WorkspaceLayout({ children }: { children: ReactNode }) {
    return (
        <div className="text-foreground min-h-dvh bg-[#f3f6f8] dark:bg-[#141b20]">
            <header className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-6">
                <Link
                    href={home()}
                    className="flex items-center gap-2.5 text-2xl font-semibold tracking-tight"
                >
                    <span className="flex size-9 items-center justify-center rounded-xl bg-teal-800 text-white">
                        <Feather className="size-5" />
                    </span>
                    Letter<span className="-ml-2 text-teal-600">.</span>
                </Link>
                <nav aria-label="Workspace" className="flex gap-1">
                    <Button variant="ghost" asChild>
                        <Link href={dashboard()} aria-label="Inbox">
                            <Inbox />
                            <span className="hidden sm:inline">Inbox</span>
                        </Link>
                    </Button>
                    <Button variant="ghost" asChild>
                        <Link href={index()} aria-label="Accounts">
                            <Settings2 />
                            <span className="hidden sm:inline">Accounts</span>
                        </Link>
                    </Button>
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={edit()} aria-label="Appearance">
                            <Palette />
                        </Link>
                    </Button>
                </nav>
            </header>
            <main className="mx-auto max-w-3xl px-6 pt-4 pb-12">
                {children}
            </main>
        </div>
    );
}
