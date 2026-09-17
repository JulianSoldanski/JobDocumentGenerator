import { router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Check, Pencil, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import { MissingLanguageBadge } from '@/components/profile/missing-language-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    destroy,
    reorder,
    store,
    update,
} from '@/actions/App/Http/Controllers/Profile/ProfileEntryController';
import type { ProfileEntry, ProfileSection } from '@/types';

type Props = {
    section: Extract<ProfileSection, 'hard_skill' | 'soft_skill' | 'language'>;
    title: string;
    description: string;
    entries: ProfileEntry[];
    /** Sprachen tragen zusätzlich ein Niveau. */
    withLevel?: boolean;
};

type FormData = {
    section: string;
    translations: {
        de: { name: string; level?: string };
        en: { name: string; level?: string };
    };
};

const text = (value: string | string[] | undefined): string =>
    Array.isArray(value) ? value.join(' ') : (value ?? '');

const emptyData = (section: string, withLevel: boolean): FormData => ({
    section,
    translations: withLevel
        ? { de: { name: '', level: '' }, en: { name: '', level: '' } }
        : { de: { name: '' }, en: { name: '' } },
});

/**
 * Eine geordnete Liste — die Reihenfolge ist die, in der die Einträge auf dem
 * Dokument stehen, solange die KI keine andere wählt.
 */
export function SkillList({
    section,
    title,
    description,
    entries,
    withLevel = false,
}: Props) {
    const [editing, setEditing] = useState<ProfileEntry | null>(null);
    const form = useForm<FormData>(emptyData(section, withLevel));

    const reset = () => {
        setEditing(null);
        form.setData(emptyData(section, withLevel));
        form.clearErrors();
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: () => reset() };

        if (editing) {
            form.patch(update.url(editing.id), options);
        } else {
            form.post(store.url(), options);
        }
    };

    const edit = (entry: ProfileEntry) => {
        setEditing(entry);
        form.clearErrors();
        form.setData({
            section,
            translations: {
                de: {
                    name: text(entry.translations.de?.name),
                    ...(withLevel
                        ? { level: text(entry.translations.de?.level) }
                        : {}),
                },
                en: {
                    name: text(entry.translations.en?.name),
                    ...(withLevel
                        ? { level: text(entry.translations.en?.level) }
                        : {}),
                },
            },
        });
    };

    const move = (entry: ProfileEntry, direction: -1 | 1) => {
        const ids = entries.map((item) => item.id);
        const index = ids.indexOf(entry.id);
        const target = index + direction;

        if (target < 0 || target >= ids.length) {
            return;
        }

        [ids[index], ids[target]] = [ids[target], ids[index]];

        router.post(
            reorder.url(),
            { section, ids },
            { preserveScroll: true, preserveState: false },
        );
    };

    const remove = (entry: ProfileEntry) => {
        router.delete(destroy.url(entry.id), { preserveScroll: true });
    };

    const setField = (
        language: 'de' | 'en',
        field: 'name' | 'level',
        value: string,
    ) => {
        form.setData('translations', {
            ...form.data.translations,
            [language]: { ...form.data.translations[language], [field]: value },
        });
    };

    return (
        <section className="space-y-3">
            <div>
                <h3 className="font-medium">{title}</h3>
                <p className="text-muted-foreground text-sm">{description}</p>
            </div>

            {entries.length > 0 && (
                <ul className="divide-y rounded-lg border">
                    {entries.map((entry, index) => (
                        <li
                            key={entry.id}
                            className="flex items-center justify-between gap-3 px-3 py-2"
                        >
                            <div className="flex min-w-0 flex-wrap items-center gap-2 text-sm">
                                <span>
                                    {text(entry.translations.de?.name) || '—'}
                                </span>
                                <span className="text-muted-foreground">·</span>
                                <span className="text-muted-foreground">
                                    {text(entry.translations.en?.name) || '—'}
                                </span>
                                {withLevel && (
                                    <span className="text-muted-foreground text-xs">
                                        (
                                        {text(entry.translations.de?.level) ||
                                            text(
                                                entry.translations.en?.level,
                                            ) ||
                                            'ohne Niveau'}
                                        )
                                    </span>
                                )}
                                <MissingLanguageBadge
                                    missing={entry.missing_languages}
                                />
                            </div>

                            <div className="flex shrink-0">
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    title="Nach oben"
                                    disabled={index === 0}
                                    onClick={() => move(entry, -1)}
                                >
                                    <ArrowUp className="h-4 w-4" />
                                </Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    title="Nach unten"
                                    disabled={index === entries.length - 1}
                                    onClick={() => move(entry, 1)}
                                >
                                    <ArrowDown className="h-4 w-4" />
                                </Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    title="Bearbeiten"
                                    onClick={() => edit(entry)}
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

            <form
                onSubmit={submit}
                className="flex flex-wrap items-end gap-2 rounded-lg border border-dashed p-3"
            >
                <div className="grid min-w-40 flex-1 gap-1">
                    <Label className="text-xs">Deutsch</Label>
                    <Input
                        value={form.data.translations.de.name}
                        onChange={(event) =>
                            setField('de', 'name', event.target.value)
                        }
                        placeholder={withLevel ? 'Deutsch' : 'z. B. Python'}
                    />
                </div>

                {withLevel && (
                    <div className="grid w-32 gap-1">
                        <Label className="text-xs">Niveau (DE)</Label>
                        <Input
                            value={form.data.translations.de.level ?? ''}
                            onChange={(event) =>
                                setField('de', 'level', event.target.value)
                            }
                            placeholder="Muttersprache"
                        />
                    </div>
                )}

                <div className="grid min-w-40 flex-1 gap-1">
                    <Label className="text-xs">Englisch</Label>
                    <Input
                        value={form.data.translations.en.name}
                        onChange={(event) =>
                            setField('en', 'name', event.target.value)
                        }
                        placeholder={withLevel ? 'German' : 'z. B. Python'}
                    />
                </div>

                {withLevel && (
                    <div className="grid w-32 gap-1">
                        <Label className="text-xs">Niveau (EN)</Label>
                        <Input
                            value={form.data.translations.en.level ?? ''}
                            onChange={(event) =>
                                setField('en', 'level', event.target.value)
                            }
                            placeholder="Native"
                        />
                    </div>
                )}

                <Button type="submit" size="sm" disabled={form.processing}>
                    {editing ? (
                        <>
                            <Check className="h-4 w-4" /> Übernehmen
                        </>
                    ) : (
                        'Hinzufügen'
                    )}
                </Button>

                {editing && (
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        onClick={reset}
                    >
                        <X className="h-4 w-4" />
                    </Button>
                )}
            </form>

            {form.errors['translations.de.name' as never] && (
                <p className="text-destructive text-sm">
                    {form.errors['translations.de.name' as never]}
                </p>
            )}
        </section>
    );
}
