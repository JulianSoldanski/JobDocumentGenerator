/**
 * KI-Aufrufe laufen im Hintergrund: Der Aufruf legt eine Aufgabe an, das
 * Frontend fragt ihren Stand ab. Hier stehen die beiden HTTP-Aufrufe dazu.
 */

import { request, RequestError } from '@/lib/http';

export type AiTaskStatus = 'queued' | 'running' | 'succeeded' | 'failed';

export type AiTaskResponse<T> = {
    id: string;
    type: string;
    status: AiTaskStatus;
    result: T | null;
    error: string | null;
};

export { RequestError as AiRequestError };

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
