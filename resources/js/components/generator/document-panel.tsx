import { ExternalLink, FileText, Pencil, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { CvEditor } from '@/components/generator/cv-editor';
import {
    DocumentPreview,
    type PreviewFit,
} from '@/components/generator/document-preview';
import { LetterEditor } from '@/components/generator/letter-editor';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { preview } from '@/actions/App/Http/Controllers/DocumentController';
import type {
    DocumentLayout,
    DocumentVersion,
    GenerationScope,
    SessionDocuments,
} from '@/types';

type Kind = 'cv' | 'letter';

const LABELS: Record<Kind, string> = {
    cv: 'Lebenslauf',
    letter: 'Anschreiben',
};

const PROGRESS: Record<Kind, string> = {
    cv: 'Der Lebenslauf wird aus deinem Profil zusammengesetzt.',
    letter: 'Das Anschreiben wird geschrieben.',
};

const IN_SCOPE: Record<GenerationScope, Kind[]> = {
    both: ['cv', 'letter'],
    cv: ['cv'],
    letter: ['letter'],
};

const FITS: Record<PreviewFit, string> = {
    width: 'Seitenbreite',
    page: 'Ganze Seite',
};

type Props = {
    scope: GenerationScope;
    /** Die gespeicherte Auswahl — die Vorschau folgt ihr erst, wenn der Server sie hat. */
    layout: DocumentLayout;
    documents: SessionDocuments;
    running: Record<Kind, boolean>;
    errors: string[];
    starting: boolean;
    hasPosting: boolean;
    onGenerate: () => void;
    onSaved: (kind: Kind, document: DocumentVersion) => void;
};

/**
 * Die rechte Seite gehört dem Dokument: oben eine Leiste mit allem, was man
 * damit tun kann, darunter das Dokument selbst über die volle Höhe.
 */
export function DocumentPanel({
    scope,
    layout,
    documents,
    running,
    errors,
    starting,
    hasPosting,
    onGenerate,
    onSaved,
}: Props) {
    const available = (['cv', 'letter'] as const).filter(
        (kind) => documents[kind],
    );
    const [chosen, setChosen] = useState<Kind>('cv');
    const [editing, setEditing] = useState(false);
    const [fit, setFit] = useState<PreviewFit>('width');
    const active = available.includes(chosen) ? chosen : available[0];

    const busy = starting || running.cv || running.letter;
    const document = active ? documents[active] : undefined;

    // Die Adresse ändert sich mit jeder Fassung und jedem Layout — so lädt
    // der Rahmen neu, statt die alte Seite zu zeigen.
    const src = document
        ? preview.url(
              { document: document.id },
              { query: { v: `${document.version}-${layout}` } },
          )
        : null;

    /**
     * Neu generieren ersetzt das Dokument — Handarbeit daran ginge verloren.
     */
    const generate = () => {
        const edited = IN_SCOPE[scope].filter(
            (kind) => documents[kind]?.edited,
        );

        if (
            edited.length > 0 &&
            !window.confirm(
                `Neu generieren ersetzt deine Änderungen am ${edited.map((kind) => LABELS[kind]).join(' und ')}. Trotzdem fortfahren?`,
            )
        ) {
            return;
        }

        onGenerate();
    };

    return (
        <div className="flex flex-col gap-3 lg:h-full lg:min-h-0">
            <div className="flex shrink-0 flex-wrap items-center gap-2">
                {available.length > 0 && !editing ? (
                    <Segmented
                        options={available.map((kind) => ({
                            value: kind,
                            label: LABELS[kind],
                        }))}
                        value={active}
                        onChange={setChosen}
                    />
                ) : (
                    <p className="font-medium">
                        {editing && active
                            ? `${LABELS[active]} bearbeiten`
                            : 'Dokument'}
                    </p>
                )}

                <div className="ml-auto flex flex-wrap items-center gap-2">
                    {document && src && !editing && (
                        <>
                            <div className="hidden lg:block">
                                <Segmented
                                    options={(['width', 'page'] as const).map(
                                        (value) => ({
                                            value,
                                            label: FITS[value],
                                        }),
                                    )}
                                    value={fit}
                                    onChange={setFit}
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setEditing(true)}
                                disabled={busy}
                            >
                                <Pencil className="h-4 w-4" />
                                Bearbeiten
                            </Button>
                            <Button variant="ghost" size="sm" asChild>
                                <a href={src} target="_blank" rel="noreferrer">
                                    <ExternalLink className="h-4 w-4" />
                                    In neuem Tab
                                </a>
                            </Button>
                        </>
                    )}

                    <Button
                        type="button"
                        size="sm"
                        onClick={generate}
                        disabled={busy || editing || !hasPosting}
                    >
                        {busy ? (
                            <Spinner className="h-4 w-4" />
                        ) : (
                            <Sparkles className="h-4 w-4" />
                        )}
                        {available.length > 0 ? 'Neu generieren' : 'Generieren'}
                    </Button>
                </div>
            </div>

            {!hasPosting && (
                <p className="text-muted-foreground shrink-0 text-sm">
                    Füge links zuerst die Anzeige ein oder lade sie über die
                    Adresse.
                </p>
            )}

            {(['cv', 'letter'] as const)
                .filter((kind) => running[kind])
                .map((kind) => (
                    <p
                        key={kind}
                        className="text-muted-foreground flex shrink-0 items-center gap-2 text-sm"
                    >
                        <Spinner className="h-4 w-4" />
                        {PROGRESS[kind]}
                    </p>
                ))}

            {/* Scheitern beide Dokumente am selben Grund, reicht eine Meldung. */}
            {[...new Set(errors)].map((error) => (
                <p key={error} className="text-destructive shrink-0 text-sm">
                    {error}
                </p>
            ))}

            {document && src && active ? (
                editing ? (
                    <div className="lg:min-h-0 lg:flex-1 lg:overflow-y-auto">
                        {active === 'cv' ? (
                            <CvEditor
                                documentId={document.id}
                                onCancel={() => setEditing(false)}
                                onSaved={(saved) => {
                                    onSaved('cv', saved);
                                    setEditing(false);
                                }}
                            />
                        ) : (
                            <LetterEditor
                                documentId={document.id}
                                onCancel={() => setEditing(false)}
                                onSaved={(saved) => {
                                    onSaved('letter', saved);
                                    setEditing(false);
                                }}
                            />
                        )}
                    </div>
                ) : (
                    <DocumentPreview
                        key={src}
                        src={src}
                        title={LABELS[active]}
                        fit={fit}
                    />
                )
            ) : (
                !busy && (
                    <div className="text-muted-foreground flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed p-10 text-center text-sm lg:flex-1">
                        <FileText className="h-6 w-6" />
                        <p className="text-foreground font-medium">
                            Hier entsteht das Dokument
                        </p>
                        <p className="max-w-md">
                            Der Lebenslauf wird wörtlich aus deinem Profil
                            zusammengesetzt; die KI schreibt nur das Statement
                            und wählt Projekte und Skills aus. Das Anschreiben
                            folgt deinen Stilregeln.
                        </p>
                    </div>
                )
            )}
        </div>
    );
}

function Segmented<T extends string>({
    options,
    value,
    onChange,
}: {
    options: { value: T; label: string }[];
    value: T;
    onChange: (value: T) => void;
}) {
    return (
        <div className="bg-muted inline-flex rounded-lg p-1">
            {options.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    onClick={() => onChange(option.value)}
                    className={cn(
                        'rounded-md px-3 py-1 text-sm',
                        option.value === value
                            ? 'bg-background shadow-sm'
                            : 'text-muted-foreground',
                    )}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}
