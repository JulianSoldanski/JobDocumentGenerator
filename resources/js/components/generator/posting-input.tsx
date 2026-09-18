import { Download, Link2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { fetch as fetchPosting } from '@/actions/App/Http/Controllers/Generator/JobPostingController';
import { postJson, RequestError } from '@/lib/http';

type Props = {
    sessionId: number;
    url: string;
    posting: string;
    onUrlChange: (value: string) => void;
    onPostingChange: (value: string) => void;
    /** Nach dem Laden steht der Text schon auf dem Server. */
    onLoaded: (posting: string) => void;
    onBlur: () => void;
};

/**
 * Die Anzeige kommt entweder als Text oder über ihre Adresse.
 *
 * Beides ist gleichwertig: Portale mit Anmeldung und Seiten, die ihre Anzeige
 * erst im Browser nachladen, geben nichts her — dann fügt man sie ein.
 */
export function PostingInput({
    sessionId,
    url,
    posting,
    onUrlChange,
    onPostingChange,
    onLoaded,
    onBlur,
}: Props) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const load = async () => {
        setLoading(true);
        setError(null);

        try {
            const { posting: loaded } = await postJson<{ posting: string }>(
                fetchPosting.url({ session: sessionId }),
                { url },
            );

            onLoaded(loaded);
        } catch (problem) {
            setError(
                problem instanceof RequestError
                    ? problem.message
                    : 'Die Seite ließ sich nicht laden.',
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="grid gap-2">
                <Label htmlFor="job_url">Adresse der Anzeige</Label>
                <div className="flex gap-2">
                    <div className="relative flex-1">
                        <Link2 className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                        <Input
                            id="job_url"
                            type="url"
                            className="pl-9"
                            value={url}
                            placeholder="https://…"
                            onChange={(event) =>
                                onUrlChange(event.target.value)
                            }
                            onBlur={onBlur}
                        />
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => void load()}
                        disabled={loading || url.trim() === ''}
                    >
                        {loading ? (
                            <Spinner className="h-4 w-4" />
                        ) : (
                            <Download className="h-4 w-4" />
                        )}
                        Laden
                    </Button>
                </div>
                {error && <p className="text-destructive text-sm">{error}</p>}
            </div>

            <div className="grid gap-2">
                <Label htmlFor="job_posting">Stellenanzeige</Label>
                {/* Das Feld wächst mit dem Text, aber nicht über eine Bildschirmhöhe
                    hinaus — sonst rutschen Eckdaten und Einstellungen aus dem
                    Blick. Ganz lesen lässt sich die Anzeige unten ausgeklappt. */}
                <Textarea
                    id="job_posting"
                    rows={12}
                    className="max-h-80 overflow-y-auto"
                    value={posting}
                    placeholder="Die Anzeige hier einfügen — oder oben die Adresse laden."
                    onChange={(event) => onPostingChange(event.target.value)}
                    onBlur={onBlur}
                />
                <p className="text-muted-foreground text-xs">
                    {posting.length > 0
                        ? `${posting.length} Zeichen. Genau dieser Text geht an die KI — korrigiere ihn, wenn beim Laden etwas danebenging.`
                        : 'Ohne Anzeige kann die KI weder Felder ausfüllen noch eine Übersicht erstellen.'}
                </p>
            </div>
        </div>
    );
}
