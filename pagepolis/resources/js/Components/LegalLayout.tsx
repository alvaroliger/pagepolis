import { Head, Link } from '@inertiajs/react';
import { ReactNode } from 'react';
import PagepolisLogo from '@/Components/PagepolisLogo';

interface Props {
    title: string;
    updated: string;
    children: ReactNode;
}

export default function LegalLayout({ title, updated, children }: Props) {
    return (
        <div className="min-h-screen bg-gray-950 text-gray-300">
            <Head title={title} />

            <header className="border-b border-gray-800/80">
                <div className="max-w-3xl mx-auto px-4 h-16 flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-2">
                        <PagepolisLogo size={30} />
                        <span className="font-black text-white text-lg">Pagepolis</span>
                    </Link>
                    <Link href="/" className="text-sm text-gray-400 hover:text-white transition-colors">
                        ← Inicio
                    </Link>
                </div>
            </header>

            <main className="max-w-3xl mx-auto px-4 py-12">
                <h1 className="text-3xl sm:text-4xl font-black text-white mb-2">{title}</h1>
                <p className="text-sm text-gray-500 mb-10">Última actualización: {updated}</p>

                <article className="legal-content space-y-6 leading-relaxed">
                    {children}
                </article>

                <div className="mt-16 pt-8 border-t border-gray-800 text-sm text-gray-500 flex flex-wrap gap-x-6 gap-y-2">
                    <Link href="/terminos" className="hover:text-white transition-colors">Términos de servicio</Link>
                    <Link href="/privacidad" className="hover:text-white transition-colors">Política de privacidad</Link>
                    <a href="mailto:hola@pagepolis.com" className="hover:text-white transition-colors">hola@pagepolis.com</a>
                </div>
            </main>
        </div>
    );
}

/** Encabezado de sección reutilizable para las páginas legales. */
export function LegalSection({ n, title, children }: { n: number; title: string; children: ReactNode }) {
    return (
        <section>
            <h2 className="text-xl font-bold text-white mb-3">{n}. {title}</h2>
            <div className="space-y-3 text-[15px]">{children}</div>
        </section>
    );
}
