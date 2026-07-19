import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

/*
 * EmptyState — icono + título + descripción + CTA opcional, para las
 * secciones que aún no tienen datos (mensajes, analítica...). El propio
 * contenedor (tarjeta, padding) lo pone cada página según el contexto;
 * este componente solo estandariza el contenido para que todas las
 * pantallas "vacías" se vean y se comporten igual.
 */
interface Props {
    icon: LucideIcon;
    title: string;
    description?: string;
    cta?: { label: string; href: string };
}

export default function EmptyState({ icon: Icon, title, description, cta }: Props) {
    return (
        <div className="text-center">
            <div className="w-14 h-14 bg-gradient-to-br from-violet-600/20 to-fuchsia-600/20 border border-violet-700/30 rounded-2xl mx-auto mb-5 flex items-center justify-center">
                <Icon className="w-7 h-7 text-violet-400" strokeWidth={1.5} />
            </div>
            <h2 className="text-lg font-bold text-white">{title}</h2>
            {description && (
                <p className="text-gray-400 text-sm mt-2 max-w-md mx-auto">{description}</p>
            )}
            {cta && (
                <Link
                    href={cta.href}
                    className="inline-block mt-6 bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:opacity-90 text-white px-6 py-2.5 rounded-xl font-semibold transition-all text-sm shadow-lg shadow-violet-900/40 hover:-translate-y-0.5"
                >
                    {cta.label}
                </Link>
            )}
        </div>
    );
}
