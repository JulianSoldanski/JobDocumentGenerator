import { ArrowDown, ArrowUp } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { getJson, patchJson, RequestError } from '@/lib/http';
import {
    show,
    update,
} from '@/actions/App/Http/Controllers/DocumentController';
import type { CvContent, DocumentVersion } from '@/types';

type Form = {
    statement: string;
    experience: { id: string; label: string; bullets: string }[];
    education: { id: string; label: string; details: string }[];
    projects: { id: string; title: string; included: boolean }[];
    hard: string;
    soft: string;
    languages: string;
};

const lines = (text: string): string[] =>
    text
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line !== '');

const names = (text: string): string[] =>
    text
        .split(',')
        .map((name) => name.trim())
        .filter((name) => name !== '');

/** „Deutsch (Muttersprache), Englisch (C1, fließend)" — Kommas in Klammern zählen nicht. */
const languages = (text: string) =>
    text
        .split(/,(?![^()]*\))/)
        .map((item) => item.trim())
        .filter((item) => item !== '')
        .map((item) => {
            const match = item.match(/^(.*?)\s*\((.*)\)$/);

            return match
                ? { name: match[1].trim(), level: match[2].trim() }
                : { name: item, level: '' };
        });

const period = (start: string | null, end: string | null, current: boolean) =>
    [start, current ? 'heute' : end].filter(Boolean).join(' – ');

const toForm = (content: CvContent): Form => ({
    statement: content.statement,
    experience: content.experience.map((entry) => ({
        id: entry.id,
        label: `${entry.title} — ${entry.organization} (${period(entry.start_month, entry.end_month, entry.is_current)})`,
        bullets: entry.bullets.join('\n'),
    })),
    education: content.education.map((entry) => ({
        id: entry.id,
        label: `${entry.degree} — ${entry.organization}`,
        details: entry.details.join('\n'),
    })),
    projects: content.projects.map((project) => ({
        id: project.id,
        title: project.title,
        included: project.included,
    })),
    hard: content.skills.hard.map((skill) => skill.name).join(', '),
    soft: content.skills.soft.map((skill) => skill.name).join(', '),
    languages: content.skills.languages
        .map((item) =>
            item.level ? `${item.name} (${item.level})` : item.name,
        )
        .join(', '),
});

type Props = {
    documentId: number;
    onSaved: (document: DocumentVersion) => void;
    onCancel: () => void;
};

/**
 * Der Lebenslauf dieser Bewerbung, von Hand nachgeschärft. Geändert wird nur
 * das Dokument — das Profil bleibt, wie es ist.
 */
