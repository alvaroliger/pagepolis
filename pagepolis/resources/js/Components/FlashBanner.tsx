import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';

interface FlashPageProps {
    flash?: { message?: string | null; error?: string | null };
}

// Muestra los mensajes flash que el backend guarda en sesión (p.ej. "límite de
// proyectos del plan gratuito alcanzado" en ProjectController::store). Antes
// se compartían via Inertia (HandleInertiaRequests) pero ningún componente los
// leía, así que el usuario se quedaba sin explicación ante un fallo silencioso.
export default function FlashBanner() {
    const { props } = usePage<FlashPageProps>();
    const message = props.flash?.message ?? null;
    const error = props.flash?.error ?? null;
    const reduce = useReducedMotion();
    const [dismissed, setDismissed] = useState(false);

    useEffect(() => {
        setDismissed(false);
        if (!message && !error) return;
        const timer = setTimeout(() => setDismissed(true), 6000);
        return () => clearTimeout(timer);
    }, [message, error]);

    const text = error ?? message;
    const visible = !!text && !dismissed;
    const isError = !!error;

    return (
        <AnimatePresence>
            {visible && (
                <motion.div
                    initial={reduce ? false : { opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={reduce ? undefined : { opacity: 0, y: -10 }}
                    transition={{ duration: 0.2, ease: 'easeOut' }}
                    role={isError ? 'alert' : 'status'}
                    className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4"
                >
                    <div
                        className={`flex items-start justify-between gap-3 p-3 rounded-xl border text-sm ${
                            isError
                                ? 'bg-red-900/20 border-red-800/50 text-red-400'
                                : 'bg-green-900/40 border-green-800/50 text-green-400'
                        }`}
                    >
                        <p>{text}</p>
                        <button
                            onClick={() => setDismissed(true)}
                            aria-label="Cerrar aviso"
                            className="shrink-0 opacity-70 hover:opacity-100 transition-opacity leading-none text-lg"
                        >
                            ×
                        </button>
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
