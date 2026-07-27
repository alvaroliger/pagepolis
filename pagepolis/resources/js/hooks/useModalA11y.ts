import { useEffect, useRef } from 'react';

const FOCUSABLE = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Accesibilidad mínima para modales: mueve el foco dentro del diálogo al
 * abrirse, lo mantiene contenido (Tab/Shift+Tab no se escapa a la página de
 * detrás), cierra con Escape y devuelve el foco al elemento que abrió el
 * modal cuando se cierra. Úsalo en el elemento con role="dialog".
 */
export function useModalA11y(onClose: () => void) {
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const trigger = document.activeElement as HTMLElement | null;
        const dialog = ref.current;
        const focusable = dialog?.querySelectorAll<HTMLElement>(FOCUSABLE);
        (focusable && focusable[0] ? focusable[0] : dialog)?.focus();

        function onKeyDown(e: KeyboardEvent) {
            if (e.key === 'Escape') {
                e.preventDefault();
                onClose();
                return;
            }
            if (e.key !== 'Tab' || !dialog) return;
            const items = dialog.querySelectorAll<HTMLElement>(FOCUSABLE);
            if (items.length === 0) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }

        document.addEventListener('keydown', onKeyDown);
        return () => {
            document.removeEventListener('keydown', onKeyDown);
            trigger?.focus();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return ref;
}
