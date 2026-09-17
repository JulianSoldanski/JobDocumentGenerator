import { router, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { LanguageTabs } from '@/components/profile/language-tabs';
import { MissingLanguageBadge } from '@/components/profile/missing-language-badge';
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
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import {
    destroy,
    store,
    update,
    visibility,
} from '@/actions/App/Http/Controllers/Profile/ProfileEntryController';
import type { Language, ProfileEntry, Translations } from '@/types';

type SectionConfig = {
    section: 'experience' | 'education';
    /** Sprachneutrales Pflichtfeld: Firma bzw. Institution. */
    organizationLabel: string;
    /** Das Feld, das den Eintrag überschreibt. */
    headlineField: 'title' | 'degree';
    headlineLabel: string;
    /** Das Listenfeld je Sprache. */
    listField: 'bullets' | 'details';
    listLabel: string;
    listHint: string;
    addLabel: string;
    emptyText: string;
};

type FormData = {
    section: string;
    organization: string;
    start_month: string;
    end_month: string;
    is_current: boolean;
    is_visible: boolean;
    translations: Translations;
};

const emptyTranslations = (config: SectionConfig): Translations => ({
    de: { [config.headlineField]: '', location: '', [config.listField]: '' },
    en: { [config.headlineField]: '', location: '', [config.listField]: '' },
});

/** Der Editor speichert Listen als Text, eine Zeile je Punkt. */
const toText = (value: string | string[] | undefined): string =>
    Array.isArray(value) ? value.join('\n') : (value ?? '');

const formatMonth = (month: string | null): string => {
    if (!month) {
        return '';
    }
    const [year, index] = month.split('-');
    const months = [
        'Jan',
        'Feb',
        'Mär',
        'Apr',
        'Mai',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Okt',
        'Nov',
        'Dez',
    ];

    return `${months[Number(index) - 1] ?? index} ${year}`;
};

const period = (entry: ProfileEntry): string => {
    const start = formatMonth(entry.start_month);
    const end = entry.is_current ? 'heute' : formatMonth(entry.end_month);

    if (!start && !end) {
        return '';
    }

    return `${start || '?'} – ${end || '?'}`;
};

export function DatedSection({
    config,
    entries,
}: {
    config: SectionConfig;
    entries: ProfileEntry[];
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ProfileEntry | null>(null);
    const [language, setLanguage] = useState<Language>('de');

    const form = useForm<FormData>({
        section: config.section,
        organization: '',
        start_month: '',
        end_month: '',
        is_current: false,
        is_visible: true,
        translations: emptyTranslations(config),
    });

    const openNew = () => {
        setEditing(null);
        setLanguage('de');
        form.setDefaults({
            section: config.section,
            organization: '',
            start_month: '',
            end_month: '',
            is_current: false,
            is_visible: true,
            translations: emptyTranslations(config),
        });
        form.reset();
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (entry: ProfileEntry) => {
        setEditing(entry);
        setLanguage('de');
        form.clearErrors();
        form.setData({
            section: config.section,
            organization: entry.organization,
            start_month: entry.start_month ?? '',
            end_month: entry.end_month ?? '',
            is_current: entry.is_current,
            is_visible: entry.is_visible,
            translations: {
                de: {
                    [config.headlineField]: toText(
                        entry.translations.de?.[config.headlineField],
                    ),
                    location: toText(entry.translations.de?.location),
                    [config.listField]: toText(
                        entry.translations.de?.[config.listField],
                    ),
                },
                en: {
                    [config.headlineField]: toText(
                        entry.translations.en?.[config.headlineField],
                    ),
                    location: toText(entry.translations.en?.location),
                    [config.listField]: toText(
                        entry.translations.en?.[config.listField],
                    ),
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

    const setTranslation = (field: string, value: string) => {
        form.setData('translations', {
            ...form.data.translations,
            [language]: { ...form.data.translations[language], [field]: value },
        });
    };

    const toggleVisibility = (entry: ProfileEntry) => {
        router.patch(
            visibility.url(entry.id),
            { is_visible: !entry.is_visible },
            { preserveScroll: true },
        );
    };

    const remove = (entry: ProfileEntry) => {
        if (confirm(`„${entry.headline || 'Eintrag'}" wirklich löschen?`)) {
            router.delete(destroy.url(entry.id), { preserveScroll: true });
        }
    };

    const block = form.data.translations[language];
    const headlineError =
        form.errors[`translations.de.${config.headlineField}` as never];

    return (
        <div className="space-y-4">
            <div className="flex justify-end">
                <Button size="sm" onClick={openNew}>
                    <Plus className="h-4 w-4" />
                    {config.addLabel}
                </Button>
            </div>

            {entries.length === 0 ? (
                <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                    {config.emptyText}
                </p>
            ) : (
                <ul className="space-y-2">
                    {entries.map((entry) => (
                        <li
                            key={entry.id}
                            className={cn(
                                'flex items-start justify-between gap-4 rounded-lg border p-4',
                                { 'opacity-50': !entry.is_visible },
                            )}
                        >
                            <div className="min-w-0 space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium">
                                        {entry.headline || 'Ohne Titel'}
                                    </span>
                                    <MissingLanguageBadge
                                        missing={entry.missing_languages}
                                    />
                                </div>
                                <p className="text-muted-foreground text-sm">
                                    {[entry.organization, period(entry)]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </p>
                            </div>

                            <div className="flex shrink-0 gap-1">
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    title={
                                        entry.is_visible
                                            ? 'Im Lebenslauf sichtbar — ausblenden'
                                            : 'Ausgeblendet — einblenden'
                                    }
                                    onClick={() => toggleVisibility(entry)}
                                >
                                    {entry.is_visible ? (
                                        <Eye className="h-4 w-4" />
                                    ) : (
                                        <EyeOff className="h-4 w-4" />
                                    )}
                                </Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    title="Bearbeiten"
                                    onClick={() => openEdit(entry)}
                                >
                                    <Pencil className="h-4 w-4" />
                                </Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    title="Löschen"
                                    onClick={() => remove(entry)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Eintrag bearbeiten' : config.addLabel}
                        </DialogTitle>
                    </DialogHeader>

                    <form onSubmit={submit} className="space-y-5">
                        <div className="grid gap-2">
                            <Label htmlFor="organization">
                                {config.organizationLabel}
                            </Label>
                            <Input
                                id="organization"
                                value={form.data.organization}
                                onChange={(event) =>
                                    form.setData(
                                        'organization',
                                        event.target.value,
                                    )
                                }
                            />
                            {form.errors.organization && (
                                <p className="text-destructive text-sm">
                                    {form.errors.organization}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="start_month">Von</Label>
                                <Input
                                    id="start_month"
                                    type="month"
                                    value={form.data.start_month}
                                    onChange={(event) =>
                                        form.setData(
                                            'start_month',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="end_month">Bis</Label>
                                <Input
                                    id="end_month"
                                    type="month"
                                    disabled={form.data.is_current}
                                    value={form.data.end_month}
                                    onChange={(event) =>
                                        form.setData(
                                            'end_month',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-6">
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={form.data.is_current}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'is_current',
                                            checked === true,
                                        )
                                    }
                                />
                                Läuft noch
                            </label>
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
                                Im Lebenslauf sichtbar
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
                            <p className="text-muted-foreground text-xs">
                                Beide Sprachen schreibst du selbst. Fehlt eine,
                                greift beim Drucken feldweise die andere —
                                übersetzt wird nichts.
                            </p>

                            <div className="grid gap-2">
                                <Label htmlFor="headline">
                                    {config.headlineLabel}
                                </Label>
                                <Input
                                    id="headline"
                                    value={toText(block[config.headlineField])}
                                    onChange={(event) =>
                                        setTranslation(
                                            config.headlineField,
                                            event.target.value,
                                        )
                                    }
                                />
                                {headlineError && (
                                    <p className="text-destructive text-sm">
                                        {headlineError}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="location">Ort</Label>
                                <Input
                                    id="location"
                                    value={toText(block.location)}
                                    onChange={(event) =>
                                        setTranslation(
                                            'location',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="list">{config.listLabel}</Label>
                                <Textarea
                                    id="list"
                                    rows={6}
                                    value={toText(block[config.listField])}
                                    onChange={(event) =>
                                        setTranslation(
                                            config.listField,
                                            event.target.value,
                                        )
                                    }
                                />
                                <p className="text-muted-foreground text-xs">
                                    {config.listHint}
                                </p>
                            </div>
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
        </div>
    );
}
