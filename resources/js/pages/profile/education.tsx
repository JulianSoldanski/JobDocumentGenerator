import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { DatedSection } from '@/components/profile/dated-section';
import type { ProfileEntry } from '@/types';

export default function Education({ entries }: { entries: ProfileEntry[] }) {
    return (
        <>
            <Head title="Ausbildung" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Ausbildung"
                    description="Institution, Zeitraum, Ort, Abschluss und Details."
                />

                <DatedSection
                    entries={entries}
                    config={{
                        section: 'education',
                        organizationLabel: 'Institution',
                        headlineField: 'degree',
                        headlineLabel: 'Abschluss',
                        listField: 'details',
                        listLabel: 'Details',
                        listHint:
                            'Eine Zeile je Detail, etwa Schwerpunkt, Note oder Abschlussarbeit.',
                        addLabel: 'Ausbildung hinzufügen',
                        emptyText: 'Noch keine Ausbildung erfasst.',
                    }}
                />
            </div>
        </>
    );
}
