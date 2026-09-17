import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { LANGUAGE_LABELS, LANGUAGES } from '@/lib/languages';
import type { Language } from '@/types';

type Props = {
    value: Language;
    onChange: (language: Language) => void;
    /** Sprachen, in denen der Eintrag noch nicht geschrieben ist. */
    missing?: Language[];
};

export function LanguageTabs({ value, onChange, missing = [] }: Props) {
    return (
        <div className="bg-muted inline-flex rounded-md p-1" role="tablist">
            {LANGUAGES.map((language) => (
                <Button
                    key={language}
                    type="button"
                    role="tab"
                    aria-selected={value === language}
                    size="sm"
                    variant="ghost"
                    onClick={() => onChange(language)}
                    className={cn('h-7 px-3 text-xs', {
                        'bg-background shadow-xs': value === language,
                    })}
                >
                    {LANGUAGE_LABELS[language]}
                    {missing.includes(language) && (
                        <span
                            className="text-muted-foreground ml-1"
                            title="In dieser Sprache noch nicht geschrieben"
                        >
                            •
                        </span>
                    )}
                </Button>
            ))}
        </div>
    );
}
