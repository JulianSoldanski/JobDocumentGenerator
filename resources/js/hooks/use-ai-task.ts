import { useCallback, useEffect, useRef, useState } from 'react';
import {
    AiRequestError,
    fetchAiTask,
    startAiTask,
    type AiTaskStatus,
} from '@/lib/ai';

const POLL_INTERVAL = 1500;

type State<T> = {
    status: 'idle' | AiTaskStatus;
    result: T | null;
    error: string | null;
};

const initial = <T>(): State<T> => ({
    status: 'idle',
    result: null,
    error: null,
});

/**
 * Startet einen KI-Aufruf und verfolgt seinen Stand, bis ein Ergebnis da ist.
 */
export function useAiTask<T>() {
    const [state, setState] = useState<State<T>>(initial<T>());
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const mounted = useRef(true);

    useEffect(() => {
        mounted.current = true;

        return () => {
            mounted.current = false;
            if (timer.current) {
                clearTimeout(timer.current);
            }
        };
    }, []);

    const poll = useCallback((id: string) => {
        timer.current = setTimeout(async () => {
            if (!mounted.current) {
                return;
            }

            try {
                const task = await fetchAiTask<T>(id);

                if (!mounted.current) {
                    return;
                }

                if (task.status === 'succeeded' || task.status === 'failed') {
                    setState({
                        status: task.status,
                        result: task.result,
                        error: task.error,
                    });

                    return;
                }

                setState((current) => ({ ...current, status: task.status }));
                poll(id);
            } catch (error) {
                if (mounted.current) {
                    setState({
                        status: 'failed',
                        result: null,
                        error:
                            error instanceof AiRequestError
                                ? error.message
                                : 'Der Stand der Aufgabe ließ sich nicht abfragen.',
                    });
                }
            }
        }, POLL_INTERVAL);
    }, []);

    const start = useCallback(
        async (url: string, payload: Record<string, unknown> | FormData) => {
            setState({ status: 'queued', result: null, error: null });

            try {
                poll(await startAiTask(url, payload));
            } catch (error) {
                setState({
                    status: 'failed',
                    result: null,
                    error:
                        error instanceof AiRequestError
                            ? error.message
                            : 'Der Aufruf ließ sich nicht starten.',
                });
            }
        },
        [poll],
    );

    const reset = useCallback(() => setState(initial<T>()), []);

    return {
        ...state,
        running: state.status === 'queued' || state.status === 'running',
        start,
        reset,
    };
}
