import { router } from '@inertiajs/react';
import { ArrowRight, RotateCcw } from 'lucide-react';
import type { MouseEvent } from 'react';
import { Button } from '@/components/ui/button';
import { nextStage, today } from '@/lib/applications';
import {
    reactivate,
    stage as changeStage,
} from '@/actions/App/Http/Controllers/ApplicationController';
import type { Application, ApplicationStage, StageOption } from '@/types';

type Props = {
    application: Application;
    stages: StageOption[];
};

/**
 * Die Stufe direkt in der Liste weiterschalten, mit heutigem Datum. Ein
 * anderes Datum oder eine Korrektur geht auf der Detailseite.
 */
export function StageActions({ application, stages }: Props) {
    const next = nextStage(stages, application);

    // Die Zeile öffnet sonst zusätzlich die Detailseite.
    const act = (event: MouseEvent, url: string, data: object = {}) => {
        event.stopPropagation();
        router.post(url, { ...data }, { preserveScroll: true });
    };

    const move = (event: MouseEvent, stage: ApplicationStage) =>
        act(event, changeStage.url({ application: application.id }), {
            stage,
            date: today(),
        });

    return (
        <div className="flex justify-end gap-1.5">
            {application.stage === 'rejected' && (
                <Button
                    size="sm"
                    variant="ghost"
                    onClick={(event) =>
                        act(
                            event,
                            reactivate.url({ application: application.id }),
                        )
                    }
                >
                    <RotateCcw />
                    Reaktivieren
                </Button>
            )}
            {next && (
                <Button
                    size="sm"
                    variant="outline"
                    title={`Nächste Stufe: ${next.label}`}
                    onClick={(event) => move(event, next.value)}
                >
                    <ArrowRight />
                    {next.label}
                </Button>
            )}
            {application.stage !== 'rejected' && (
                <Button
                    size="sm"
                    variant="ghost"
                    title="Absage erhalten"
                    className="text-destructive hover:text-destructive"
                    onClick={(event) => move(event, 'rejected')}
                >
                    Absage
                </Button>
            )}
        </div>
    );
}
