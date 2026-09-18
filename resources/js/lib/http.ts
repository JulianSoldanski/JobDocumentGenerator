/**
 * Die Aufrufe, die nicht über Inertia laufen.
 *
 * Inertia rendert die Seite neu — richtig für ein gespeichertes Formular,
 * falsch für einen geladenen Anzeigentext oder den Stand einer
 * Hintergrund-Aufgabe. Diese Aufrufe geben nur Daten zurück.
 */

export class RequestError extends Error {}

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

type RequestInit = {
    method: 'GET' | 'POST' | 'PATCH';
    body?: BodyInit;
    /** Bewusst eng typisiert: nur einfache Kopfzeilen, damit der Spread hält. */
    headers?: Record<string, string>;
};

export async function request<T>(url: string, init: RequestInit): Promise<T> {
    const response = await fetch(url, {
        method: init.method,
        body: init.body,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': csrfToken(),
            ...init.headers,
        },
    });

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        // Laravel liefert bei 422 eine Sammlung von Feldfehlern; für einen
        // Knopf reicht die erste verständliche Meldung.
        const firstError =
            payload?.message ??
            (payload?.errors
                ? Object.values(
                      payload.errors as Record<string, string[]>,
                  )[0]?.[0]
                : null);

        throw new RequestError(
            firstError ??
                (response.status === 429
                    ? 'Zu viele Aufrufe in kurzer Zeit. Warte einen Moment.'
                    : 'Der Aufruf ist fehlgeschlagen.'),
        );
    }

    return payload as T;
}

/** Ein Aufruf mit JSON-Rumpf, der eine Antwort zurückgibt. */
export async function postJson<T>(
    url: string,
    payload: Record<string, unknown>,
): Promise<T> {
    return request<T>(url, {
        method: 'POST',
        body: JSON.stringify(payload),
        headers: { 'Content-Type': 'application/json' },
    });
}

/** Liest Daten, ohne die Seite neu zu rendern. */
export async function getJson<T>(url: string): Promise<T> {
    return request<T>(url, { method: 'GET' });
}

/** Ändert einen Datensatz und gibt die Antwort des Servers zurück. */
export async function patchJson<T>(
    url: string,
    payload: Record<string, unknown>,
): Promise<T> {
    return request<T>(url, {
        method: 'PATCH',
        body: JSON.stringify(payload),
        headers: { 'Content-Type': 'application/json' },
    });
}
