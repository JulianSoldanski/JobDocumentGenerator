import { Head } from '@inertiajs/react';
import { DatedSection } from '@/components/profile/dated-section';
import Heading from '@/components/heading';
import type { ProfileEntry } from '@/types';

export default function Experience({ entries }: { entries: ProfileEntry[] }) {
    return (
        <>
            <Head title="Berufserfahrung" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Berufserfahrung"
                    description="Stationen in der Reihenfolge, in der sie im Lebenslauf stehen: laufende zuerst, dann nach Enddatum."
                />

                <DatedSection
                    entries={entries}
                    config={{
                        section: 'experience',
                        organizationLabel: 'Unternehmen',
                        headlineField: 'title',
                        headlineLabel: 'Berufsbezeichnung',
                        listField: 'bullets',
                        listLabel: 'Bullet Points',
                        listHint:
                            'Eine Zeile je Punkt. Diese Sätze stehen später wörtlich im Lebenslauf.',
                        addLabel: 'Station hinzufügen',
                        emptyText:
                            'Noch keine Station erfasst. Alles, was hier steht, wird beim Generieren wörtlich übernommen.',
                    }}
                />
            </div>
        </>
    );
}
