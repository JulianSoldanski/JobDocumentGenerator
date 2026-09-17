import { Head, useForm } from '@inertiajs/react';
import { Plus, Sparkles, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useAiTask } from '@/hooks/use-ai-task';
import { store as analyze } from '@/actions/App/Http/Controllers/Profile/StyleAnalysisController';
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

    const analysis = useAiTask<{ rules: string[] }>();

    const analyse = () =>
        void analysis.start(analyze.url(), { example: form.data.example });

    /**
     * Die Regeln der KI ersetzen die Liste erst auf Zuruf — und gespeichert
     * sind sie damit noch nicht.
     */
    const adoptRules = () => {
        if (analysis.result) {
            form.setData('rules', analysis.result.rules);
            analysis.reset();
        }
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
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <Label>Stilregeln</Label>
                            <p className="text-muted-foreground text-sm">
                                Eine Regel je Zeile — Tonfall, Satzbau,
                                Wortwahl, Aufbau, Eigenheiten. Diese Liste
                                steuert das Generieren, nicht das Beispiel.
                            </p>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={analyse}
                            disabled={analysis.running}
                        >
                            {analysis.running ? (
                                <Spinner className="h-4 w-4" />
                            ) : (
                                <Sparkles className="h-4 w-4" />
                            )}
                            Stil analysieren
                        </Button>
                    </div>

                    {analysis.error && (
                        <p className="text-destructive text-sm">
                            {analysis.error}
                        </p>
                    )}

                    {analysis.result && (
                        <div className="bg-muted/40 space-y-3 rounded-lg border p-4">
                            <p className="text-sm font-medium">
                                Vorschlag der KI
                            </p>
                            <ul className="list-disc space-y-1 pl-5 text-sm">
                                {analysis.result.rules.map((rule, index) => (
                                    <li key={index}>{rule}</li>
                                ))}
                            </ul>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    onClick={adoptRules}
                                >
                                    Übernehmen
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onClick={analysis.reset}
                                >
                                    Verwerfen
                                </Button>
                            </div>
                            <p className="text-muted-foreground text-xs">
                                Übernehmen ersetzt deine Liste. Gespeichert wird
                                erst mit „Speichern".
                            </p>
                        </div>
                    )}

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
