import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowRight, Bookmark, ExternalLink, Trash2 } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { Field } from '@/components/applications/field';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ago } from '@/lib/applications';
import {
    destroy,
    generate,
    store,
    token,
    update,
} from '@/actions/App/Http/Controllers/QueueController';
import { show as showApplication } from '@/actions/App/Http/Controllers/ApplicationController';

type Status = 'open' | 'in_progress' | 'done' | 'skipped' | 'failed';

type Item = {
    id: number;
    url: string;
    title: string;
    note: string;
    status: Status;
    created_at: string | null;
    application_id: number | null;
};

const STATUS: Record<Status, string> = {
    open: 'offen',
    in_progress: 'in Arbeit',
    done: 'erledigt',
    skipped: 'übersprungen',
    failed: 'fehlgeschlagen',
};

/** Ohne Titel die Adresse, ohne Protokoll — so liest sie sich wie ein Name. */
const label = (item: Item) =>
    item.title ||
    item.url.replace(/^https?:\/\/(www\.)?/, '').replace(/\/$/, '');

const host = (url: string) => {
    try {
        return new URL(url).host.replace(/^www\./, '');
    } catch {
        return url;
    }
};

/**
 * Die Sammelstelle: Stellen, die noch nicht bearbeitet sind — die ältesten
 * oben, die Queue wird von hinten abgearbeitet.
 */
