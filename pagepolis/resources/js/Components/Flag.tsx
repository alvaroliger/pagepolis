import { useId } from 'react';

interface Props {
    code: string;
    className?: string;
    size?: number;
}

/**
 * Banderas SVG (se renderizan en todos los sistemas, a diferencia de los emoji
 * de bandera que en Windows aparecen como "ES", "GB", etc.).
 */
export default function Flag({ code, className = '', size = 20 }: Props) {
    const uid = useId();
    const clip = `flagclip-${uid}`;

    const shapes: Record<string, JSX.Element> = {
        es: (
            <>
                <rect width="20" height="14" fill="#AA151B" />
                <rect y="3.5" width="20" height="7" fill="#F1BF00" />
            </>
        ),
        en: (
            <>
                <rect width="20" height="14" fill="#012169" />
                <path d="M0,0 20,14 M20,0 0,14" stroke="#fff" strokeWidth="2.8" />
                <path d="M0,0 20,14 M20,0 0,14" stroke="#C8102E" strokeWidth="1.2" />
                <path d="M10,0 V14 M0,7 H20" stroke="#fff" strokeWidth="3.5" />
                <path d="M10,0 V14 M0,7 H20" stroke="#C8102E" strokeWidth="2" />
            </>
        ),
        fr: (
            <>
                <rect width="20" height="14" fill="#fff" />
                <rect width="6.67" height="14" fill="#0055A4" />
                <rect x="13.33" width="6.67" height="14" fill="#EF4135" />
            </>
        ),
        de: (
            <>
                <rect width="20" height="14" fill="#FFCE00" />
                <rect width="20" height="4.67" fill="#000" />
                <rect y="4.67" width="20" height="4.67" fill="#DD0000" />
            </>
        ),
        pt: (
            <>
                <rect width="20" height="14" fill="#009C3B" />
                <path d="M10,1.4 18.6,7 10,12.6 1.4,7 Z" fill="#FFDF00" />
                <circle cx="10" cy="7" r="2.9" fill="#002776" />
            </>
        ),
        it: (
            <>
                <rect width="20" height="14" fill="#fff" />
                <rect width="6.67" height="14" fill="#009246" />
                <rect x="13.33" width="6.67" height="14" fill="#CE2B37" />
            </>
        ),
    };

    return (
        <svg
            viewBox="0 0 20 14"
            width={size}
            height={(size * 14) / 20}
            className={`inline-block rounded-[2px] ring-1 ring-black/10 ${className}`}
            aria-hidden="true"
        >
            <defs>
                <clipPath id={clip}>
                    <rect width="20" height="14" rx="2" />
                </clipPath>
            </defs>
            <g clipPath={`url(#${clip})`}>{shapes[code] ?? shapes.es}</g>
        </svg>
    );
}
