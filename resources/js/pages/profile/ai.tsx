import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, PlugZap, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { AiRequestError } from '@/lib/ai';
import {
    destroy,
    test,
    update,
} from '@/actions/App/Http/Controllers/Profile/AiAccessController';

type Access = {
    provider: string;
    model: string;
    has_key: boolean;
    key_hint: string | null;
    verified_at: string | null;
    uses_server_key: boolean;
};

const PROVIDER_HINTS: Record<string, string> = {
    gemini: 'z. B. gemini-2.5-flash',
    anthropic: 'z. B. claude-sonnet-5',
    openai: 'z. B. gpt-5',
    mistral: 'z. B. mistral-large-latest',
    openrouter: 'z. B. google/gemini-2.5-flash',
    ollama: 'z. B. llama3.2 (läuft lokal, ohne Schlüssel)',
};

export default function Ai({
    access,
    providers,
}: {
    access: Access;
    providers: string[];
}) {
    const form = useForm({
        provider: access.provider,
        model: access.model,
        api_key: '',
    });

    const [testing, setTesting] = useState(false);
    const [testResult, setTestResult] = useState<{
        ok: boolean;
        message: string;
    } | null>(null);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.put(update.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.setData('api_key', '');
                setTestResult(null);
            },
        });
    };

    const testConnection = async () => {
        setTesting(true);
        setTestResult(null);

        try {
            const response = await fetch(test.url(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie.match(
                            /(?:^|;\s*)XSRF-TOKEN=([^;]+)/,
                        )?.[1] ?? '',
                    ),
                },
            });
            const payload = await response.json();
            setTestResult({
                ok: response.ok && payload.ok === true,
                message: payload.message ?? 'Unbekannte Antwort.',
            });
        } catch (error) {
            setTestResult({
                ok: false,
                message:
                    error instanceof AiRequestError
                        ? error.message
                        : 'Der Test ließ sich nicht ausführen.',
            });
        } finally {
            setTesting(false);
        }
    };

    const removeKey = () => {
        if (confirm('Den hinterlegten Schlüssel wirklich entfernen?')) {
            router.delete(destroy.url(), { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="KI-Zugang" />

            <div className="max-w-xl space-y-6">
                <Heading
                    variant="small"
                    title="KI-Zugang"
                    description="Anbieter, Modell und dein eigener Schlüssel. Er wird verschlüsselt gespeichert und nie wieder angezeigt."
                />

                <div className="flex items-start gap-3 rounded-lg border p-4 text-sm">
                    <KeyRound className="mt-0.5 h-4 w-4 shrink-0" />
                    <div>
                        {access.has_key ? (
                            <>
                                <p className="font-medium">
                                    Schlüssel hinterlegt ({access.key_hint})
                                </p>
                                <p className="text-muted-foreground">
                                    {access.verified_at
                                        ? `Zuletzt erfolgreich geprüft am ${new Date(access.verified_at).toLocaleDateString('de-DE')}.`
                                        : 'Noch nicht geprüft.'}
                                </p>
                            </>
                        ) : access.uses_server_key ? (
                            <>
                                <p className="font-medium">
                                    Kein eigener Schlüssel
                                </p>
                                <p className="text-muted-foreground">
                                    Es greift der Schlüssel aus der
                                    Serverkonfiguration.
                                </p>
                            </>
                        ) : (
                            <>
                                <p className="font-medium">
                                    Noch kein Schlüssel hinterlegt
                                </p>
                                <p className="text-muted-foreground">
                                    Ohne Schlüssel bleiben Stilanalyse,
                                    Generator und PDF-Import stumm.
                                </p>
                            </>
                        )}
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="provider">Anbieter</Label>
                        <Select
                            value={form.data.provider}
                            onValueChange={(value) =>
                                form.setData('provider', value)
                            }
                        >
                            <SelectTrigger id="provider">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {providers.map((provider) => (
                                    <SelectItem key={provider} value={provider}>
                                        {provider}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {form.errors.provider && (
                            <p className="text-destructive text-sm">
                                {form.errors.provider}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="model">Modell</Label>
                        <Input
                            id="model"
                            value={form.data.model}
                            onChange={(event) =>
                                form.setData('model', event.target.value)
                            }
                            placeholder={
                                PROVIDER_HINTS[form.data.provider] ?? ''
                            }
                        />
                        {form.errors.model && (
                            <p className="text-destructive text-sm">
                                {form.errors.model}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="api_key">API-Schlüssel</Label>
                        <Input
                            id="api_key"
                            type="password"
                            autoComplete="off"
                            value={form.data.api_key}
                            onChange={(event) =>
                                form.setData('api_key', event.target.value)
                            }
                            placeholder={
                                access.has_key
                                    ? 'Leer lassen, um den vorhandenen zu behalten'
                                    : 'Schlüssel einfügen'
                            }
                        />
                        {form.errors.api_key && (
                            <p className="text-destructive text-sm">
                                {form.errors.api_key}
                            </p>
                        )}
                        <p className="text-muted-foreground text-xs">
                            Die Aufrufe laufen über dein Konto beim Anbieter und
                            werden dir dort berechnet.
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Speichern
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            onClick={testConnection}
                            disabled={testing}
                        >
                            {testing ? (
                                <Spinner className="h-4 w-4" />
                            ) : (
                                <PlugZap className="h-4 w-4" />
                            )}
                            Verbindung testen
                        </Button>

                        {access.has_key && (
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={removeKey}
                            >
                                <Trash2 className="h-4 w-4" />
                                Schlüssel entfernen
                            </Button>
                        )}
                    </div>
                </form>

                {testResult && (
                    <p
                        className={
                            testResult.ok
                                ? 'text-sm text-emerald-600 dark:text-emerald-400'
                                : 'text-destructive text-sm'
                        }
                    >
                        {testResult.message}
                    </p>
                )}
            </div>
        </>
    );
}
