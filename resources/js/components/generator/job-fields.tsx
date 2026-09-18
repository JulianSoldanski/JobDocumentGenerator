import { Sparkles } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

export type JobFieldValues = {
    company: string;
    position: string;
    contact_person: string;
    city: string;
    company_address: string;
};

type Props = {
    values: JobFieldValues;
    onChange: (field: keyof JobFieldValues, value: string) => void;
    onBlur: () => void;
    onExtract: () => void;
    extracting: boolean;
    error: string | null;
    /** Was der letzte Aufruf tatsächlich gefüllt hat. */
    filled: (keyof JobFieldValues)[];
    hasPosting: boolean;
};

const LABELS: Record<keyof JobFieldValues, string> = {
    company: 'Unternehmen',
    position: 'Position',
    contact_person: 'Ansprechpartner',
    city: 'Ort',
    company_address: 'Postanschrift',
};

/**
 * Die Eckdaten der Stelle. „Felder ausfüllen" schreibt nur in leere Felder —
 * was hier von Hand steht, bleibt stehen.
 */
export function JobFields({
    values,
    onChange,
    onBlur,
    onExtract,
    extracting,
    error,
    filled,
    hasPosting,
}: Props) {
    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p className="font-medium">Eckdaten</p>
                    <p className="text-muted-foreground text-sm">
                        Sie stehen später im Briefkopf und in der Anrede.
                    </p>
                </div>

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={onExtract}
                    disabled={extracting || !hasPosting}
                >
                    {extracting ? (
                        <Spinner className="h-4 w-4" />
                    ) : (
                        <Sparkles className="h-4 w-4" />
                    )}
                    Felder ausfüllen
                </Button>
            </div>

            {!hasPosting && (
                <p className="text-muted-foreground text-sm">
                    Füge zuerst die Anzeige ein oder lade sie über die Adresse —
                    ohne sie gibt es nichts auszulesen.
                </p>
            )}

            {error && <p className="text-destructive text-sm">{error}</p>}

            {filled.length > 0 && (
                <p className="text-muted-foreground text-sm">
                    Ausgefüllt:{' '}
                    {filled.map((field) => LABELS[field]).join(', ')}. Was schon
                    dastand, blieb unangetastet.
                </p>
            )}

            <div className="grid gap-4 sm:grid-cols-2">
                {(
                    ['company', 'position', 'contact_person', 'city'] as const
                ).map((field) => (
                    <div key={field} className="grid gap-2">
                        <Label htmlFor={field}>{LABELS[field]}</Label>
                        <Input
                            id={field}
                            value={values[field]}
                            onChange={(event) =>
                                onChange(field, event.target.value)
                            }
                            onBlur={onBlur}
                        />
                    </div>
                ))}
            </div>

            <div className="grid gap-2">
                <Label htmlFor="company_address">
                    {LABELS.company_address}
                </Label>
                <Textarea
                    id="company_address"
                    rows={2}
                    value={values.company_address}
                    placeholder={'Musterstraße 1\n12345 Musterstadt'}
                    onChange={(event) =>
                        onChange('company_address', event.target.value)
                    }
                    onBlur={onBlur}
                />
            </div>
        </div>
    );
}
