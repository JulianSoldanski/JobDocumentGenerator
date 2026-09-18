import type { ApplicationStage, StageOption } from '@/types';

const DAY = 24 * 60 * 60 * 1000;

export function stageLabel(
    stages: StageOption[],
    value: ApplicationStage | null,
): string {
    return stages.find((stage) => stage.value === value)?.label ?? '';
}

/** „heute", „seit 1 Tag", „seit 12 Tagen" — wie lange etwas schon liegt. */
export function since(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const days = Math.floor((Date.now() - new Date(iso).getTime()) / DAY);

    if (days < 1) {
        return 'seit heute';
    }

    return days === 1 ? 'seit 1 Tag' : `seit ${days} Tagen`;
}

/** „heute", „gestern", „vor 3 Tagen" — wann etwas geschah. */
export function ago(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const days = Math.floor((Date.now() - new Date(iso).getTime()) / DAY);

    return days < 1 ? 'heute' : days === 1 ? 'gestern' : `vor ${days} Tagen`;
}

/** Recherchezeit lesbar: „45 Min.", „1 Std. 20 Min.". */
export function duration(seconds: number): string {
    const minutes = Math.round(seconds / 60);

    if (minutes < 60) {
        return `${minutes} Min.`;
    }

    const rest = minutes % 60;

    return `${Math.floor(minutes / 60)} Std.${rest ? ` ${rest} Min.` : ''}`;
}

export function date(iso: string | null): string {
    return iso
        ? new Date(iso).toLocaleDateString('de-DE', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
          })
        : '';
}

/** Heute als Wert für ein Datumsfeld. */
export function today(): string {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60 * 1000;

    return new Date(now.getTime() - offset).toISOString().slice(0, 10);
}
