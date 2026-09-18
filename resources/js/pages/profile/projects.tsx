import { Head, router, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Pencil, Plus, Sparkles, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { LanguageTabs } from '@/components/profile/language-tabs';
import { MissingLanguageBadge } from '@/components/profile/missing-language-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useAiTask } from '@/hooks/use-ai-task';
import { store as draftProject } from '@/actions/App/Http/Controllers/Profile/ProjectDraftController';
import { cn } from '@/lib/utils';
import {
    destroy,
    flags,
    store,
    update,
} from '@/actions/App/Http/Controllers/Profile/ProjectController';
import type { Language, Project, Translations } from '@/types';

type FormData = {
    is_visible: boolean;
    in_project_list: boolean;
    link: string;
    grade: string;
    tags: string[];
    client: string;
    period: string;
    team_size: string;
    technologies: string[];
    translations: Translations;
};

const text = (value: string | string[] | undefined): string =>
    Array.isArray(value) ? value.join('\n') : (value ?? '');

const emptyBlock = () => ({
    title: '',
    summary: '',
    role: '',
    situation: '',
    contributions: '',
    result: '',
});

const emptyForm = (): FormData => ({
    is_visible: true,
    in_project_list: true,
    link: '',
    grade: '',
    tags: [],
    client: '',
    period: '',
    team_size: '',
    technologies: [],
    translations: { de: emptyBlock(), en: emptyBlock() },
});

const asList = (value: string): string[] =>
    value
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);

/**
 * Behält den getippten Text, damit ein gerade eingegebenes Komma nicht beim
 * Zurückschreiben der Liste verschwindet. Ändert sich die Liste von außen,
 * wird sie neu angezeigt.
 */
function ListInput({
    value,
    onChange,
    ...props
}: Omit<React.ComponentProps<typeof Input>, 'value' | 'onChange'> & {
    value: string[];
    onChange: (value: string[]) => void;
}) {
    const [raw, setRaw] = useState(value.join(', '));
    const shown =
        asList(raw).join(', ') === value.join(', ') ? raw : value.join(', ');

    return (
        <Input
            {...props}
            value={shown}
            onChange={(event) => {
                setRaw(event.target.value);
                onChange(asList(event.target.value));
            }}
        />
    );
}

