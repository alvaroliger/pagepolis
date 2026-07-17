import { useState } from 'react';
import { Copy, Check } from 'lucide-react';

interface Props {
    /** Texto que se copiará al portapapeles (normalmente una URL). */
    text: string;
    className?: string;
    label?: string;
    /** Oculta visualmente el texto del label (queda solo el icono), pero lo conserva para lectores de pantalla. */
    iconOnly?: boolean;
}

/*
 * Botón de copiar al portapapeles con feedback visual (icono + texto
 * cambian a "Copiado" un par de segundos). Usa la Clipboard API y cae a
 * document.execCommand como respaldo para contextos sin HTTPS/permiso.
 */
export default function CopyButton({ text, className = '', label = 'Copiar', iconOnly = false }: Props) {
    const [copied, setCopied] = useState(false);

    const copy = async () => {
        try {
            if (navigator.clipboard) {
                await navigator.clipboard.writeText(text);
            } else {
                throw new Error('sin Clipboard API');
            }
        } catch {
            const el = document.createElement('textarea');
            el.value = text;
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            try { document.execCommand('copy'); } catch { /* no-op */ }
            document.body.removeChild(el);
        }
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <button
            type="button"
            onClick={copy}
            aria-label={copied ? 'Enlace copiado' : label}
            className={`inline-flex items-center gap-1.5 transition-colors ${className}`}
        >
            {copied ? <Check className="w-4 h-4" strokeWidth={2} /> : <Copy className="w-4 h-4" strokeWidth={1.75} />}
            <span className={iconOnly ? 'sr-only' : undefined}>{copied ? '¡Copiado!' : label}</span>
        </button>
    );
}
