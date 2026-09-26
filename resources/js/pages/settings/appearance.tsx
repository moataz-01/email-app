import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import WorkspaceLayout from '@/layouts/workspace-layout';

export default function Appearance() {
    return (
        <WorkspaceLayout>
            <Head title="Appearance" />
            <h1 className="text-3xl font-semibold tracking-tight">
                Make yourself at home
            </h1>
            <p className="text-muted-foreground mt-3 mb-8 text-sm">
                Choose how Letter looks on this device.
            </p>
            <section className="bg-background rounded-2xl border p-6">
                <h2 className="mb-4 font-semibold">Color theme</h2>
                <AppearanceTabs />
            </section>
        </WorkspaceLayout>
    );
}