export default function Projects({ projects }: { projects: Project[] }) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Project | null>(null);
    const [language, setLanguage] = useState<Language>('de');
    const [showLongForm, setShowLongForm] = useState(false);

    const form = useForm<FormData>(emptyForm());

    type Draft = {
        client: string;
        period: string;
        team_size: string;
        technologies: string[];
        translations: Translations;
    };

    const draft = useAiTask<Draft>();

    /**
     * Der Entwurf füllt die ausführlichen Felder beider Sprachen. Gespeichert
     * wird nichts, bevor der Nutzer ihn gelesen hat.
     */
    const requestDraft = () =>
        void draft.start(draftProject.url(), {
            project_id: editing?.id ?? null,
            title:
                text(form.data.translations.de.title) ||
                text(form.data.translations.en.title),
            summary:
                text(form.data.translations.de.summary) ||
                text(form.data.translations.en.summary),
            tags: form.data.tags,
            grade: form.data.grade,
        });

    const adoptDraft = () => {
        if (!draft.result) {
            return;
        }

        const result = draft.result;

        setShowLongForm(true);
        form.setData((current) => ({
            ...current,
            client: result.client || current.client,
            period: result.period || current.period,
            team_size: result.team_size || current.team_size,
            technologies:
                result.technologies.length > 0
                    ? result.technologies
                    : current.technologies,
            translations: {
                de: { ...current.translations.de, ...result.translations.de },
                en: { ...current.translations.en, ...result.translations.en },
            },
        }));
        draft.reset();
    };

    const openNew = () => {
        setEditing(null);
        setLanguage('de');
        setShowLongForm(false);
        form.clearErrors();
        form.setData(emptyForm());
        setOpen(true);
    };

    const openEdit = (project: Project) => {
        setEditing(project);
        setLanguage('de');
        setShowLongForm(project.has_long_form);
        form.clearErrors();
        form.setData({
            is_visible: project.is_visible,
            in_project_list: project.in_project_list,
            link: project.link ?? '',
            grade: project.grade ?? '',
            tags: project.tags,
            client: project.client,
            period: project.period,
            team_size: project.team_size,
            technologies: project.technologies,
            translations: {
                de: {
                    title: text(project.translations.de?.title),
                    summary: text(project.translations.de?.summary),
                    role: text(project.translations.de?.role),
                    situation: text(project.translations.de?.situation),
                    contributions: text(project.translations.de?.contributions),
                    result: text(project.translations.de?.result),
                },
                en: {
                    title: text(project.translations.en?.title),
                    summary: text(project.translations.en?.summary),
                    role: text(project.translations.en?.role),
                    situation: text(project.translations.en?.situation),
                    contributions: text(project.translations.en?.contributions),
                    result: text(project.translations.en?.result),
                },
            },
        });
        setOpen(true);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        if (editing) {
            form.patch(update.url(editing.id), options);
        } else {
            form.post(store.url(), options);
        }
    };

    const setTranslation = (field: string, value: string) =>
        form.setData('translations', {
            ...form.data.translations,
            [language]: { ...form.data.translations[language], [field]: value },
        });

    const toggle = (
        project: Project,
        field: 'is_visible' | 'in_project_list',
    ) =>
        router.patch(
            flags.url(project.id),
            { [field]: !project[field] },
            { preserveScroll: true },
        );

    const remove = (project: Project) => {
        if (confirm(`„${project.headline || 'Projekt'}" wirklich löschen?`)) {
            router.delete(destroy.url(project.id), { preserveScroll: true });
        }
    };

    const block = form.data.translations[language];

    return (
        <>
            <Head title="Projekte" />

            <div className="max-w-3xl space-y-6">
                <Heading
                    variant="small"
                    title="Projekte"
                    description="Kurz für den Lebenslauf, ausführlich für die Projektliste."
                />

                <div className="flex justify-end">
                    <Button size="sm" onClick={openNew}>
                        <Plus className="h-4 w-4" /> Projekt hinzufügen
                    </Button>
                </div>

                {projects.length === 0 ? (
                    <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                        Noch keine Projekte erfasst. Die KI wählt beim
                        Generieren aus dieser Liste aus.
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {projects.map((project) => (
                            <li
                                key={project.id}
                                className={cn(
                                    'flex items-start justify-between gap-4 rounded-lg border p-4',
                                    { 'opacity-50': !project.is_visible },
                                )}
                            >
                                <div className="min-w-0 space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-medium">
                                            {project.headline || 'Ohne Titel'}
                                        </span>
                                        {project.in_project_list && (
                                            <Badge
                                                variant="secondary"
                                                className="text-[10px]"
                                                title="Erscheint in der Projektliste"
                                            >
                                                Projektliste
                                            </Badge>
                                        )}
                                        <MissingLanguageBadge
                                            missing={project.missing_languages}
                                        />
                                    </div>
                                    <p className="text-muted-foreground text-sm">
                                        {project.tags.join(' · ') ||
                                            'ohne Tags'}
                                        {project.grade
                                            ? ` · Note ${project.grade}`
                                            : ''}
                                    </p>
                                </div>

                                <div className="flex shrink-0 gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        title={
                                            project.is_visible
                                                ? 'Für den Lebenslauf sichtbar — ausblenden'
                                                : 'Ausgeblendet — einblenden'
                                        }
                                        onClick={() =>
                                            toggle(project, 'is_visible')
                                        }
                                    >
                                        {project.is_visible ? (
                                            <Eye className="h-4 w-4" />
                                        ) : (
                                            <EyeOff className="h-4 w-4" />
                                        )}
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        title="Bearbeiten"
                                        onClick={() => openEdit(project)}
                                    >
                                        <Pencil className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        title="Löschen"
                                        onClick={() => remove(project)}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editing
                                ? 'Projekt bearbeiten'
                                : 'Projekt hinzufügen'}
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={submit} className="space-y-5">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="tags">Tags</Label>
                                <ListInput
                                    id="tags"
                                    value={form.data.tags}
                                    onChange={(tags) =>
                                        form.setData('tags', tags)
                                    }
                                    placeholder="react, typescript"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="grade">Note (optional)</Label>
                                <Input
                                    id="grade"
                                    value={form.data.grade}
                                    onChange={(event) =>
                                        form.setData(
                                            'grade',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="link">Link (optional)</Label>
                            <Input
                                id="link"
                                value={form.data.link}
                                onChange={(event) =>
                                    form.setData('link', event.target.value)
                                }
                                placeholder="https://github.com/…"
                            />
                            {form.errors.link && (
                                <p className="text-destructive text-sm">
                                    {form.errors.link}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-wrap gap-6">
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={form.data.is_visible}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'is_visible',
                                            checked === true,
                                        )
                                    }
                                />
                                Für den Lebenslauf verfügbar
                            </label>
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={form.data.in_project_list}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'in_project_list',
                                            checked === true,
                                        )
                                    }
                                />
                                In der Projektliste
                            </label>
                        </div>

                        <div className="space-y-4 rounded-lg border p-4">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium">
                                    Texte je Sprache
                                </p>
                                <LanguageTabs
                                    value={language}
                                    onChange={setLanguage}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Titel</Label>
                                <Input
                                    id="title"
                                    value={text(block.title)}
                                    onChange={(event) =>
                                        setTranslation(
                                            'title',
                                            event.target.value,
                                        )
                                    }
                                />
                                {form.errors[
                                    'translations.de.title' as never
                                ] && (
                                    <p className="text-destructive text-sm">
                                        {
                                            form.errors[
                                                'translations.de.title' as never
                                            ]
                                        }
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="summary">
                                    Kurzbeschreibung (Lebenslauf)
                                </Label>
                                <Textarea
                                    id="summary"
                                    rows={3}
                                    value={text(block.summary)}
                                    onChange={(event) =>
                                        setTranslation(
                                            'summary',
                                            event.target.value,
                                        )
                                    }
                                />
                                {form.errors[
                                    'translations.de.summary' as never
                                ] && (
                                    <p className="text-destructive text-sm">
                                        {
                                            form.errors[
                                                'translations.de.summary' as never
                                            ]
                                        }
                                    </p>
                                )}
                            </div>

                            {showLongForm && (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="role">Rolle</Label>
                                        <Input
                                            id="role"
                                            value={text(block.role)}
                                            onChange={(event) =>
                                                setTranslation(
                                                    'role',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="situation">
                                            Ausgangslage
                                        </Label>
                                        <Textarea
                                            id="situation"
                                            rows={2}
                                            value={text(block.situation)}
                                            onChange={(event) =>
                                                setTranslation(
                                                    'situation',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="contributions">
                                            Eigener Beitrag
                                        </Label>
                                        <Textarea
                                            id="contributions"
                                            rows={3}
                                            value={text(block.contributions)}
                                            onChange={(event) =>
                                                setTranslation(
                                                    'contributions',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <p className="text-muted-foreground text-xs">
                                            Eine Zeile je Punkt.
                                        </p>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="result">Ergebnis</Label>
                                        <Textarea
                                            id="result"
                                            rows={2}
                                            value={text(block.result)}
                                            onChange={(event) =>
                                                setTranslation(
                                                    'result',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </>
                            )}
                        </div>

                        {showLongForm ? (
                            <div className="grid gap-4 rounded-lg border p-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="client">Auftraggeber</Label>
                                    <Input
                                        id="client"
                                        value={form.data.client}
                                        onChange={(event) =>
                                            form.setData(
                                                'client',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="period">Zeitraum</Label>
                                    <Input
                                        id="period"
                                        value={form.data.period}
                                        onChange={(event) =>
                                            form.setData(
                                                'period',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="03/2024 – 07/2024"
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="team_size">Teamgröße</Label>
                                    <Input
                                        id="team_size"
                                        value={form.data.team_size}
                                        onChange={(event) =>
                                            form.setData(
                                                'team_size',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="4 Personen"
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="technologies">
                                        Technologien
                                    </Label>
                                    <ListInput
                                        id="technologies"
                                        value={form.data.technologies}
                                        onChange={(technologies) =>
                                            form.setData(
                                                'technologies',
                                                technologies,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        ) : (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setShowLongForm(true)}
                            >
                                Ausführliche Felder für die Projektliste
                            </Button>
                        )}

                        <div className="space-y-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={requestDraft}
                                disabled={draft.running}
                            >
                                {draft.running ? (
                                    <Spinner className="h-4 w-4" />
                                ) : (
                                    <Sparkles className="h-4 w-4" />
                                )}
                                Entwurf aus der Kurzfassung
                            </Button>

                            {draft.error && (
                                <p className="text-destructive text-sm">
                                    {draft.error}
                                </p>
                            )}

                            {draft.result && (
                                <div className="bg-muted/40 space-y-2 rounded-lg border p-3 text-sm">
                                    <p className="font-medium">
                                        Vorschlag der KI
                                    </p>
                                    <p className="text-muted-foreground">
                                        {text(
                                            draft.result.translations.de.role,
                                        )}
                                        {' · '}
                                        {text(
                                            draft.result.translations.de
                                                .situation,
                                        )}
                                    </p>
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            onClick={adoptDraft}
                                        >
                                            Übernehmen
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            onClick={draft.reset}
                                        >
                                            Verwerfen
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setOpen(false)}
                            >
                                Abbrechen
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                Speichern
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