export default function Index({
    items,
    capture_url,
}: {
    items: Item[];
    capture_url: string;
}) {
    // Was gerade im Generator liegt, steht vor dem, was noch wartet.
    const active = [
        ...items.filter((item) => item.status === 'in_progress'),
        ...items.filter((item) => item.status === 'open'),
    ];
    const archive = items.filter(
        (item) => item.status !== 'open' && item.status !== 'in_progress',
    );
    const open = items.filter((item) => item.status === 'open').length;

    return (
        <>
            <Head title="Queue" />

            <div className="max-w-4xl space-y-8 px-4 py-6">
                <Heading
                    title="Queue"
                    description={
                        open === 1
                            ? '1 Stelle offen.'
                            : `${open} Stellen offen.`
                    }
                />

                <AddForm />

                <section className="space-y-2">
                    {active.length === 0 ? (
                        <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-sm">
                            Nichts offen. Neue Stellen kommen über das
                            Bookmarklet unten oder das Feld oben herein.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-lg border">
                            {active.map((item) => (
                                <ActiveItem key={item.id} item={item} />
                            ))}
                        </ul>
                    )}
                </section>

                {archive.length > 0 && (
                    <details className="group">
                        <summary className="text-muted-foreground hover:text-foreground cursor-pointer text-sm">
                            Archiv ({archive.length})
                        </summary>
                        <ul className="mt-3 divide-y rounded-lg border">
                            {archive.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-center gap-3 px-4 py-2.5 text-sm"
                                >
                                    <Badge variant="secondary">
                                        {STATUS[item.status]}
                                    </Badge>
                                    <a
                                        href={item.url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="min-w-0 flex-1 truncate hover:underline"
                                    >
                                        {label(item)}
                                    </a>
                                    {item.application_id && (
                                        <Link
                                            href={showApplication.url({
                                                application:
                                                    item.application_id,
                                            })}
                                            className="text-muted-foreground hover:text-foreground shrink-0"
                                        >
                                            Zur Bewerbung
                                        </Link>
                                    )}
                                    {item.status === 'skipped' && (
                                        <button
                                            type="button"
                                            className="text-muted-foreground hover:text-foreground shrink-0"
                                            onClick={() =>
                                                router.patch(
                                                    update.url({
                                                        item: item.id,
                                                    }),
                                                    { status: 'open' },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Wieder öffnen
                                        </button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </details>
                )}

                <Bookmarklet url={capture_url} />
            </div>
        </>
    );
}

function AddForm() {
    const form = useForm({ url: '', note: '' });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.post(store.url(), {
                    preserveScroll: true,
                    onSuccess: () => form.reset(),
                });
            }}
            className="grid gap-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto] sm:items-end"
        >
            <Field label="Adresse der Stelle" error={form.errors.url}>
                <Input
                    type="url"
                    placeholder="https://…"
                    value={form.data.url}
                    onChange={(event) =>
                        form.setData('url', event.target.value)
                    }
                />
            </Field>
            <Field label="Notiz" error={form.errors.note}>
                <Input
                    placeholder="optional"
                    value={form.data.note}
                    onChange={(event) =>
                        form.setData('note', event.target.value)
                    }
                />
            </Field>
            <Button
                type="submit"
                disabled={form.processing || form.data.url.trim() === ''}
                className="sm:mb-px"
            >
                Hinzufügen
            </Button>
        </form>
    );
}

function ActiveItem({ item }: { item: Item }) {
    const form = useForm({});

    return (
        <li className="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3">
            <div className="min-w-0 flex-1">
                <a
                    href={item.url}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex max-w-full items-center gap-1.5 font-medium hover:underline"
                >
                    <span className="truncate">{label(item)}</span>
                    <ExternalLink className="text-muted-foreground h-3.5 w-3.5 shrink-0" />
                </a>
                <p className="text-muted-foreground truncate text-sm">
                    {[
                        host(item.url),
                        item.note,
                        item.status === 'in_progress'
                            ? 'im Generator'
                            : `gemerkt ${ago(item.created_at)}`,
                    ]
                        .filter(Boolean)
                        .join(' · ')}
                </p>
            </div>

            <div className="flex items-center gap-1">
                <Button
                    size="sm"
                    disabled={form.processing}
                    onClick={() => form.post(generate.url({ item: item.id }))}
                >
                    <ArrowRight className="h-4 w-4" />
                    {item.status === 'in_progress' ? 'Weiter' : 'Generieren'}
                </Button>
                {item.status === 'open' && (
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() =>
                            router.patch(
                                update.url({ item: item.id }),
                                { status: 'skipped' },
                                { preserveScroll: true },
                            )
                        }
                    >
                        Überspringen
                    </Button>
                )}
                <Button
                    size="icon"
                    variant="ghost"
                    aria-label="Entfernen"
                    onClick={() =>
                        router.delete(destroy.url({ item: item.id }), {
                            preserveScroll: true,
                        })
                    }
                >
                    <Trash2 className="h-4 w-4" />
                </Button>
            </div>
        </li>
    );
}

/**
 * Ein Lesezeichen, das die gerade offene Seite in die Queue schickt: Es
 * öffnet ein kleines Fenster zur App, das sich nach dem Eintragen selbst
 * schließt. Kein Add-on, keine Berechtigungen.
 */
function Bookmarklet({ url }: { url: string }) {
    const link = useRef<HTMLAnchorElement>(null);

    // React blockiert javascript:-Adressen im href — hier bewusst gesetzt.
    useEffect(() => {
        link.current?.setAttribute(
            'href',
            `javascript:(()=>{window.open(${JSON.stringify(url)}+'&url='+encodeURIComponent(location.href)+'&title='+encodeURIComponent(document.title),'cvcreater','width=420,height=180')})()`,
        );
    }, [url]);

    return (
        <section className="space-y-3 rounded-lg border p-4">
            <div>
                <p className="font-medium">
                    Stellen von jeder Seite aus merken
                </p>
                <p className="text-muted-foreground text-sm">
                    Zieh den Knopf in deine Lesezeichenleiste. Ein Klick darauf
                    auf einer Stellenseite schickt sie hierher.
                </p>
            </div>
            <div className="flex flex-wrap items-center gap-3">
                <a
                    ref={link}
                    onClick={(event) => event.preventDefault()}
                    className="bg-secondary inline-flex cursor-grab items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium"
                >
                    <Bookmark className="h-4 w-4" />→ Queue
                </a>
                <button
                    type="button"
                    className="text-muted-foreground hover:text-foreground text-sm"
                    onClick={() => {
                        if (
                            window.confirm(
                                'Einen neuen Link erzeugen? Das bisherige Lesezeichen funktioniert danach nicht mehr.',
                            )
                        ) {
                            router.post(
                                token.url(),
                                {},
                                { preserveScroll: true },
                            );
                        }
                    }}
                >
                    Neuen Link erzeugen
                </button>
            </div>
        </section>
    );
}
