import { useEffect } from 'react';

/**
 * Comportamiento estándar de modal: cerrar con Escape y bloquear el scroll
 * del fondo mientras está abierto (evita que la página se desplace detrás
 * del overlay en móvil/desktop).
 */
export default function useModalKeyboard(isOpen: boolean, onClose: () => void) {
    useEffect(() => {
        if (!isOpen) return;

        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        document.addEventListener('keydown', onKeyDown);

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = previousOverflow;
        };
    }, [isOpen, onClose]);
}
