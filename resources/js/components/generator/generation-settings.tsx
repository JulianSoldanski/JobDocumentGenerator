import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { LANGUAGE_LABELS, LANGUAGES } from '@/lib/languages';
import type { DocumentLayout, GenerationScope, Language } from '@/types';

const LAYOUTS: Record<DocumentLayout, string> = {
    modern: 'Modern',
    sidebar: 'Sidebar',
    classic: 'Classic',
};

const SCOPES: Record<GenerationScope, string> = {
    both: 'Lebenslauf und Anschreiben',
    cv: 'nur Lebenslauf',
    letter: 'nur Anschreiben',
};

type Props = {
    language: Language;
    layout: DocumentLayout;
    scope: GenerationScope;
    notes: string;
    /** Eine Auswahl hat kein Verlassen des Feldes — sie speichert sofort. */
    onSelect: (
        values: Partial<{
            language: Language;
            layout: DocumentLayout;
            scope: GenerationScope;
        }>,
    ) => void;
    onNotesChange: (value: string) => void;
    onBlur: () => void;
};

/**
 * Was vor dem Generieren feststeht. Die Sprache entscheidet, welcher der
 * beiden Profiltexte verwendet wird — übersetzt wird nichts.
 */
export function GenerationSettings({
    language,
    layout,
    scope,
    notes,
    onSelect,
    onNotesChange,
    onBlur,
}: Props) {
    return (
        <div className="space-y-4">
            {/* Die Spalte ist schmal: Sprache und Layout teilen sich eine Zeile,
                der Umfang — der längste Wert — bekommt eine eigene. */}
            <div className="grid grid-cols-2 gap-4">
                <div className="grid min-w-0 gap-2">
                    <Label htmlFor="language">Sprache</Label>
                    <Select
                        value={language}
                        onValueChange={(value) =>
                            onSelect({ language: value as Language })
                        }
                    >
                        <SelectTrigger id="language" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {LANGUAGES.map((option) => (
                                <SelectItem key={option} value={option}>
                                    {LANGUAGE_LABELS[option]}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="grid min-w-0 gap-2">
                    <Label htmlFor="layout">Layout</Label>
                    <Select
                        value={layout}
                        onValueChange={(value) =>
                            onSelect({ layout: value as DocumentLayout })
                        }
                    >
                        <SelectTrigger id="layout" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {Object.entries(LAYOUTS).map(([value, label]) => (
                                <SelectItem key={value} value={value}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="col-span-2 grid min-w-0 gap-2">
                    <Label htmlFor="scope">Umfang</Label>
                    <Select
                        value={scope}
                        onValueChange={(value) =>
                            onSelect({ scope: value as GenerationScope })
                        }
                    >
                        <SelectTrigger id="scope" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {Object.entries(SCOPES).map(([value, label]) => (
                                <SelectItem key={value} value={value}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="notes">Hinweise</Label>
                <Textarea
                    id="notes"
                    rows={2}
                    value={notes}
                    placeholder={
                        '„betone den Data-Teil“, „erwähne den Umzug nach Berlin“'
                    }
                    onChange={(event) => onNotesChange(event.target.value)}
                    onBlur={onBlur}
                />
            </div>
        </div>
    );
}
