import { useEffect, useRef, useState } from 'react';

/** Das Blatt ist am Bildschirm 210 mm breit — dazu etwas Rand für den Schatten. */
const SHEET_WIDTH = 820;

/** Eine A4-Seite am Bildschirm, samt Rand oben und unten. */
const PAGE_HEIGHT = 1200;

/** Größer wird die Schrift unangenehm — dann lieber Rand links und rechts. */
const MAX_SCALE = 1.5;

/**
 * „Seitenbreite" füllt die Spalte und lässt scrollen; „Ganze Seite" zeigt
 * eine Seite vollständig, zum Überblick.
 */
export type PreviewFit = 'width' | 'page';

type Props = {
    src: string;
    title: string;
    fit: PreviewFit;
};

/**
 * Das Dokument so, wie der Server es rendert — derselbe Code, der später das
 * PDF erzeugt. Das A4-Blatt wird auf den verfügbaren Platz skaliert.
 */
export function DocumentPreview({ src, title, fit }: Props) {
    const container = useRef<HTMLDivElement>(null);
    const frame = useRef<HTMLIFrameElement>(null);
    const [box, setBox] = useState({ width: SHEET_WIDTH, height: PAGE_HEIGHT });
    const [height, setHeight] = useState(1160);

    useEffect(() => {
        const element = container.current;

        if (!element) {
            return;
        }

        const observer = new ResizeObserver(([entry]) =>
            setBox({
                width: entry.contentRect.width,
                height: entry.contentRect.height,
            }),
        );

        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    const byWidth = Math.min(MAX_SCALE, box.width / SHEET_WIDTH);
    const scale =
        fit === 'page' ? Math.min(byWidth, box.height / PAGE_HEIGHT) : byWidth;

    // Ein Lebenslauf kann zwei Seiten lang sein; die Höhe kommt vom Inhalt.
    const measure = () => {
        const page = frame.current?.contentDocument?.documentElement;

        if (page) {
            setHeight(page.scrollHeight);
        }
    };

    return (
        <div
            ref={container}
            className="[scrollbar-gutter:stable] overflow-auto rounded-lg border bg-[#f3f4f6] lg:min-h-0 lg:flex-1"
        >
            <div
                className="mx-auto"
                style={{ width: SHEET_WIDTH * scale, height: height * scale }}
            >
                <iframe
                    ref={frame}
                    src={src}
                    title={title}
                    onLoad={measure}
                    className="origin-top-left border-0"
                    style={{
                        width: SHEET_WIDTH,
                        height,
                        transform: `scale(${scale})`,
                    }}
                />
            </div>
        </div>
    );
}
