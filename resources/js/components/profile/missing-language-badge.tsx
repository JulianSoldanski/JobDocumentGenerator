import { Badge } from '@/components/ui/badge';
import type { Language } from '@/types';

/**
 * Wo eine Sprache fehlt, wird das sichtbar markiert. Übersetzt wird nichts —
 * der Leser sieht im Zweifel den Text, den der Nutzer geschrieben hat.
 */
export function MissingLanguageBadge({ missing }: { missing: Language[] }) {
    if (missing.length === 0) {
        return null;
    }

    return (
        <span className="flex gap-1">
            {missing.map((language) => (
                <Badge
                    key={language}
                    variant="outline"
                    className="text-muted-foreground border-dashed text-[10px] uppercase"
                    title="Dieser Eintrag ist in dieser Sprache noch nicht geschrieben."
                >
                    {language} fehlt
                </Badge>
            ))}
        </span>
    );
}
