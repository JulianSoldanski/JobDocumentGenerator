/**
 * KI-Aufrufe laufen im Hintergrund: Der Aufruf legt eine Aufgabe an, das
 * Frontend fragt ihren Stand ab. Hier stehen die beiden HTTP-Aufrufe dazu.
 */

export type AiTaskStatus = 'queued' | 'running' | 'succeeded' | 'failed';

export type AiTaskResponse<T> = {
    id: string;
    type: string;
    status: AiTaskStatus;
    result: T | null;
    error: string | null;
};

export class AiRequestError extends Error {}

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

type Request = {
    method: 'GET' | 'POST';
    body?: BodyInit;
    /** Bewusst eng typisiert: nur einfache Kopfzeilen, damit der Spread hält. */
    headers?: Record<string, string>;
};

async function request<T>(url: string, init: Request): Promise<T> {
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

        throw new AiRequestError(
            firstError ??
                (response.status === 429
                    ? 'Zu viele KI-Aufrufe in kurzer Zeit. Warte einen Moment.'
                    : 'Der Aufruf ist fehlgeschlagen.'),
        );
    }

    return payload as T;
}

/** Startet eine Aufgabe und gibt ihre Kennung zurück. */
export async function startAiTask(
    url: string,
    payload: Record<string, unknown> | FormData,
): Promise<string> {
    const isForm = payload instanceof FormData;

    const { task_id } = await request<{ task_id: string }>(url, {
        method: 'POST',
        body: isForm ? payload : JSON.stringify(payload),
        headers: isForm ? {} : { 'Content-Type': 'application/json' },
    });

    return task_id;
}

export async function fetchAiTask<T>(id: string): Promise<AiTaskResponse<T>> {
    return request<AiTaskResponse<T>>(`/ai-tasks/${id}`, { method: 'GET' });
}