export function CvEditor({ documentId, onSaved, onCancel }: Props) {
    const [form, setForm] = useState<Form | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let active = true;

        getJson<{ content: CvContent }>(show.url({ document: documentId }))
            .then(({ content }) => active && setForm(toForm(content)))
            .catch(
                () =>
                    active && setError('Der Lebenslauf ließ sich nicht laden.'),
            );

        return () => {
            active = false;
        };
    }, [documentId]);

    if (!form) {
        return error ? (
            <p className="text-destructive text-sm">{error}</p>
        ) : (
            <p className="text-muted-foreground flex items-center gap-2 text-sm">
                <Spinner className="h-4 w-4" />
                Lebenslauf wird geladen.
            </p>
        );
    }

    const change = (values: Partial<Form>) =>
        setForm((current) => (current ? { ...current, ...values } : current));

    const moveProject = (index: number, step: -1 | 1) => {
        const projects = [...form.projects];
        const target = index + step;

        if (target < 0 || target >= projects.length) {
            return;
        }

        [projects[index], projects[target]] = [
            projects[target],
            projects[index],
        ];
        change({ projects });
    };

    const save = async () => {
        setSaving(true);
        setError(null);

        try {
            onSaved(
                await patchJson<DocumentVersion>(
                    update.url({ document: documentId }),
                    {
                        statement: form.statement,
                        experience: form.experience.map((entry) => ({
                            id: entry.id,
                            bullets: lines(entry.bullets),
                        })),
                        education: form.education.map((entry) => ({
                            id: entry.id,
                            details: lines(entry.details),
                        })),
                        projects: form.projects.map(({ id, included }) => ({
                            id,
                            included,
                        })),
                        skills: {
                            hard: names(form.hard),
                            soft: names(form.soft),
                            languages: languages(form.languages),
                        },
                    },
                ),
            );
        } catch (problem) {
            setError(
                problem instanceof RequestError
                    ? problem.message
                    : 'Die Änderungen ließen sich nicht speichern.',
            );
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="space-y-6 rounded-lg border p-4">
            <p className="text-muted-foreground text-sm">
                Änderungen gelten nur für diesen Lebenslauf — dein Profil
                bleibt, wie es ist.
            </p>

            <div className="grid gap-2">
                <Label htmlFor="cv-statement">Profil-Statement</Label>
                <Textarea
                    id="cv-statement"
                    rows={3}
                    value={form.statement}
                    onChange={(event) =>
                        change({ statement: event.target.value })
                    }
                />
            </div>

            {form.experience.length > 0 && (
                <Section
                    title="Berufserfahrung"
                    hint="Ein Stichpunkt je Zeile."
                >
                    {form.experience.map((entry, index) => (
                        <div key={entry.id} className="grid gap-1.5">
                            <p className="text-sm font-medium">{entry.label}</p>
                            <Textarea
                                rows={Math.max(
                                    2,
                                    entry.bullets.split('\n').length,
                                )}
                                value={entry.bullets}
                                onChange={(event) => {
                                    const experience = [...form.experience];
                                    experience[index] = {
                                        ...entry,
                                        bullets: event.target.value,
                                    };
                                    change({ experience });
                                }}
                            />
                        </div>
                    ))}
                </Section>
            )}

            {form.education.length > 0 && (
                <Section title="Ausbildung" hint="Ein Detail je Zeile.">
                    {form.education.map((entry, index) => (
                        <div key={entry.id} className="grid gap-1.5">
                            <p className="text-sm font-medium">{entry.label}</p>
                            <Textarea
                                rows={Math.max(
                                    1,
                                    entry.details.split('\n').length,
                                )}
                                value={entry.details}
                                onChange={(event) => {
                                    const education = [...form.education];
                                    education[index] = {
                                        ...entry,
                                        details: event.target.value,
                                    };
                                    change({ education });
                                }}
                            />
                        </div>
                    ))}
                </Section>
            )}

            {form.projects.length > 0 && (
                <Section
                    title="Projekte"
                    hint="Eingeschaltete erscheinen im Lebenslauf, in dieser Reihenfolge."
                >
                    <ul className="divide-y rounded-md border">
                        {form.projects.map((project, index) => (
                            <li
                                key={project.id}
                                className="flex items-center gap-3 px-3 py-2"
                            >
                                <Checkbox
                                    id={`project-${project.id}`}
                                    checked={project.included}
                                    onCheckedChange={(checked) => {
                                        const projects = [...form.projects];
                                        projects[index] = {
                                            ...project,
                                            included: checked === true,
                                        };
                                        change({ projects });
                                    }}
                                />
                                <label
                                    htmlFor={`project-${project.id}`}
                                    className={
                                        project.included
                                            ? 'flex-1 text-sm'
                                            : 'text-muted-foreground flex-1 text-sm line-through'
                                    }
                                >
                                    {project.title}
                                </label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="h-7 w-7"
                                    disabled={index === 0}
                                    onClick={() => moveProject(index, -1)}
                                    aria-label="Nach oben"
                                >
                                    <ArrowUp className="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="h-7 w-7"
                                    disabled={
                                        index === form.projects.length - 1
                                    }
                                    onClick={() => moveProject(index, 1)}
                                    aria-label="Nach unten"
                                >
                                    <ArrowDown className="h-4 w-4" />
                                </Button>
                            </li>
                        ))}
                    </ul>
                </Section>
            )}

            <Section
                title="Skills"
                hint="Durch Komma getrennt, in dieser Reihenfolge."
            >
                <div className="grid gap-2">
                    <Label htmlFor="cv-hard">Fachlich</Label>
                    <Input
                        id="cv-hard"
                        value={form.hard}
                        onChange={(event) =>
                            change({ hard: event.target.value })
                        }
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="cv-soft">Überfachlich</Label>
                    <Input
                        id="cv-soft"
                        value={form.soft}
                        onChange={(event) =>
                            change({ soft: event.target.value })
                        }
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="cv-languages">Sprachen</Label>
                    <Input
                        id="cv-languages"
                        value={form.languages}
                        placeholder="Deutsch (Muttersprache), Englisch (C1)"
                        onChange={(event) =>
                            change({ languages: event.target.value })
                        }
                    />
                </div>
            </Section>

            {error && <p className="text-destructive text-sm">{error}</p>}

            <div className="bg-background sticky bottom-0 flex justify-end gap-2 border-t pt-4">
                <Button type="button" variant="ghost" onClick={onCancel}>
                    Abbrechen
                </Button>
                <Button
                    type="button"
                    onClick={() => void save()}
                    disabled={saving}
                >
                    {saving && <Spinner className="h-4 w-4" />}
                    Speichern
                </Button>
            </div>
        </div>
    );
}

function Section({
    title,
    hint,
    children,
}: {
    title: string;
    hint: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-3">
            <div>
                <p className="font-medium">{title}</p>
                <p className="text-muted-foreground text-xs">{hint}</p>
            </div>
            {children}
        </div>
    );
}
