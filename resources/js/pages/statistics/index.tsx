import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { date } from '@/lib/applications';
import { cn } from '@/lib/utils';
import { show } from '@/actions/App/Http/Controllers/ApplicationController';

type RejectedApplication = {
    id: number;
    company: string;
    position: string;
    rejected_at: string;
};

type Props = {
    summary: {
        total: number;
        running: number;
        rejected: number;
        interviewed: number;
    };
    funnel: { label: string; count: number }[];
    durations: { label: string; median_days: number | null; samples: number }[];
    rejections: {
        label: string;
        count: number;
        applications: RejectedApplication[];
    }[];
    months: { month: string; count: number }[];
};

/**
 * Eine Reihe je Diagramm, also eine Farbe für alle Balken — geprüft gegen
 * beide Hintergründe der App (hell und dunkel).
 */
const BAR = 'bg-[#2a78d6] dark:bg-[#3987e5]';

const percent = (part: number, whole: number) =>
    whole > 0 ? `${Math.round((part / whole) * 100)} %` : '–';

const days = (value: number) =>
    `${value.toLocaleString('de-DE', { maximumFractionDigits: 1 })} ${value === 1 ? 'Tag' : 'Tage'}`;

/**
 * Wertet die Stufen-Historie aus: wie weit Bewerbungen kommen, wie lange sie
 * wo liegen, wo abgesagt wird und wann wie viel lief.
 */
