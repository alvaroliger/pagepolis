import { PropsWithChildren, useRef, MouseEvent } from 'react';
import { motion, useReducedMotion } from 'framer-motion';

/*
 * Primitivas de motion de Pagepolis — mismas curvas y el mismo lenguaje 3D
 * que las webs generadas (.reveal y .tilt-3d de database/templates/base.css),
 * pero en React con framer-motion. Todas respetan prefers-reduced-motion.
 */

const EASE = [0.22, 1, 0.36, 1] as const;

interface RevealProps extends PropsWithChildren {
    className?: string;
    /** Retardo en segundos (para escalonar grids). */
    delay?: number;
    /** Desplazamiento vertical inicial en px. */
    y?: number;
}

/** Aparece con fade + subida al entrar en el viewport (una sola vez). */
export function Reveal({ children, className, delay = 0, y = 26 }: RevealProps) {
    const reduce = useReducedMotion();
    return (
        <motion.div
            className={className}
            initial={reduce ? false : { opacity: 0, y }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: '-60px 0px' }}
            transition={{ duration: 0.65, delay, ease: EASE }}
        >
            {children}
        </motion.div>
    );
}

/** Igual que Reveal pero anima al montar (para contenido above-the-fold). */
export function FadeIn({ children, className, delay = 0, y = 22 }: RevealProps) {
    const reduce = useReducedMotion();
    return (
        <motion.div
            className={className}
            initial={reduce ? false : { opacity: 0, y }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.7, delay, ease: EASE }}
        >
            {children}
        </motion.div>
    );
}

interface TiltCardProps extends PropsWithChildren {
    className?: string;
    /** Inclinación máxima en grados. */
    max?: number;
}

/**
 * Tarjeta con inclinación 3D al ratón — equivalente React de la clase
 * .tilt-3d de las plantillas generadas. Sin re-renders: escribe el
 * transform directamente en el nodo. Se desactiva con reduced-motion
 * y en dispositivos sin hover (el transform simplemente no se aplica).
 */
export function TiltCard({ children, className = '', max = 6 }: TiltCardProps) {
    const ref = useRef<HTMLDivElement>(null);
    const reduce = useReducedMotion();

    const onMove = (e: MouseEvent<HTMLDivElement>) => {
        const el = ref.current;
        if (!el || reduce) return;
        const rect = el.getBoundingClientRect();
        const px = (e.clientX - rect.left) / rect.width - 0.5;
        const py = (e.clientY - rect.top) / rect.height - 0.5;
        el.style.transform = `perspective(900px) rotateX(${(-py * max).toFixed(2)}deg) rotateY(${(px * max).toFixed(2)}deg) translateZ(0)`;
    };

    const onLeave = () => {
        const el = ref.current;
        if (el) el.style.transform = '';
    };

    return (
        <div
            ref={ref}
            className={className}
            onMouseMove={onMove}
            onMouseLeave={onLeave}
            style={{
                transformStyle: 'preserve-3d',
                transition: 'transform .35s cubic-bezier(.2,.8,.2,1), box-shadow .35s ease',
                willChange: 'transform',
            }}
        >
            {children}
        </div>
    );
}
