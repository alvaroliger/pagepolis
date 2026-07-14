import { ReactNode, useEffect, useRef } from 'react';

/*
 * Modal accesible reutilizable — hasta ahora cada modal de la app (preview
 * de plantillas, confirmaciones del editor, borrar proyecto...) era un div
 * suelto sin role="dialog", sin cierre con Escape y sin devolver el foco al
 * cerrarse. Centraliza eso una sola vez: teclado (Escape), foco inicial en
 * el diálogo, foco devuelto al disparador al desmontar, y scroll del fondo
 * bloqueado mientras está abierto.
 */

interface ModalProps {
    onClose: () => void;
    children: ReactNode;
    /** Clases del contenedor del diálogo (fondo, tamaño, padding...). */
    className?: string;
    /** Clases del overlay de fondo. */
    backdropClassName?: string;
    /** id del elemento que hace de título del diálogo, para aria-labelledby. */
    labelledBy?: string;
    /** Texto accesible del diálogo cuando no hay un título visible con id. */
    label?: string;
    /** Si true, un click en el fondo también cierra (por defecto no, para no perder confirmaciones por accidente). */
    closeOnBackdrop?: boolean;
}

export default function Modal({
    onClose,
    children,
    className = '',
    backdropClassName = 'fixed inset-0 bg-black/60 flex items-center justify-center z-50 px-4',
    labelledBy,
    label,
    closeOnBackdrop = false,
}: ModalProps) {
    const dialogRef = useRef<HTMLDivElement>(null);
    const onCloseRef = useRef(onClose);
    onCloseRef.current = onClose;

    useEffect(() => {
        const previouslyFocused = document.activeElement as HTMLElement | null;
        const dialog = dialogRef.current;
        const focusable = dialog?.querySelector<HTMLElement>(
            'button, a[href], input, textarea, select, [tabindex]:not([tabindex="-1"])'
        );
        (focusable ?? dialog)?.focus();

        function onKeyDown(e: KeyboardEvent) {
            if (e.key === 'Escape') onCloseRef.current();
        }
        document.addEventListener('keydown', onKeyDown);

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = previousOverflow;
            previouslyFocused?.focus?.();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <div className={backdropClassName} onClick={closeOnBackdrop ? onClose : undefined}>
            <div
                ref={dialogRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby={labelledBy}
                aria-label={labelledBy ? undefined : label}
                tabIndex={-1}
                className={className}
                onClick={e => e.stopPropagation()}
            >
                {children}
            </div>
        </div>
    );
}