export default function Index({
    summary,
    funnel,
    durations,
    rejections,
    months,
}: Props) {
    const [filter, setFilter] = useState<string | null>(null);
    const selected = rejections.find((group) => group.label === filter);

    if (summary.total === 0) {
        return (
            <>
                <Head title="Statistik" />
                <div className="space-y-6 px-4 py-6">
                    <Heading title="Statistik" />
                    <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-sm">
                        Noch keine Bewerbungen — die Statistik füllt sich,
                        sobald es welche gibt.
                    </p>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Statistik" />

            <div className="max-w-6xl space-y-8 px-4 py-6">
                <Heading
                    title="Statistik"
                    description="Ausgewertet aus dem Verlauf jeder Bewerbung."
                />

                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatTile label="Bewerbungen" value={summary.total} />
                    <StatTile label="Laufend" value={summary.running} />
                    <StatTile
                        label="Gesprächsquote"
                        value={percent(summary.interviewed, summary.total)}
                        hint={`${summary.interviewed} von ${summary.total} bis ins Gespräch`}
                    />
                    <StatTile label="Absagen" value={summary.rejected} />
                </div>

                <div className="grid gap-8 lg:grid-cols-2">
                    <Card
                        title="Funnel"
                        description="Wie viele Bewerbungen welche Stufe erreicht haben — Erstellt und Versendet als eine Zeile."
                    >
                        <Bars
                            rows={funnel.map((row) => ({
                                label: row.label,
                                value: row.count,
                                display: `${row.count} · ${percent(row.count, summary.total)}`,
                                tooltip: `${row.count} von ${summary.total} Bewerbungen`,
                            }))}
                        />
                    </Card>

                    <Card
                        title="Verweildauer je Stufe"
                        description="Median der abgeschlossenen Aufenthalte — einzelne Ausreißer verzerren ihn nicht."
                    >
                        <Bars
                            rows={durations.map((row) => ({
                                label: row.label,
                                value: row.median_days ?? 0,
                                display:
                                    row.median_days === null
                                        ? '–'
                                        : days(row.median_days),
                                tooltip:
                                    row.samples === 0
                                        ? 'Noch kein abgeschlossener Aufenthalt'
                                        : `Median aus ${row.samples} ${row.samples === 1 ? 'Aufenthalt' : 'Aufenthalten'}`,
                            }))}
                        />
                    </Card>

                    <Card
                        title="Absagen"
                        description="Aus welcher Stufe heraus abgesagt wurde. Ein Klick zeigt die Bewerbungen dahinter."
                    >
                        <Bars
                            rows={rejections.map((group) => ({
                                label: group.label,
                                value: group.count,
                                display: `${group.count}`,
                                tooltip: `${group.count} ${group.count === 1 ? 'Absage' : 'Absagen'} nach „${group.label}“`,
                            }))}
                            selected={filter}
                            onSelect={(label) =>
                                setFilter((current) =>
                                    current === label ? null : label,
                                )
                            }
                        />

                        {selected && (
                            <div className="mt-4 space-y-2 border-t pt-4">
                                <p className="text-sm font-medium">
                                    Absagen nach „{selected.label}“
                                </p>
                                {selected.applications.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">
                                        Keine.
                                    </p>
                                ) : (
                                    <ul className="space-y-1 text-sm">
                                        {selected.applications.map(
                                            (application) => (
                                                <li
                                                    key={`${application.id}-${application.rejected_at}`}
                                                    className="flex justify-between gap-4"
                                                >
                                                    <Link
                                                        href={show.url({
                                                            application:
                                                                application.id,
                                                        })}
                                                        className="hover:underline"
                                                    >
                                                        {application.company}
                                                        <span className="text-muted-foreground">
                                                            {' '}
                                                            ·{' '}
                                                            {
                                                                application.position
                                                            }
                                                        </span>
                                                    </Link>
                                                    <span className="text-muted-foreground shrink-0 tabular-nums">
                                                        {date(
                                                            application.rejected_at,
                                                        )}
                                                    </span>
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                )}
                            </div>
                        )}
                    </Card>

                    <Card
                        title="Verlauf"
                        description="Bewerbungen je Monat, nach ihrem ersten Eintrag."
                    >
                        <Columns months={months} />
                    </Card>
                </div>
            </div>
        </>
    );
}

function StatTile({
    label,
    value,
    hint,
}: {
    label: string;
    value: number | string;
    hint?: string;
}) {
    return (
        <div className="rounded-lg border p-4">
            <p className="text-muted-foreground text-sm">{label}</p>
            <p className="mt-1 text-2xl font-semibold">{value}</p>
            {hint && (
                <p className="text-muted-foreground mt-1 text-xs">{hint}</p>
            )}
        </div>
    );
}

function Card({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: React.ReactNode;
}) {
    return (
        <section className="rounded-lg border p-5">
            <p className="font-medium">{title}</p>
            <p className="text-muted-foreground mb-5 text-sm">{description}</p>
            {children}
        </section>
    );
}

type Row = { label: string; value: number; display: string; tooltip: string };

/**
 * Waagerechte Balken, der Wert an der Spitze. Mit `onSelect` wird jede Zeile
 * zum Knopf — so filtern die Absagen.
 */
function Bars({
    rows,
    selected,
    onSelect,
}: {
    rows: Row[];
    selected?: string | null;
    onSelect?: (label: string) => void;
}) {
    const max = Math.max(...rows.map((row) => row.value), 0);

    return (
        <div className="space-y-2.5">
            {rows.map((row) => {
                const faded = selected != null && selected !== row.label;
                const content = (
                    <>
                        <span className="text-muted-foreground w-36 shrink-0 text-left text-sm">
                            {row.label}
                        </span>
                        <span className="flex min-w-0 flex-1 items-center gap-2">
                            {row.value > 0 && (
                                <span
                                    className={cn(
                                        'h-4 rounded-r-[4px] transition-opacity',
                                        BAR,
                                        faded && 'opacity-35',
                                    )}
                                    style={{
                                        width: `${(row.value / max) * 85}%`,
                                    }}
                                />
                            )}
                            <span className="shrink-0 text-sm tabular-nums">
                                {row.display}
                            </span>
                        </span>
                    </>
                );

                return (
                    <Tooltip key={row.label}>
                        <TooltipTrigger asChild>
                            {onSelect ? (
                                <button
                                    type="button"
                                    onClick={() => onSelect(row.label)}
                                    aria-pressed={selected === row.label}
                                    className="hover:bg-muted/50 -mx-2 flex w-[calc(100%+1rem)] items-center gap-3 rounded-md px-2 py-0.5"
                                >
                                    {content}
                                </button>
                            ) : (
                                <div
                                    tabIndex={0}
                                    className="flex items-center gap-3 rounded-md outline-offset-2"
                                >
                                    {content}
                                </div>
                            )}
                        </TooltipTrigger>
                        <TooltipContent>{row.tooltip}</TooltipContent>
                    </Tooltip>
                );
            })}
        </div>
    );
}

/**
 * Eine Säule je Monat, der Wert auf der Säule. Leere Monate bleiben stehen,
 * damit Pausen als Pausen sichtbar werden.
 */
function Columns({ months }: { months: Props['months'] }) {
    const max = Math.max(...months.map((month) => month.count), 1);

    return (
        <div className="flex h-44 items-end gap-0.5">
            {months.map((month, position) => {
                const day = new Date(`${month.month}-01T00:00:00`);
                const label = day.toLocaleDateString('de-DE', {
                    month: 'short',
                });
                const full = day.toLocaleDateString('de-DE', {
                    month: 'long',
                    year: 'numeric',
                });
                // Die Jahreszahl nur am Anfang und bei jedem Januar.
                const year =
                    position === 0 || day.getMonth() === 0
                        ? `’${String(day.getFullYear()).slice(2)}`
                        : '';

                return (
                    <Tooltip key={month.month}>
                        <TooltipTrigger asChild>
                            <div
                                tabIndex={0}
                                className="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1 rounded-md outline-offset-2"
                            >
                                {month.count > 0 && (
                                    <span className="text-xs tabular-nums">
                                        {month.count}
                                    </span>
                                )}
                                <span
                                    className={cn(
                                        'w-full max-w-6 rounded-t-[4px]',
                                        BAR,
                                    )}
                                    style={{
                                        height: `${(month.count / max) * 70}%`,
                                    }}
                                />
                                <span className="text-muted-foreground w-full truncate text-center text-[11px]">
                                    {label}
                                    {year}
                                </span>
                            </div>
                        </TooltipTrigger>
                        <TooltipContent>
                            {full}: {month.count}{' '}
                            {month.count === 1 ? 'Bewerbung' : 'Bewerbungen'}
                        </TooltipContent>
                    </Tooltip>
                );
            })}
        </div>
    );
}
