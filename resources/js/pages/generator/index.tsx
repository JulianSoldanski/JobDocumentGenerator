import { Head, router } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState } from 'react';
import { DocumentPanel } from '@/components/generator/document-panel';
import { GenerationSettings } from '@/components/generator/generation-settings';
import {
    JobFields,
    type JobFieldValues,
} from '@/components/generator/job-fields';
import { JobSummaryPanel } from '@/components/generator/job-summary';
import { PostingInput } from '@/components/generator/posting-input';
import { Spinner } from '@/components/ui/spinner';
import { useAiTask } from '@/hooks/use-ai-task';
import { postJson, RequestError } from '@/lib/http';
import { store as extractFields } from '@/actions/App/Http/Controllers/Generator/ExtractFieldsController';
import { store as generateDocuments } from '@/actions/App/Http/Controllers/Generator/GenerateController';
import { store as createSummary } from '@/actions/App/Http/Controllers/Generator/JobSummaryController';
import { update } from '@/actions/App/Http/Controllers/Generator/SessionController';
import type {
    DocumentLayout,
    DocumentVersion,
    GenerationScope,
    GeneratorSession,
    JobSummary,
    Language,
    SessionDocuments,
} from '@/types';

type SessionForm = JobFieldValues & {
    job_url: string;
    job_posting: string;
    language: Language;
    layout: DocumentLayout;
    scope: GenerationScope;
    notes: string;
};

type FieldsResult = {
    fields: JobFieldValues;
    found: JobFieldValues;
    filled: (keyof JobFieldValues)[];
};

type GenerateResponse = {
    tasks: Record<'cv' | 'letter' | 'summary', string | null>;
};

/**
 * Der Arbeitsplatz: links die Stelle, rechts das Dokument.
 *
 * Gespeichert wird beiläufig — beim Verlassen eines Feldes und nach jeder
 * Auswahl. Wer an einer Bewerbung sitzt, soll nicht an einen Speichern-Knopf
 * denken müssen.
 */
