import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { date, duration } from '@/lib/applications';
import { cn } from '@/lib/utils';
import { show } from '@/actions/App/Http/Controllers/ApplicationController';

type RejectedApplication = {
    id: number;
    company: string;
    position: string;
    rejected_at: string;
};

type EffortGroup = {
    label: string;
    invited: boolean;
    median_seconds: number | null;
    applications: {
        id: number;
        company: string;
        position: string;
        seconds: number;
    }[];
};

type Props = {
    summary: {
        total: number;
        running: number;
        rejected: number;
        interviewed: number;
    };
    funnel: { label: string; count: number }[];
    furthest: { label: string; count: number }[];
    effort: { groups: EffortGroup[]; unmeasured: number; pending: number };
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

/** Was nur Kontext ist, bleibt grau — so tritt das Blau hervor. */
const CONTEXT = 'bg-[#898781]';

/**
 * Wie weit es ging, als Stufen einer Farbe: Grau für kein Gespräch, dann
 * kräftiger, je weiter. Im Dunkeln laufen die Stufen zum Hellen hin, damit
 * das weiteste Gespräch auch dort am deutlichsten ist.
 */
const REACHED = [
    'text-[#b5b4ab] dark:text-[#4a4945]',
    'text-[#86b6ef] dark:text-[#184f95]',
    'text-[#2a78d6] dark:text-[#3987e5]',
    'text-[#104281] dark:text-[#9ec5f4]',
];

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
    furthest,
    effort,
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
                <div className="mx-auto w-full max-w-6xl space-y-6 px-4 py-6">
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

            <div className="mx-auto w-full max-w-6xl space-y-8 px-4 py-6">
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
                        title="Wie weit es ging"
                        description="Jede Bewerbung genau einmal, nach dem weitesten Gespräch — auch wenn danach abgesagt wurde."
                    >
                        <Donut slices={furthest} total={summary.total} />
                    </Card>

                    <Card
                        title="Erstellungsdauer und Einladung"
                        description={`Die im Generator gemessene Zeit je Bewerbung, der Strich ist der Median. Nicht enthalten: ${effort.unmeasured} ohne gemessene Zeit oder unter 20 Sek., ${effort.pending} noch ohne Antwort.`}
                    >
                        <Strip groups={effort.groups} />
                    </Card>

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

/**
 * Ein Ring, in der Mitte die Gesamtzahl. Die Legende daneben trägt die
 * Zahlen, damit die Farbe allein nichts erklären muss.
 */
function Donut({
    slices,
    total,
}: {
    slices: Props['furthest'];
    total: number;
}) {
    const [active, setActive] = useState<string | null>(null);

    // Winkel im Uhrzeigersinn ab zwölf Uhr, als Anteil am ganzen Ring.
    const starts = slices.map((_, position) =>
        slices.slice(0, position).reduce((sum, slice) => sum + slice.count, 0),
    );

    const hover = (label: string) => ({
        onPointerEnter: () => setActive(label),
        onPointerLeave: () => setActive(null),
        onFocus: () => setActive(label),
        onBlur: () => setActive(null),
    });

    return (
        <div className="flex flex-wrap items-center gap-x-10 gap-y-6">
            <div className="relative size-44 shrink-0">
                <svg viewBox="0 0 200 200" className="size-full">
                    {slices.map(
                        (slice, position) =>
                            slice.count > 0 && (
                                <Tooltip key={slice.label}>
                                    <TooltipTrigger asChild>
                                        <path
                                            d={sector(
                                                starts[position] / total,
                                                (starts[position] +
                                                    slice.count) /
                                                    total,
                                            )}
                                            tabIndex={0}
                                            {...hover(slice.label)}
                                            className={cn(
                                                'stroke-background fill-current stroke-2 transition-opacity outline-none',
                                                REACHED[position],
                                                active !== null &&
                                                    active !== slice.label &&
                                                    'opacity-35',
                                            )}
                                        />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {slice.label}: {slice.count} von {total}{' '}
                                        Bewerbungen
                                    </TooltipContent>
                                </Tooltip>
                            ),
                    )}
                </svg>
                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-2xl font-semibold tabular-nums">
                        {total}
                    </span>
                    <span className="text-muted-foreground text-xs">
                        Bewerbungen
                    </span>
                </div>
            </div>

            <ul className="min-w-44 flex-1 space-y-1 text-sm">
                {slices.map((slice, position) => (
                    <li
                        key={slice.label}
                        {...hover(slice.label)}
                        className="flex items-center gap-2 rounded-md py-0.5"
                    >
                        <span
                            className={cn(
                                'size-2.5 shrink-0 rounded-full bg-current',
                                REACHED[position],
                            )}
                        />
                        <span className="flex-1">{slice.label}</span>
                        <span className="tabular-nums">{slice.count}</span>
                        <span className="text-muted-foreground w-11 text-right tabular-nums">
                            {percent(slice.count, total)}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/**
 * Ein Ringstück zwischen zwei Anteilen (0 bis 1) des Umlaufs. Ein ganzer
 * Ring bleibt eine Haaresbreite offen — Anfang und Ende eines Bogens dürfen
 * nicht zusammenfallen.
 */
function sector(from: number, to: number) {
    const outer = 92;
    const inner = 60;
    const turn = Math.min(to - from, 0.99999);
    const point = (radius: number, share: number) => {
        const angle = 2 * Math.PI * share;

        return `${100 + radius * Math.sin(angle)} ${100 - radius * Math.cos(angle)}`;
    };
    const large = turn > 0.5 ? 1 : 0;

    return [
        `M ${point(outer, from)}`,
        `A ${outer} ${outer} 0 ${large} 1 ${point(outer, from + turn)}`,
        `L ${point(inner, from + turn)}`,
        `A ${inner} ${inner} 0 ${large} 0 ${point(inner, from)}`,
        'Z',
    ].join(' ');
}

/**
 * Wegmarken der Zeitachse. Sie ist logarithmisch — sonst drängten sich alle
 * kurzen Bewerbungen am linken Rand, weil einzelne Stunden dauerten.
 */
const TICKS = [
    { seconds: 10, label: '10 Sek.' },
    { seconds: 60, label: '1 Min.' },
    { seconds: 5 * 60, label: '5 Min.' },
    { seconds: 30 * 60, label: '30 Min.' },
    { seconds: 3 * 60 * 60, label: '3 Std.' },
];

/** Nebeneinanderliegende Punkte weichen abwechselnd nach oben und unten aus. */
const LANES = [0, -9, 9];

/**
 * Je Ausgang eine Zeile, je Bewerbung ein Punkt auf der Zeitachse. So bleibt
 * sichtbar, wie wenige Fälle hinter einem Median stehen.
 */
function Strip({ groups }: { groups: EffortGroup[] }) {
    const seconds = groups.flatMap((group) =>
        group.applications.map((application) => application.seconds),
    );

    if (seconds.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                Noch keine Bewerbung mit gemessener Zeit und feststehendem
                Ausgang.
            </p>
        );
    }

    const low = Math.log(Math.min(...seconds) / 1.6);
    const high = Math.log(Math.max(...seconds) * 1.6);
    const x = (value: number) => ((Math.log(value) - low) / (high - low)) * 100;
    const ticks = TICKS.filter(
        (tick) => x(tick.seconds) > 4 && x(tick.seconds) < 96,
    );

    return (
        <div className="space-y-3">
            {groups.map((group) => (
                <div key={group.label} className="flex items-center gap-3">
                    <div className="w-36 shrink-0">
                        <p className="text-sm">{group.label}</p>
                        <p className="text-muted-foreground text-xs">
                            {group.applications.length} · Median{' '}
                            {group.median_seconds === null
                                ? '–'
                                : duration(group.median_seconds)}
                        </p>
                    </div>
                    <div className="relative h-14 flex-1">
                        {ticks.map((tick) => (
                            <span
                                key={tick.seconds}
                                className="bg-border absolute inset-y-0 w-px"
                                style={{ left: `${x(tick.seconds)}%` }}
                            />
                        ))}
                        {group.median_seconds !== null && (
                            <span
                                className="bg-foreground absolute inset-y-1 w-0.5 -translate-x-1/2 rounded-full"
                                style={{ left: `${x(group.median_seconds)}%` }}
                            />
                        )}
                        {group.applications.map((application, position) => (
                            <Tooltip key={application.id}>
                                <TooltipTrigger asChild>
                                    <Link
                                        href={show.url({
                                            application: application.id,
                                        })}
                                        className="absolute top-1/2 flex size-5 -translate-1/2 items-center justify-center rounded-full"
                                        style={{
                                            left: `${x(application.seconds)}%`,
                                            marginTop:
                                                LANES[position % LANES.length],
                                        }}
                                    >
                                        <span
                                            className={cn(
                                                'ring-background size-2.5 rounded-full ring-2',
                                                group.invited ? BAR : CONTEXT,
                                            )}
                                        />
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {application.company} ·{' '}
                                    {application.position}:{' '}
                                    {duration(application.seconds)}
                                </TooltipContent>
                            </Tooltip>
                        ))}
                    </div>
                </div>
            ))}

            <div className="flex gap-3">
                <div className="w-36 shrink-0" />
                <div className="relative h-4 flex-1">
                    {ticks.map((tick) => (
                        <span
                            key={tick.seconds}
                            className="text-muted-foreground absolute -translate-x-1/2 text-[11px] whitespace-nowrap"
                            style={{ left: `${x(tick.seconds)}%` }}
                        >
                            {tick.label}
                        </span>
                    ))}
                </div>
            </div>
        </div>
    );
}
