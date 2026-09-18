import { router } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { StageBadge } from '@/components/applications/stage-badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { stageLabel, today } from '@/lib/applications';
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
 * Die Stufe direkt in der Liste weiterschalten — mit heutigem Datum.
 * Nachtragen mit einem anderen Datum geht auf der Detailseite.
 *
 * Bewusst ein Menü statt eines Knopfs: Stufenwechsel werden nie gelöscht, ein
 * Fehlklick bliebe für immer im Verlauf.
 */
export function StageMenu({ application, stages }: Props) {
    const linear = stages.filter((stage) => stage.value !== 'rejected');
    const rejected = application.stage === 'rejected';
    const position = linear.findIndex(
        (stage) => stage.value === application.stage,
    );
    const next = rejected ? undefined : linear[position + 1];

    const move = (stage: ApplicationStage) =>
        router.post(
            changeStage.url({ application: application.id }),
            { stage, date: today() },
            { preserveScroll: true },
        );

    return (
        <DropdownMenu>
            {/* Die Zeile öffnet sonst die Detailseite. */}
            <DropdownMenuTrigger
                onClick={(event) => event.stopPropagation()}
                className="hover:bg-muted -mx-1 inline-flex items-center gap-1 rounded-md px-1 py-0.5"
            >
                <StageBadge application={application} stages={stages} />
                <ChevronDown className="text-muted-foreground h-3.5 w-3.5" />
            </DropdownMenuTrigger>

            {/* Klicks im Menü laufen in React bis zur Zeile hoch — hier ist Schluss. */}
            <DropdownMenuContent
                align="start"
                onClick={(event) => event.stopPropagation()}
            >
                {next && (
                    <DropdownMenuItem onSelect={() => move(next.value)}>
                        Nächste Runde: {stageLabel(stages, next.value)}
                    </DropdownMenuItem>
                )}
                {!rejected && (
                    <DropdownMenuItem
                        variant="destructive"
                        onSelect={() => move('rejected')}
                    >
                        Absage erhalten
                    </DropdownMenuItem>
                )}
                {rejected && (
                    <DropdownMenuItem
                        onSelect={() =>
                            router.post(
                                reactivate.url({ application: application.id }),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Reaktivieren
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
