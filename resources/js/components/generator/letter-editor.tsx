import { Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { getJson, patchJson, RequestError } from '@/lib/http';
import {
    show,
    update,
} from '@/actions/App/Http/Controllers/DocumentController';
import type { DocumentVersion, LetterContent } from '@/types';

/** Ein Absatz, der nur aus einem Platzhalter wie „[WHY_US: …]" besteht. */
const isPlaceholder = (text: string) => /\[[^\]]+\]/.test(text);

type Props = {
    documentId: number;
    onSaved: (document: DocumentVersion) => void;
    onCancel: () => void;
};

/**
 * Das Anschreiben, Absatz für Absatz. Platzhalter aus den Stilregeln sind
 * markiert — sie sollen ersetzt werden, bevor der Brief rausgeht.
 */
export function LetterEditor({ documentId, onSaved, onCancel }: Props) {
    const [form, setForm] = useState<LetterContent | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        let active = true;

        getJson<{ content: LetterContent }>(show.url({ document: documentId }))
            .then(({ content }) => active && setForm(content))
            .catch(
                () =>
                    active &&
                    setError('Das Anschreiben ließ sich nicht laden.'),
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
                Anschreiben wird geladen.
            </p>
        );
    }

    const change = (values: Partial<LetterContent>) =>
        setForm((current) => (current ? { ...current, ...values } : current));

    const setParagraph = (index: number, text: string) => {
        const paragraphs = [...form.paragraphs];
        paragraphs[index] = text;
        change({ paragraphs });
    };

    const placeholders = form.paragraphs.filter(isPlaceholder).length;

    const save = async () => {
        setSaving(true);
        setError(null);

        try {
            onSaved(
                await patchJson<DocumentVersion>(
                    update.url({ document: documentId }),
                    {
                        subject: form.subject,
                        salutation: form.salutation,
                        paragraphs: form.paragraphs,
                        closing: form.closing,
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
        <div className="space-y-5 rounded-lg border p-4">
            {placeholders > 0 && (
                <p className="rounded-md border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm">
                    {placeholders === 1
                        ? 'Ein Absatz ist noch ein Platzhalter'
                        : `${placeholders} Absätze sind noch Platzhalter`}{' '}
                    — ersetze ihn, bevor der Brief rausgeht.
                </p>
            )}

            <div className="grid gap-2">
                <Label htmlFor="letter-subject">Betreff</Label>
                <Input
                    id="letter-subject"
                    value={form.subject}
                    onChange={(event) =>
                        change({ subject: event.target.value })
                    }
                />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="letter-salutation">Anrede</Label>
                <Input
                    id="letter-salutation"
                    value={form.salutation}
                    onChange={(event) =>
                        change({ salutation: event.target.value })
                    }
                />
            </div>

            <div className="space-y-3">
                <Label>Absätze</Label>
                {form.paragraphs.map((paragraph, index) => (
                    <div key={index} className="flex gap-2">
                        <Textarea
                            rows={3}
                            value={paragraph}
                            className={cn(
                                isPlaceholder(paragraph) &&
                                    'border-amber-500/60 bg-amber-500/5',
                            )}
                            onChange={(event) =>
                                setParagraph(index, event.target.value)
                            }
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label="Absatz entfernen"
                            onClick={() =>
                                change({
                                    paragraphs: form.paragraphs.filter(
                                        (_, position) => position !== index,
                                    ),
                                })
                            }
                        >
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </div>
                ))}
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() =>
                        change({ paragraphs: [...form.paragraphs, ''] })
                    }
                >
                    <Plus className="h-4 w-4" />
                    Absatz hinzufügen
                </Button>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="letter-closing">Grußformel</Label>
                <Input
                    id="letter-closing"
                    value={form.closing}
                    onChange={(event) =>
                        change({ closing: event.target.value })
                    }
                />
            </div>

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
