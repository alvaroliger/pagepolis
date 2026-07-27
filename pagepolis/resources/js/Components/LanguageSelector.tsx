import { useState, useRef, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { SUPPORTED_LANGS } from '@/i18n';
import Flag from '@/Components/Flag';

export default function LanguageSelector() {
    const { i18n } = useTranslation();
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    const current = SUPPORTED_LANGS.find(l => l.code === i18n.language) ?? SUPPORTED_LANGS[0];

    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    const select = (code: string) => {
        i18n.changeLanguage(code);
        localStorage.setItem('pagepolis_lang', code);
        setOpen(false);
    };

    return (
        <div ref={ref} className="relative">
            <button
                onClick={() => setOpen(o => !o)}
                aria-label={current.label}
                aria-haspopup="true"
                aria-expanded={open}
                className="flex items-center gap-1.5 text-sm text-gray-400 hover:text-white transition-colors px-2 py-1 rounded-lg hover:bg-gray-800"
            >
                <Flag code={current.code} size={20} />
                <span className="hidden sm:block">{current.label}</span>
                <svg className={`w-3 h-3 transition-transform ${open ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {open && (
                <div className="absolute right-0 mt-2 w-44 bg-gray-900 border border-gray-700 rounded-xl shadow-xl overflow-hidden z-50">
                    {SUPPORTED_LANGS.map(lang => (
                        <button
                            key={lang.code}
                            onClick={() => select(lang.code)}
                            className={`w-full flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-gray-800 transition-colors text-left ${lang.code === i18n.language ? 'text-violet-300 bg-violet-950/30' : 'text-gray-300'}`}
                        >
                            <Flag code={lang.code} size={20} />
                            <span>{lang.label}</span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
