import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { SkillList } from '@/components/profile/skill-list';
import type { ProfileEntry } from '@/types';

export default function Skills({
    hardSkills,
    softSkills,
    languages,
}: {
    hardSkills: ProfileEntry[];
    softSkills: ProfileEntry[];
    languages: ProfileEntry[];
}) {
    return (
        <>
            <Head title="Skills & Sprachen" />

            <div className="max-w-3xl space-y-8">
                <Heading
                    variant="small"
                    title="Skills & Sprachen"
                    description="Drei geordnete Listen. Die KI wählt beim Generieren aus und sortiert — geschrieben werden sie hier."
                />

                <SkillList
                    section="hard_skill"
                    title="Hard Skills"
                    description="Technologien, Werkzeuge, Methoden."
                    entries={hardSkills}
                />

                <SkillList
                    section="soft_skill"
                    title="Soft Skills"
                    description="Arbeitsweise und Zusammenarbeit."
                    entries={softSkills}
                />

                <SkillList
                    section="language"
                    title="Sprachen"
                    description="Mit Niveau, etwa Muttersprache oder C1."
                    entries={languages}
                    withLevel
                />
            </div>
        </>
    );
}