export default function Index({ session }: { session: GeneratorSession }) {
    const [data, setData] = useState<SessionForm>({
        job_url: session.job_url,
        job_posting: session.job_posting,
        company: session.company,
        position: session.position,
        contact_person: session.contact_person,
        city: session.city,
        company_address: session.company_address,
        language: session.language,
        layout: session.layout,
        scope: session.scope,
        notes: session.notes,
    });

    const [status, setStatus] = useState<'clean' | 'saving' | 'saved'>('clean');
    const [error, setError] = useState<string | null>(null);
    const [summary, setSummary] = useState<JobSummary | null>(session.summary);
    const [filled, setFilled] = useState<(keyof JobFieldValues)[]>([]);
    const [documents, setDocuments] = useState<SessionDocuments>(
        session.documents,
    );
    const [starting, setStarting] = useState(false);
    const [generateError, setGenerateError] = useState<string | null>(null);

    const change = (values: Partial<SessionForm>) => {
        setData((current) => ({ ...current, ...values }));
        setStatus('clean');
    };

    /**
     * Was gerade geändert wurde, kommt als Argument mit: Ein Zustand aus
     * `useState` ist erst im nächsten Render zu sehen, gespeichert wird aber
     * jetzt. Das Ergebnis sagt, ob der Server die Daten angenommen hat.
     */
    const save = (values: Partial<SessionForm> = {}) =>
        new Promise<boolean>((resolve) => {
            setStatus('saving');

            router.patch(
                update.url({ session: session.id }),
                { ...data, ...values },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        setStatus('saved');
                        setError(null);
                        resolve(true);
                    },
                    onError: (errors) => {
                        setStatus('clean');
                        setError(Object.values(errors)[0] ?? null);
                        resolve(false);
                    },
                    onCancel: () => resolve(false),
                },
            );
        });

    // Beide Aufgaben speichern ihr Ergebnis selbst; hier wird nur nachgezogen,
    // was der Nutzer sieht.
    const fields = useAiTask<FieldsResult>((result) => {
        change(result.fields);
        setFilled(result.filled);
    });

    const overview = useAiTask<{ summary: JobSummary }>((result) =>
        setSummary(result.summary),
    );

    const cv = useAiTask<{ document: DocumentVersion }>((result) =>
        setDocuments((current) => ({ ...current, cv: result.document })),
    );

    const letter = useAiTask<{ document: DocumentVersion }>((result) =>
        setDocuments((current) => ({ ...current, letter: result.document })),
    );

    /**
     * Erst speichern, dann generieren: Wer einen Hinweis tippt und direkt auf
     * „Generieren" klickt, soll ihn im Dokument wiederfinden.
     */
    const generate = async () => {
        setStarting(true);
        setGenerateError(null);

        try {
            if (!(await save())) {
                return;
            }

            const { tasks } = await postJson<GenerateResponse>(
                generateDocuments.url({ session: session.id }),
                {},
            );

            if (tasks.cv) {
                cv.track(tasks.cv);
            }

            if (tasks.letter) {
                letter.track(tasks.letter);
            }

            if (tasks.summary) {
                overview.track(tasks.summary);
            }
        } catch (problem) {
            setGenerateError(
                problem instanceof RequestError
                    ? problem.message
                    : 'Das Generieren ließ sich nicht starten.',
            );
        } finally {
            setStarting(false);
        }
    };

    const hasPosting = data.job_posting.trim() !== '';

    return (
        <>
            <Head title="Generator" />

            {/*
                Links eine schmale Spalte für die Stelle, rechts der ganze Rest
                für das Dokument. Ab Desktopbreite füllt der Arbeitsplatz genau
                den Bildschirm unter dem Menü, und beide Seiten scrollen für
                sich — das Dokument bleibt stehen, während links gearbeitet wird.
            */}
            <div className="lg:grid lg:h-[calc(100dvh-3.5rem)] lg:grid-cols-[400px_minmax(0,1fr)] xl:grid-cols-[440px_minmax(0,1fr)]">
                <aside className="space-y-8 px-4 py-5 lg:overflow-y-auto lg:border-r">
                    <div className="flex items-center justify-between gap-2">
                        <p className="font-medium">Stelle</p>

                        {status !== 'clean' && (
                            <p className="text-muted-foreground flex items-center gap-1.5 text-sm">
                                {status === 'saving' ? (
                                    <>
                                        <Spinner className="h-4 w-4" />
                                        Speichern
                                    </>
                                ) : (
                                    <>
                                        <Check className="h-4 w-4" />
                                        Gespeichert
                                    </>
                                )}
                            </p>
                        )}
                    </div>

                    {error && (
                        <p className="text-destructive -mt-6 text-sm">
                            {error}
                        </p>
                    )}

                    <PostingInput
                        sessionId={session.id}
                        url={data.job_url}
                        posting={data.job_posting}
                        onUrlChange={(value) => change({ job_url: value })}
                        onPostingChange={(value) =>
                            change({ job_posting: value })
                        }
                        onLoaded={(posting) => change({ job_posting: posting })}
                        onBlur={() => void save()}
                    />

                    <JobFields
                        values={data}
                        onChange={(field, value) => change({ [field]: value })}
                        onBlur={() => void save()}
                        onExtract={() =>
                            void fields.start(
                                extractFields.url({ session: session.id }),
                                {},
                            )
                        }
                        extracting={fields.running}
                        error={fields.error}
                        filled={filled}
                        hasPosting={hasPosting}
                    />

                    <GenerationSettings
                        language={data.language}
                        layout={data.layout}
                        scope={data.scope}
                        notes={data.notes}
                        onSelect={(values) => {
                            change(values);
                            void save(values);
                        }}
                        onNotesChange={(value) => change({ notes: value })}
                        onBlur={() => void save()}
                    />

                    <JobSummaryPanel
                        summary={summary}
                        posting={data.job_posting}
                        onCreate={() =>
                            void overview.start(
                                createSummary.url({ session: session.id }),
                                {},
                            )
                        }
                        creating={overview.running}
                        error={overview.error}
                        hasPosting={hasPosting}
                    />
                </aside>

                <section className="px-4 py-5 lg:min-h-0">
                    <DocumentPanel
                        scope={data.scope}
                        layout={session.layout}
                        documents={documents}
                        running={{ cv: cv.running, letter: letter.running }}
                        errors={[generateError, cv.error, letter.error].filter(
                            (message): message is string => message !== null,
                        )}
                        starting={starting}
                        hasPosting={hasPosting}
                        onGenerate={() => void generate()}
                        onSaved={(kind, saved) =>
                            setDocuments((current) => ({
                                ...current,
                                [kind]: saved,
                            }))
                        }
                    />
                </section>
            </div>
        </>
    );
}
