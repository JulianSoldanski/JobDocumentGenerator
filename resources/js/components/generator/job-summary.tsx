import { ChevronDown, Sparkles } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Spinner } from '@/components/ui/spinner';
import type { JobSummary as Summary } from '@/types';

type Props = {
    summary: Summary | null;
    posting: string;
    onCreate: () => void;
    creating: boolean;
    error: string | null;
    hasPosting: boolean;
};

/**
 * Die Übersicht bleibt stehen, während rechts am Dokument gearbeitet wird —
 * darunter ausklappbar der bereinigte Originaltext, falls doch ein Detail
 * fehlt.
 */
export function JobSummaryPanel({
    summary,
    posting,
    onCreate,
    creating,
    error,
    hasPosting,
}: Props) {
    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p className="font-medium">Stellen-Übersicht</p>
                    <p className="text-muted-foreground text-sm">
                        Damit beim Schreiben niemand die ganze Anzeige noch
                        einmal lesen muss.
                    </p>
                </div>

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={onCreate}
                    disabled={creating || !hasPosting}
                >
                    {creating ? (
                        <Spinner className="h-4 w-4" />
                    ) : (
                        <Sparkles className="h-4 w-4" />
                    )}
                    {summary ? 'Neu erstellen' : 'Übersicht erstellen'}
                </Button>
            </div>

            {!hasPosting && (
                <p className="text-muted-foreground text-sm">
                    Füge zuerst die Anzeige ein oder lade sie über die Adresse —
                    ohne sie gibt es nichts zusammenzufassen.
                </p>
            )}

            {error && <p className="text-destructive text-sm">{error}</p>}

            {summary && (
                <div className="space-y-4 rounded-lg border p-4">
                    <Block title="Das Unternehmen" text={summary.company} />
                    <Block title="Gesucht wird" text={summary.role} />

                    <div className="space-y-2">
                        <p className="text-sm font-medium">Technologien</p>
                        {summary.technologies.length > 0 ? (
                            <div className="flex flex-wrap gap-1.5">
                                {summary.technologies.map((technology) => (
                                    <Badge key={technology} variant="secondary">
                                        {technology}
                                    </Badge>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                In der Anzeige stand keine.
                            </p>
                        )}
                    </div>
                </div>
            )}

            {posting !== '' && (
                <Collapsible>
                    <CollapsibleTrigger className="text-muted-foreground hover:text-foreground flex items-center gap-1 text-sm">
                        <ChevronDown className="h-4 w-4" />
                        Originaltext der Anzeige
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <pre className="text-muted-foreground mt-2 max-h-96 overflow-auto rounded-lg border p-4 text-xs whitespace-pre-wrap">
                            {posting}
                        </pre>
                    </CollapsibleContent>
                </Collapsible>
            )}
        </div>
    );
}

function Block({ title, text }: { title: string; text: string }) {
    if (text === '') {
        return null;
    }

    return (
        <div className="space-y-1">
            <p className="text-sm font-medium">{title}</p>
            <p className="text-muted-foreground text-sm">{text}</p>
        </div>
    );
}
