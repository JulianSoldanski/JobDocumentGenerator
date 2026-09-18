import type { SVGAttributes } from 'react';

/** Ein Blatt mit Textzeilen — das, was die App erzeugt. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path
                fillRule="evenodd"
                d="M6 2h7v5a2.5 2.5 0 0 0 2.5 2.5H20V20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm2 11.25h8v1.5H8Zm0 3.5h8v1.5H8Zm0-7h3.5v1.5H8Z"
            />
            <path d="M14.75 2.4 19.6 7.25h-4.1a.75.75 0 0 1-.75-.75Z" />
        </svg>
    );
}
