import { Badge } from '@/components/ui/badge';
import { stageLabel } from '@/lib/applications';
import type { Application, StageOption } from '@/types';

/**
 * Eine Absage ist keine Stufe, sondern ein Abbruch — deshalb steht dabei,
 * wie weit es ging.
 */
export function StageBadge({
    application,
    stages,
}: {
    application: Application;
    stages: StageOption[];
}) {
    if (application.stage === 'rejected') {
        return (
            <span className="inline-flex flex-wrap items-center gap-1.5">
                <Badge variant="destructive">Abgesagt</Badge>
                {application.highest_stage && (
                    <span className="text-muted-foreground text-xs">
                        nach „{stageLabel(stages, application.highest_stage)}“
                    </span>
                )}
            </span>
        );
    }

    return (
        <Badge variant="secondary">
            {stageLabel(stages, application.stage)}
        </Badge>
    );
}
