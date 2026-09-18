import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, FileText, RotateCcw } from 'lucide-react';
import { useState } from 'react';
import { Field } from '@/components/applications/field';
import { StageBadge } from '@/components/applications/stage-badge';
import { DocumentPreview } from '@/components/generator/document-preview';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { date, duration, since, stageLabel, today } from '@/lib/applications';
import { cn } from '@/lib/utils';
import {
    destroy,
    index,
    reactivate,
    stage as changeStage,
    update,
} from '@/actions/App/Http/Controllers/ApplicationController';
import { preview } from '@/actions/App/Http/Controllers/DocumentController';
import { show as openSession } from '@/actions/App/Http/Controllers/Generator/GeneratorController';
import type {
    Application,
    ApplicationStage,
    DocumentVersion,
    StageEvent,
    StageOption,
} from '@/types';

type Props = {
    application: Application;
    history: StageEvent[];
    documents: Partial<Record<'cv' | 'letter', DocumentVersion>>;
    session_id: number | null;
    stages: StageOption[];
};

/**
 * Eine Bewerbung: wo sie steht, wie sie dahin kam, und was verschickt wurde.
 */
export default function Show({
    application,
    history,
    documents,
    session_id,
    stages,
}: Props) {
    return (
        <>
            <Head title={`${application.company} · Bewerbung`} />

            <div className="space-y-6 px-4 py-6">
                <Link
                    href={index.url()}
                    className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-sm"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Alle Bewerbungen
                </Link>

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold">
                            {application.company}
                        </h1>
                        <p className="text-muted-foreground">
                            {application.position}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {application.job_url && (
                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={application.job_url}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <ExternalLink className="h-4 w-4" />
                                    Originalanzeige
                                </a>
                            </Button>
                        )}
                        {session_id && (
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={openSession.url({
                                        session: session_id,
                                    })}
                                >
                                    <FileText className="h-4 w-4" />
                                    Im Generator öffnen
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-8 xl:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                    <div className="space-y-8">
                        <StageSection
                            application={application}
                            history={history}
                            stages={stages}
                        />
                        <DetailsForm application={application} />
                    </div>

                    <Documents documents={documents} />
                </div>
            </div>
        </>
    );
}

function StageSection({
    application,
    history,
    stages,
}: {
    application: Application;
    history: StageEvent[];
    stages: StageOption[];
}) {
    const linear = stages.filter((stage) => stage.value !== 'rejected');
    const rejected = application.stage === 'rejected';
    const reached = linear.findIndex(
        (stage) =>
            stage.value ===
            (rejected ? application.highest_stage : application.stage),
    );

    const form = useForm({
        stage: (linear.find((_, position) => position === reached + 1)?.value ??
            'rejected') as ApplicationStage,
        date: today(),
    });

    const submit = (stage: ApplicationStage) => {
        form.transform((data) => ({ ...data, stage }));
        form.post(changeStage.url({ application: application.id }), {
            preserveScroll: true,
        });
    };

    return (
        <section className="space-y-4">
            <div className="flex items-center justify-between gap-2">
                <p className="font-medium">Stufe</p>
                <span className="flex items-center gap-2">
                    <StageBadge application={application} stages={stages} />
                    <span className="text-muted-foreground text-xs">
                        {since(application.stage_since)}
                    </span>
                </span>
            </div>

            {/* Der Weg bleibt sichtbar — auch nach einer Absage. */}
            <ol className="grid grid-cols-5 gap-1">
                {linear.map((stage, position) => (
                    <li key={stage.value} className="space-y-1.5">
                        <div
                            className={cn(
                                'h-1.5 rounded-full',
                                position <= reached
                                    ? rejected
                                        ? 'bg-muted-foreground'
                                        : 'bg-foreground'
                                    : 'bg-muted',
                            )}
                        />
                        <p
                            className={cn(
                                'text-xs',
                                position === reached
                                    ? 'font-medium'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {stage.label}
                        </p>
                    </li>
                ))}
            </ol>

            {rejected ? (
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() =>
                        router.post(
                            reactivate.url({ application: application.id }),
                            {},
                            { preserveScroll: true },
                        )
                    }
                >
                    <RotateCcw className="h-4 w-4" />
                    Reaktivieren
                </Button>
            ) : (
                <div className="space-y-2">
                    <div className="flex flex-wrap items-end gap-2">
                        <div className="grid gap-2">
                            <span className="text-sm">Neue Stufe</span>
                            <Select
                                value={form.data.stage}
                                onValueChange={(value) =>
                                    form.setData(
                                        'stage',
                                        value as ApplicationStage,
                                    )
                                }
                            >
                                <SelectTrigger className="w-40">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {linear
                                        .filter(
                                            (stage) =>
                                                stage.value !==
                                                application.stage,
                                        )
                                        .map((stage) => (
                                            <SelectItem
                                                key={stage.value}
                                                value={stage.value}
                                            >
                                                {stage.label}
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="grid gap-2">
                            <span className="text-sm">am</span>
                            <Input
                                type="date"
                                max={today()}
                                className="w-40"
                                value={form.data.date}
                                onChange={(event) =>
                                    form.setData('date', event.target.value)
                                }
                            />
                        </div>
                        <Button
                            size="sm"
                            className="h-9"
                            disabled={form.processing}
                            onClick={() => submit(form.data.stage)}
                        >
                            Übernehmen
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            className="h-9"
                            disabled={form.processing}
                            onClick={() => submit('rejected')}
                        >
                            Absage erhalten
                        </Button>
                    </div>
                    <InputError
                        message={form.errors.stage ?? form.errors.date}
                    />
                </div>
            )}

            <div className="space-y-2">
                <p className="text-sm font-medium">Verlauf</p>
                <ul className="space-y-1 text-sm">
                    {[...history].reverse().map((event) => (
                        <li key={event.id} className="flex gap-3">
                            <span className="text-muted-foreground w-24 shrink-0">
                                {date(event.occurred_at)}
                            </span>
                            <span>{stageLabel(stages, event.stage)}</span>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}

function DetailsForm({ application }: { application: Application }) {
    const form = useForm({
        company: application.company,
        position: application.position,
        job_url: application.job_url ?? '',
        applied_on: application.applied_on ?? '',
        feedback: application.feedback,
    });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.patch(update.url({ application: application.id }), {
                    preserveScroll: true,
                });
            }}
            className="space-y-4"
        >
            <p className="font-medium">Angaben</p>

            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Unternehmen" error={form.errors.company}>
                    <Input
                        value={form.data.company}
                        onChange={(event) =>
                            form.setData('company', event.target.value)
                        }
                    />
                </Field>
                <Field label="Position" error={form.errors.position}>
                    <Input
                        value={form.data.position}
                        onChange={(event) =>
                            form.setData('position', event.target.value)
                        }
                    />
                </Field>
                <Field label="Adresse der Anzeige" error={form.errors.job_url}>
                    <Input
                        type="url"
                        value={form.data.job_url}
                        onChange={(event) =>
                            form.setData('job_url', event.target.value)
                        }
                    />
                </Field>
                <Field label="Beworben am" error={form.errors.applied_on}>
                    <Input
                        type="date"
                        value={form.data.applied_on}
                        onChange={(event) =>
                            form.setData('applied_on', event.target.value)
                        }
                    />
                </Field>
            </div>

            <Field label="Feedback" error={form.errors.feedback}>
                <Textarea
                    rows={4}
                    value={form.data.feedback}
                    placeholder="Was im Gespräch kam, warum abgesagt wurde, was beim nächsten Mal anders sein soll."
                    onChange={(event) =>
                        form.setData('feedback', event.target.value)
                    }
                />
            </Field>

            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-muted-foreground text-sm">
                    Recherche:{' '}
                    {application.research_seconds > 0
                        ? duration(application.research_seconds)
                        : 'nicht gemessen'}
                </p>
                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        className="text-destructive"
                        onClick={() => {
                            if (
                                window.confirm(
                                    `Die Bewerbung bei ${application.company} samt Verlauf und Dokumenten löschen?`,
                                )
                            ) {
                                router.delete(
                                    destroy.url({
                                        application: application.id,
                                    }),
                                );
                            }
                        }}
                    >
                        Löschen
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {form.recentlySuccessful ? 'Gespeichert' : 'Speichern'}
                    </Button>
                </div>
            </div>
        </form>
    );
}

/**
 * Der Snapshot: was für genau diese Bewerbung erzeugt wurde — Monate später
 * noch nachvollziehbar.
 */
function Documents({ documents }: { documents: Props['documents'] }) {
    const available = (['cv', 'letter'] as const).filter(
        (kind) => documents[kind],
    );
    const [chosen, setChosen] = useState<'cv' | 'letter'>('cv');
    const active = available.includes(chosen) ? chosen : available[0];
    const document = active ? documents[active] : undefined;

    if (!document || !active) {
        return (
            <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-sm">
                Zu dieser Bewerbung gibt es keine Dokumente — sie ist außerhalb
                des Generators entstanden.
            </p>
        );
    }

    const src = preview.url(
        { document: document.id },
        { query: { v: String(document.version) } },
    );

    return (
        <section className="space-y-3">
            <div className="flex items-center justify-between gap-2">
                <div className="bg-muted inline-flex rounded-lg p-1">
                    {available.map((kind) => (
                        <button
                            key={kind}
                            type="button"
                            onClick={() => setChosen(kind)}
                            className={cn(
                                'rounded-md px-3 py-1 text-sm',
                                kind === active
                                    ? 'bg-background shadow-sm'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {kind === 'cv' ? 'Lebenslauf' : 'Anschreiben'}
                        </button>
                    ))}
                </div>
                <Button variant="ghost" size="sm" asChild>
                    <a href={src} target="_blank" rel="noreferrer">
                        <ExternalLink className="h-4 w-4" />
                        In neuem Tab
                    </a>
                </Button>
            </div>
            <DocumentPreview
                key={src}
                src={src}
                title={active === 'cv' ? 'Lebenslauf' : 'Anschreiben'}
                fit="width"
            />
        </section>
    );
}
