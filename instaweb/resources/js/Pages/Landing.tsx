import { Head, Link } from '@inertiajs/react';

export default function Landing() {
    return (
        <>
            <Head title="InstaWeb — Crea tu web con IA en segundos" />

            {/* Nav */}
            <nav className="fixed top-0 left-0 right-0 z-50 border-b border-gray-800/50 bg-gray-950/90 backdrop-blur-lg">
                <div className="max-w-7xl mx-auto px-6 flex justify-between items-center h-16">
                    <span className="text-xl font-black bg-gradient-to-r from-violet-500 to-cyan-400 bg-clip-text text-transparent">
                        InstaWeb
                    </span>
                    <div className="flex items-center gap-6">
                        <Link href="/plantillas" className="text-sm text-gray-400 hover:text-white transition-colors hidden sm:block">
                            Plantillas
                        </Link>
                        <Link href="/login" className="text-sm text-gray-400 hover:text-white transition-colors">
                            Iniciar sesión
                        </Link>
                        <Link
                            href="/register"
                            className="bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
                        >
                            Empezar gratis
                        </Link>
                    </div>
                </div>
            </nav>

            <div className="bg-gray-950 text-white min-h-screen">

                {/* Hero */}
                <section className="pt-32 pb-24 px-6 text-center">
                    <div className="max-w-4xl mx-auto">
                        <div className="inline-flex items-center gap-2 bg-violet-950/60 border border-violet-800/50 text-violet-300 text-xs font-semibold px-4 py-2 rounded-full mb-8">
                            <span className="w-2 h-2 bg-violet-400 rounded-full animate-pulse"></span>
                            Impulsado por Claude AI · Generación en segundos
                        </div>

                        <h1 className="text-5xl sm:text-7xl font-black mb-6 leading-tight">
                            Tu web profesional,{' '}
                            <span className="bg-gradient-to-r from-violet-500 to-cyan-400 bg-clip-text text-transparent">
                                creada por IA
                            </span>
                        </h1>

                        <p className="text-xl text-gray-400 mb-10 max-w-2xl mx-auto leading-relaxed">
                            Elige una plantilla, describe tu negocio en lenguaje natural y la IA genera
                            tu sitio web completo en segundos. Sin código, sin diseñador.
                        </p>

                        <div className="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                            <Link
                                href="/register"
                                className="bg-gradient-to-r from-violet-600 to-violet-500 text-white px-8 py-4 rounded-xl font-bold text-lg hover:opacity-90 transition-opacity"
                            >
                                Crear mi web gratis →
                            </Link>
                            <Link
                                href="/plantillas"
                                className="border border-gray-700 text-gray-300 px-8 py-4 rounded-xl font-semibold text-lg hover:border-violet-600 hover:text-white transition-colors"
                            >
                                Ver plantillas
                            </Link>
                        </div>

                        <p className="text-sm text-gray-600">
                            ✓ 7 días gratis &nbsp;·&nbsp; ✓ Sin tarjeta de crédito &nbsp;·&nbsp; ✓ Cancela cuando quieras
                        </p>
                    </div>
                </section>

                {/* Cómo funciona */}
                <section className="py-24 px-6 bg-gray-900/50">
                    <div className="max-w-5xl mx-auto">
                        <h2 className="text-4xl font-black text-center mb-4">¿Cómo funciona?</h2>
                        <p className="text-gray-400 text-center mb-16">Tres pasos. Menos de 5 minutos.</p>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {[
                                { n: '01', title: 'Elige una plantilla', desc: 'Más de 20 diseños profesionales para cualquier tipo de negocio: restaurante, portfolio, tienda, SaaS...', icon: '🎨' },
                                { n: '02', title: 'Describe tu negocio', desc: 'Escribe en español qué hace tu negocio, qué colores quieres, qué secciones necesitas. La IA lo entiende todo.', icon: '✍️' },
                                { n: '03', title: 'Publica con tu dominio', desc: 'Un subdominio gratis o tu propio dominio .com. El sitio queda online en segundos tras el pago.', icon: '🚀' },
                            ].map(step => (
                                <div key={step.n} className="bg-gray-900 border border-gray-800 rounded-2xl p-8 relative">
                                    <span className="text-6xl font-black text-gray-800 absolute top-6 right-6">{step.n}</span>
                                    <div className="text-4xl mb-4">{step.icon}</div>
                                    <h3 className="text-xl font-bold mb-3">{step.title}</h3>
                                    <p className="text-gray-400 text-sm leading-relaxed">{step.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Precios */}
                <section className="py-24 px-6">
                    <div className="max-w-4xl mx-auto text-center">
                        <h2 className="text-4xl font-black mb-4">Precios simples</h2>
                        <p className="text-gray-400 mb-16">Sin sorpresas. Todo incluido.</p>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto">
                            <div className="bg-gray-900 border border-gray-800 rounded-2xl p-8 text-left">
                                <div className="text-gray-400 text-sm font-semibold uppercase tracking-wider mb-2">Mensual</div>
                                <div className="text-5xl font-black mb-1">14,99€</div>
                                <div className="text-gray-500 text-sm mb-6">por mes</div>
                                <ul className="space-y-3 text-sm text-gray-300 mb-8">
                                    {['Proyectos ilimitados', 'IA ilimitada', 'Subdominio gratis', 'Soporte por email'].map(f => (
                                        <li key={f} className="flex items-center gap-2">
                                            <span className="text-green-400">✓</span> {f}
                                        </li>
                                    ))}
                                </ul>
                                <Link href="/register" className="block text-center border border-gray-700 text-white py-3 rounded-xl hover:border-violet-600 transition-colors font-semibold">
                                    Empezar
                                </Link>
                            </div>

                            <div className="bg-gradient-to-b from-violet-900/40 to-gray-900 border border-violet-700/50 rounded-2xl p-8 text-left relative">
                                <div className="absolute -top-3 left-1/2 -translate-x-1/2 bg-violet-600 text-white text-xs font-bold px-4 py-1 rounded-full">
                                    AHORRA 33%
                                </div>
                                <div className="text-violet-300 text-sm font-semibold uppercase tracking-wider mb-2">Anual</div>
                                <div className="text-5xl font-black mb-1">9,99€</div>
                                <div className="text-gray-500 text-sm mb-6">por mes · 119,88€/año</div>
                                <ul className="space-y-3 text-sm text-gray-300 mb-8">
                                    {['Todo lo del plan mensual', 'Dominio propio incluido', 'Prioridad en soporte', '2 meses gratis'].map(f => (
                                        <li key={f} className="flex items-center gap-2">
                                            <span className="text-green-400">✓</span> {f}
                                        </li>
                                    ))}
                                </ul>
                                <Link href="/register" className="block text-center bg-violet-600 text-white py-3 rounded-xl hover:bg-violet-500 transition-colors font-semibold">
                                    Empezar · 7 días gratis
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA Final */}
                <section className="py-24 px-6 bg-gradient-to-r from-violet-950/40 to-cyan-950/20 border-t border-gray-800">
                    <div className="max-w-2xl mx-auto text-center">
                        <h2 className="text-4xl font-black mb-4">¿Listo para empezar?</h2>
                        <p className="text-gray-400 mb-8">Crea tu primera web en menos de 5 minutos. Gratis.</p>
                        <Link
                            href="/register"
                            className="inline-block bg-gradient-to-r from-violet-600 to-cyan-500 text-white px-10 py-4 rounded-xl font-bold text-lg hover:opacity-90 transition-opacity"
                        >
                            Crear mi web gratis →
                        </Link>
                    </div>
                </section>

                <footer className="border-t border-gray-800 py-8 px-6 text-center text-sm text-gray-600">
                    <p>© 2025 InstaWeb · <Link href="/login" className="hover:text-gray-400">Iniciar sesión</Link> · <Link href="/register" className="hover:text-gray-400">Registrarse</Link></p>
                </footer>
            </div>
        </>
    );
}
