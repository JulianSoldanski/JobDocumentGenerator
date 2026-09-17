import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function Index() {
    return (
        <>
            <Head title="Generator" />

            <div className="px-4 py-6">
                <Heading
                    title="Generator"
                    description="Der Arbeitsplatz: Stelle links, Dokument rechts."
                />

                <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-sm">
                    Dieser Bereich wird gerade gebaut. Das Profil ist bereits
                    nutzbar.
                </p>
            </div>
        </>
    );
}
