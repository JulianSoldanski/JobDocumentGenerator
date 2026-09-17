import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { update } from '@/actions/App/Http/Controllers/Profile/WritingStyleController';
import type { WritingStyle } from '@/types';

/**
 * Prinzip 3: Aus dem Beispiel-Anschreiben destilliert die KI Stilregeln, die
 * hier korrigiert werden. Beim Generieren geht die Regelliste in den Prompt,
 * nicht das Beispiel.
 */
export default function Style({ style }: { style: WritingStyle }) {
    const form = useForm({
        example: style.example,
        rules: style.rules.length > 0 ? style.rules : [''],
    });

    const setRule = (index: number, value: string) => {
        const rules = [...form.data.rules];
        rules[index] = value;
        form.setData('rules', rules);
    };

    const addRule = () => form.setData('rules', [...form.data.rules, '']);

    const removeRule = (index: number) =>
        form.setData(
            'rules',
            form.data.rules.filter((_, position) => position !== index),
        );

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.put(update.url(), { preserveScroll: true });
    };

    return (
        <>
            <Head title="Schreibstil" />

            <form onSubmit={submit} className="max-w-2xl space-y-8">
                <Heading
                    variant="small"
                    title="Schreibstil"
                    description="Ein Anschreiben ist Fließtext für genau eine Stelle — hier steuerst du die Stimme, in der es geschrieben wird."
                />

                <div className="grid gap-2">
                    <Label htmlFor="example">Beispiel-Anschreiben</Label>
                    <Textarea
                        id="example"
                        rows={12}
                        value={form.data.example}
                        onChange={(event) =>
                            form.setData('example', event.target.value)
                        }
                        placeholder="Ein Anschreiben, das deine Stimme gut trifft."
                    />
                    <p className="text-muted-foreground text-xs">
                        {form.data.example.length} Zeichen. Das Beispiel dient
                        nur der Analyse; in den Prompt geht es nicht.
                    </p>
                </div>

                <div className="space-y-3">
                    <div>
                        <Label>Stilregeln</Label>
                        <p className="text-muted-foreground text-sm">
                            Eine Regel je Zeile — Tonfall, Satzbau, Wortwahl,
                            Aufbau, Eigenheiten. Diese Liste steuert das
                            Generieren.
                        </p>
                    </div>

                    <ul className="space-y-2">
                        {form.data.rules.map((rule, index) => (
                            <li key={index} className="flex gap-2">
                                <Input
                                    value={rule}
                                    onChange={(event) =>
                                        setRule(index, event.target.value)
                                    }
                                    placeholder="z. B. Kurze Hauptsätze, keine Floskeln."
                                />
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    title="Regel entfernen"
                                    onClick={() => removeRule(index)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </li>
                        ))}
                    </ul>

                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={addRule}
                    >
                        <Plus className="h-4 w-4" /> Regel hinzufügen
                    </Button>
                </div>

                <Button type="submit" disabled={form.processing}>
                    Speichern
                </Button>
            </form>
        </>
    );
}
