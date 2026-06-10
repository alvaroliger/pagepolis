interface Props {
    size?: number;
    className?: string;
}

export default function PagepolisLogo({ size = 36, className = '' }: Props) {
    const id = 'pp-grad';
    return (
        <svg width={size} height={size} viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" className={className}>
            <defs>
                <linearGradient id={id} x1="0" y1="0" x2="36" y2="36" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stopColor="#8b5cf6" />
                    <stop offset="50%" stopColor="#d946ef" />
                    <stop offset="100%" stopColor="#06b6d4" />
                </linearGradient>
            </defs>
            {/* Outer rounded square */}
            <rect x="2" y="2" width="32" height="32" rx="9" fill={`url(#${id})`} />
            {/* Page shape */}
            <rect x="9" y="8" width="14" height="18" rx="2" fill="white" fillOpacity="0.15" />
            <rect x="9" y="8" width="14" height="18" rx="2" stroke="white" strokeOpacity="0.6" strokeWidth="1.5" />
            {/* Lines on page */}
            <line x1="12" y1="13" x2="20" y2="13" stroke="white" strokeOpacity="0.8" strokeWidth="1.5" strokeLinecap="round" />
            <line x1="12" y1="17" x2="20" y2="17" stroke="white" strokeOpacity="0.5" strokeWidth="1.2" strokeLinecap="round" />
            <line x1="12" y1="21" x2="17" y2="21" stroke="white" strokeOpacity="0.5" strokeWidth="1.2" strokeLinecap="round" />
            {/* AI spark */}
            <path d="M22 20 L25 17 L24 22 L27 19 L24 22 L25 27 L22 24 L19 27 L20 22 L17 25 L20 22 L17 20 L20 23 L22 20Z"
                fill="white" fillOpacity="0.0" />
            <path d="M24 18 L25.5 22 L29 22 L26.5 24.5 L27.5 28 L24 26 L20.5 28 L21.5 24.5 L19 22 L22.5 22 Z"
                fill="white" fillOpacity="0.9" />
        </svg>
    );
}
