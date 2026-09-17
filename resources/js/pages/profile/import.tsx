import { Head, router } from '@inertiajs/react';
import { FileUp, Sparkles } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useAiTask } from '@/hooks/use-ai-task';
import { store as importCv } from '@/actions/App/Http/Controllers/Profile/CvImportController';
import { apply } from '@/actions/App/Http/Controllers/Profile/CvImportController';
import { LANGUAGE_LABELS } from '@/lib/languages';
import type { Language } from '@/types';

type ImportedEntry = {
    title?: string;
    degree?: string;
    organization: string;
    location: string;
    start_month: string | null;
    end_month: string | null;
    is_current: boolean;
    bullets?: string[];
    details?: string[];
};

type ImportedProject = { title: string; summary: string; tags: string[] };

type ImportResult = {
    language: Language;
    contact: Record<string, string>;
    experience: ImportedEntry[];
    education: ImportedEntry[];
    hard_skills: string[];
    soft_skills: string[];
    languages: { name: string; level: string }[];
    projects: ImportedProject[];
};

const period = (entry: ImportedEntry): string => {
    const end = entry.is_current ? 'heute' : (entry.end_month ?? '?');

    return entry.start_month || entry.end_month
        ? `${entry.start_month ?? '?'} – ${end}`
        : '';
};

export default function Import() {
    const task = useAiTask<ImportResult>();
    const [fileName, setFileName] = useState<string | null>(null);
    const [applying, setApplying] = useState(false);

    const upload = (file: File) => {
        setFileName(file.name);
        const payload = new FormData();
        payload.append('file', file);
        // Der Hook fängt Fehler selbst ab und legt sie in seinen Zustand.
        void task.start(importCv.url(), payload);
    };

    const adopt = () => {
        if (!task.result) {
            return;
        }

        setApplying(true);
        router.post(
            apply.url(),
            task.result as unknown as Record<string, never>,
            {
                onFinish: () => setApplying(false),
            },
        );
    };

    const result = task.result;

    return (
        <>
            <Head title="Import aus PDF" />

            <div className="max-w-3xl space-y-6">
                <Heading
                    variant="small"
                    title="Import aus einer bestehenden PDF"
                    description="Ein vorhandener Lebenslauf als Startpunkt — statt eines leeren Formulars."
                />

                <label className="hover:bg-muted/40 flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed p-8 text-center">
                    <FileUp className="text-muted-foreground h-6 w-6" />
                    <span className="text-sm font-medium">
                        Lebenslauf als PDF auswählen
                    </span>
                    <span className="text-muted-foreground text-xs">
                        {fileName ??
                            'Höchstens 10 MB. Reine Bild-Scans lassen sich nicht lesen.'}
                    </span>
                    <input
                        type="file"
                        accept="application/pdf"
                        className="hidden"
                        onChange={(event) => {
                            const file = event.target.files?.[0];
                            if (file) {
                                upload(file);
                            }
                        }}
                    />
                </label>

                {task.running && (
                    <p className="text-muted-foreground flex items-center gap-2 text-sm">
                        <Spinner className="h-4 w-4" />
                        Die KI liest den Lebenslauf. Das dauert einen Moment.
                    </p>
                )}

                {task.error && (
                    <p className="text-destructive text-sm">{task.error}</p>
                )}

                {result && (
                    <div className="space-y-5 rounded-lg border p-5">
                        <div className="flex items-center gap-2">
                            <Sparkles className="h-4 w-4" />
                            <p className="font-medium">Gefunden</p>
                            <span className="text-muted-foreground text-sm">
                                Sprache des Dokuments:{' '}
                                {LANGUAGE_LABELS[result.language]}
                            </span>
                        </div>

                        <Section title="Berufserfahrung">
                            {result.experience.map((entry, index) => (
                                <li key={index}>
                                    <span className="font-medium">
                                        {entry.title}
                                    </span>{' '}
                                    — {entry.organization} {period(entry)}
                                    {entry.bullets &&
                                        entry.bullets.length > 0 && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                ({entry.bullets.length} Punkte)
                                            </span>
                                        )}
                                </li>
                            ))}
                        </Section>

                        <Section title="Ausbildung">
                            {result.education.map((entry, index) => (
                                <li key={index}>
                                    <span className="font-medium">
                                        {entry.degree}
                                    </span>{' '}
                                    — {entry.organization} {period(entry)}
                                </li>
                            ))}
                        </Section>

                        <Section title="Skills">
                            <li>
                                Hard Skills:{' '}
                                {result.hard_skills.join(', ') || '–'}
                            </li>
                            <li>
                                Soft Skills:{' '}
                                {result.soft_skills.join(', ') || '–'}
                            </li>
                            <li>
                                Sprachen:{' '}
                                {result.languages
                                    .map((item) =>
                                        item.level
                                            ? `${item.name} (${item.level})`
                                            : item.name,
                                    )
                                    .join(', ') || '–'}
                            </li>
                        </Section>

                        <Section title="Projekte">
                            {result.projects.map((project, index) => (
                                <li key={index}>{project.title}</li>
                            ))}
                        </Section>

                        <div className="space-y-2 border-t pt-4">
                            <p className="text-muted-foreground text-sm">
                                Übernommen wird ergänzend: vorhandene Einträge
                                bleiben unangetastet, und die Texte landen in
                                der Sprache des Dokuments. Die zweite Sprache
                                schreibst du selbst — sie wird nicht übersetzt.
                            </p>
                            <Button onClick={adopt} disabled={applying}>
                                Ins Profil übernehmen
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

function Section({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    const items = Array.isArray(children) ? children : [children];

    return (
        <div className="space-y-1">
            <p className="text-sm font-medium">{title}</p>
            {items.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    Nichts gefunden.
                </p>
            ) : (
                <ul className="list-disc space-y-1 pl-5 text-sm">{children}</ul>
            )}
        </div>
    );
}
