import { Head, Link, router, useForm } from '@inertiajs/react';
import { ExternalLink, Plus } from 'lucide-react';
import { useState } from 'react';
import { Field } from '@/components/applications/field';
import { StageMenu } from '@/components/applications/stage-menu';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { date, duration, since, today } from '@/lib/applications';
import {
    show,
    store,
} from '@/actions/App/Http/Controllers/ApplicationController';
import type { Application, ApplicationStage, StageOption } from '@/types';

type Props = {
    applications: Application[];
    stages: StageOption[];
};

/**
 * Der Tracker. Bewerbungen entstehen beim Generieren; laufende stehen oben,
 * die mit der jüngsten Bewegung zuerst.
 */
export default function Index({ applications, stages }: Props) {
    const [creating, setCreating] = useState(false);
    const running = applications.filter(
        (application) => application.stage !== 'rejected',
    ).length;

    return (
        <>
            <Head title="Bewerbungen" />

            <div className="max-w-6xl space-y-6 px-4 py-6">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <Heading
                        title="Bewerbungen"
                        description={`${running} laufend, ${applications.length - running} abgesagt.`}
                    />
                    {!creating && (
                        <Button onClick={() => setCreating(true)}>
                            <Plus className="h-4 w-4" />
                            Bewerbung anlegen
                        </Button>
                    )}
                </div>

                {creating && (
                    <CreateForm
                        stages={stages}
                        onCancel={() => setCreating(false)}
                    />
                )}

                {applications.length === 0 ? (
                    <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-sm">
                        Noch keine Bewerbungen. Sie entstehen, sobald du im
                        Generator ein Dokument erzeugst — oder hier von Hand.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="text-muted-foreground border-b text-left">
                                <tr>
                                    <th className="px-4 py-2 font-medium">
                                        Unternehmen · Position
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Stufe
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Beworben
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium">
                                        Recherche
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {applications.map((application) => (
                                    <tr
                                        key={application.id}
                                        onClick={() =>
                                            router.visit(
                                                show.url({
                                                    application: application.id,
                                                }),
                                            )
                                        }
                                        className="hover:bg-muted/40 cursor-pointer"
                                    >
                                        <td className="px-4 py-3">
                                            <span className="inline-flex items-center gap-1.5">
                                                <Link
                                                    href={show.url({
                                                        application:
                                                            application.id,
                                                    })}
                                                    className="font-medium"
                                                >
                                                    {application.company}
                                                </Link>
                                                {application.job_url && (
                                                    <a
                                                        href={
                                                            application.job_url
                                                        }
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        title="Originalanzeige"
                                                        onClick={(event) =>
                                                            event.stopPropagation()
                                                        }
                                                        className="text-muted-foreground hover:text-foreground"
                                                    >
                                                        <ExternalLink className="h-3.5 w-3.5" />
                                                    </a>
                                                )}
                                            </span>
                                            <p className="text-muted-foreground">
                                                {application.position}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3">
                                            <StageMenu
                                                application={application}
                                                stages={stages}
                                            />
                                            <p className="text-muted-foreground mt-1 text-xs">
                                                {since(application.stage_since)}
                                            </p>
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">
                                            {date(application.applied_on) ||
                                                '–'}
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3 text-right">
                                            {application.research_seconds > 0
                                                ? duration(
                                                      application.research_seconds,
                                                  )
                                                : '–'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

function CreateForm({
    stages,
    onCancel,
}: {
    stages: StageOption[];
    onCancel: () => void;
}) {
    const form = useForm({
        company: '',
        position: '',
        job_url: '',
        stage: 'sent' as ApplicationStage,
        date: today(),
    });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post(store.url());
            }}
            className="space-y-4 rounded-lg border p-4"
        >
            <div>
                <p className="font-medium">Bewerbung von Hand anlegen</p>
                <p className="text-muted-foreground text-sm">
                    Für Bewerbungen, die außerhalb des Tools entstanden sind.
                </p>
            </div>

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
                        placeholder="https://…"
                        value={form.data.job_url}
                        onChange={(event) =>
                            form.setData('job_url', event.target.value)
                        }
                    />
                </Field>
                <div className="grid grid-cols-2 gap-4">
                    <Field label="Stufe" error={form.errors.stage}>
                        <Select
                            value={form.data.stage}
                            onValueChange={(value) =>
                                form.setData('stage', value as ApplicationStage)
                            }
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {stages.map((stage) => (
                                    <SelectItem
                                        key={stage.value}
                                        value={stage.value}
                                    >
                                        {stage.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field label="Seit" error={form.errors.date}>
                        <Input
                            type="date"
                            max={today()}
                            value={form.data.date}
                            onChange={(event) =>
                                form.setData('date', event.target.value)
                            }
                        />
                    </Field>
                </div>
            </div>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="ghost" onClick={onCancel}>
                    Abbrechen
                </Button>
                <Button type="submit" disabled={form.processing}>
                    Anlegen
                </Button>
            </div>
        </form>
    );
}
