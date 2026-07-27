import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';

// Avisos globales (flash de sesión). El backend ya compartía flash.message /
// flash.error / flash.status por Inertia, pero NINGÚN componente los leía: los
// errores se perdían y las acciones fallidas parecían no hacer nada (p. ej. el
// límite de proyectos del plan gratis dejaba el botón girando sin explicar nada).
type Flash = { message?: string | null; error?: string | null; status?: string | null };

const STYLES = {
    error:   'bg-red-950/90 border-red-800 text-red-100',
    success: 'bg-emerald-950/90 border-emerald-800 text-emerald-100',
} as const;

export default function FlashMessages() {
    const { flash } = usePage().props as unknown as { flash?: Flash };
    const reduce = useReducedMotion();
    const [visible, setVisible] = useState<{ kind: keyof typeof STYLES; text: string } | null>(null);

    const error = flash?.error ?? null;
    const success = flash?.message ?? flash?.status ?? null;

    useEffect(() => {
        const next = error ? { kind: 'error' as const, text: error }
            : success ? { kind: 'success' as const, text: success }
            : null;
        setVisible(next);
        if (!next) return;
        // Los errores se quedan hasta que el usuario los cierre; los éxitos se van solos.
        if (next.kind === 'success') {
            const t = setTimeout(() => setVisible(null), 6000);
            return () => clearTimeout(t);
        }
    }, [error, success]);

    return (
        <div className="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 max-w-md print:hidden">
            <AnimatePresence>
                {visible && (
                    <motion.div
                        key={visible.text}
                        initial={reduce ? false : { opacity: 0, y: 12 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={reduce ? { opacity: 0 } : { opacity: 0, y: 12 }}
                        role={visible.kind === 'error' ? 'alert' : 'status'}
                        aria-live={visible.kind === 'error' ? 'assertive' : 'polite'}
                        className={`flex items-start gap-3 border rounded-xl px-4 py-3 shadow-2xl backdrop-blur ${STYLES[visible.kind]}`}
                    >
                        <span className="text-sm leading-relaxed flex-1">{visible.text}</span>
                        <button
                            onClick={() => setVisible(null)}
                            aria-label="Cerrar aviso"
                            className="text-lg leading-none opacity-70 hover:opacity-100 transition-opacity"
                        >
                            ×
                        </button>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}
