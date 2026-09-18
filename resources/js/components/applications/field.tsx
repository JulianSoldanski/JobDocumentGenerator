import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';

/** Beschriftung, Eingabe, Fehlermeldung — die Form jedes Formularfelds. */
export function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid min-w-0 gap-2">
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
