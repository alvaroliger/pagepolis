import { useState, useEffect, useRef } from 'react';
import { Head, Link } from '@inertiajs/react';

const DEMOS = [
    { label: 'Restaurante',  prompt: 'restaurante italiano moderno en Madrid, colores cálidos, carta, reservas' },
    { label: 'Portfolio',    prompt: 'portfolio de diseñadora UX minimalista, proyectos, sobre mí, contacto' },
    { label: 'Tienda',       prompt: 'tienda online de ropa vintage, productos destacados, newsletter' },
    { label: 'SaaS',         prompt: 'landing de app de productividad, hero, features, pricing, testimonios' },
    { label: 'Clínica',      prompt: 'clínica dental profesional, servicios, equipo médico, cita previa' },
];

const FEATURES = [
    { icon: '⚡', title: 'Generación en segundos',   desc: 'Describe tu negocio en lenguaje natural. La IA genera HTML, CSS y JavaScript profesional al instante.' },
    { icon: '✏️', title: 'Editor en tiempo real',    desc: 'Edita el código directamente con syntax highlighting o pide más cambios a la IA. El preview se actualiza al momento.' },
    { icon: '🌐', title: 'Publicación automática',   desc: 'Elige un dominio propio o usa tu subdominio en pagepolis.com. El sitio queda online en segundos tras el pago.' },
    { icon: '🔒', title: 'Seguro y fiable',           desc: 'SSL incluido, infraestructura de alto rendimiento, backups automáticos y soporte en español.' },
    { icon: '📱', title: 'Siempre responsivo',        desc: 'Cada web generada es mobile-first. Vista previa en desktop, tablet y móvil antes de publicar.' },
    { icon: '🤖', title: 'IA que te entiende',        desc: 'Pide cambios en español: pon el fondo azul, añade sección de precios, hazlo más moderno.' },
];

const TESTIMONIALS = [
    { name: 'Carlos M.', role: 'Dueño de restaurante',  text: 'Tenía mi web lista en 10 minutos. La IA entendió exactamente lo que necesitaba. Mis clientes no paran de decirme lo bonita que está.', avatar: 'C', color: 'from-orange-500 to-rose-500' },
    { name: 'Laura S.',  role: 'Freelance diseñadora',   text: 'Lo uso para crear portfolios rápidos a mis clientes. El editor de código con highlighting es lo mejor, puedo retocar lo que genera la IA.', avatar: 'L', color: 'from-violet-500 to-pink-500' },
    { name: 'Marcos T.', role: 'Emprendedor',            text: 'Puse mi landing de SaaS en producción el mismo día que tuve la idea. Con Wix tardé semanas. Con Pagepolis, horas.', avatar: 'M', color: 'from-cyan-500 to-blue-500' },
];

const COMPARE = [
    { feature: 'Generación por IA',           pagepolis: true,        wix: false,       ss: false,     webflow: false  },
    { feature: 'Editor de código real',        pagepolis: true,        wix: false,       ss: false,     webflow: true   },
    { feature: 'Precio desde',                 pagepolis: '6,99€/mes', wix: '13€/mes',   ss: '16€/mes', webflow: '14€/mes' },
    { feature: 'Subdominio gratis',            pagepolis: true,        wix: true,        ss: false,     webflow: false  },
    { feature: 'SSL incluido',                 pagepolis: true,        wix: true,        ss: true,      webflow: true   },
    { feature: 'Sin comisiones',               pagepolis: true,        wix: false,       ss: false,     webflow: true   },
    { feature: 'Soporte en español',           pagepolis: true,        wix: true,        ss: false,     webflow: false  },
    { feature: 'Cambios por lenguaje natural', pagepolis: true,        wix: false,       ss: false,     webflow: false  },
];

function TypingAnimation() {
    const prompts = DEMOS.map(d => d.prompt);
    const [displayed, setDisplayed] = useState('');
    const [promptIdx, setPromptIdx] = useState(0);
    const [charIdx, setCharIdx]     = useState(0);
    const [deleting, setDeleting]   = useState(false);

    useEffect(() => {
        const current = prompts[promptIdx];
        const speed   = deleting ? 18 : 45;
        const timer = setTimeout(() => {
            if (!deleting) {
                if (charIdx < current.length) {
                    setDisplayed(current.slice(0, charIdx + 1));
                    setCharIdx(c => c + 1);
                } else {
                    setTimeout(() => setDeleting(true), 1800);
                }
            } else {
                if (charIdx > 0) {
                    setDisplayed(current.slice(0, charIdx - 1));
                    setCharIdx(c => c - 1);
                } else {
                    setDeleting(false);
                    setPromptIdx(i => (i + 1) % prompts.length);
                }
            }
        }, speed);
        return () => clearTimeout(timer);
    }, [charIdx, deleting, promptIdx]);

    return (
        <span className="text-violet-300 font-mono">
            {displayed}<span className="animate-pulse">|</span>
        </span>
    );
}

function Cell({ val }: { val: boolean | string }) {
    if (typeof val === 'boolean') {
        return val
            ? <span className="text-green-400 font-bold">✓</span>
            : <span className="text-gray-700">—</span>;
    }
    return <span className="font-semibold text-violet-300">{val}</span>;
}

export default function Landing() {
    const [cycle, setCycle] = useState<'monthly' | 'yearly'>('yearly');
    const pricingRef = useRef<HTMLElement>(null);

    return (
        <>
            <Head>
                <title>Pagepolis — The web that you get | Crea tu web con IA</title>
                <meta name="description" content="Pagepolis crea tu web profesional con IA en segundos. Describe tu negocio, edita con código real y publica con tu dominio. Desde 6,99€/mes. 7 días gratis." />
                <meta name="keywords" content="crear web con IA, constructor web inteligencia artificial, diseño web automático, pagepolis" />
                <meta property="og:title" content="Pagepolis — The web that you get" />
                <meta property="og:description" content="Crea tu web profesional con IA en segundos. Sin código. Sin diseñador." />
                <meta property="og:type" content="website" />
                <link rel="canonical" href="https://pagepolis.com" />
            </Head>

            <div className="bg-gray-950 text-white min-h-screen">

                {/* NAV */}
                <nav className="fixed top-0 left-0 right-0 z-50 border-b border-white/5 bg-gray-950/85 backdrop-blur-xl">
                    <div className="max-w-7xl mx-auto px-6 flex justify-between items-center h-16">
                        <a href="/" className="flex items-center gap-2.5">
                            <span className="text-2xl font-black tracking-tight bg-gradient-to-r from-violet-400 via-fuchsia-400 to-cyan-400 bg-clip-text text-transparent">
                                pagepolis
                            </span>
                            <span className="hidden sm:block text-xs text-gray-600 italic">the web that you get</span>
                        </a>
                        <div className="hidden md:flex items-center gap-8 text-sm text-gray-400">
                            <button
                                onClick={() => pricingRef.current?.scrollIntoView({ behavior: 'smooth' })}
                                className="hover:text-white transition-colors"
                            >
                                Precios
                            </button>
                            <a href="/plantillas" className="hover:text-white transition-colors">Plantillas</a>
                        </div>
                        <div className="flex items-center gap-3">
                            <Link href="/login" className="text-sm text-gray-400 hover:text-white transition-colors hidden sm:block">
                                Entrar
                            </Link>
                            <Link
                                href="/register"
                                className="bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:opacity-90 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-opacity"
                            >
                                Empezar gratis
                            </Link>
                        </div>
                    </div>
                </nav>

                {/* HERO */}
                <section className="pt-36 pb-20 px-6 relative overflow-hidden">
                    <div className="absolute inset-0 pointer-events-none">
                        <div className="absolute top-1/3 left-1/2 -translate-x-1/2 w-[700px] h-[450px] bg-violet-700/20 rounded-full blur-[130px]" />
                        <div className="absolute top-1/2 left-1/3 w-[300px] h-[300px] bg-fuchsia-700/12 rounded-full blur-[100px]" />
                    </div>
                    <div className="max-w-5xl mx-auto text-center relative">
                        <div className="inline-flex items-center gap-2 bg-violet-950/70 border border-violet-700/40 text-violet-300 text-xs font-semibold px-4 py-2 rounded-full mb-8 backdrop-blur-sm">
                            <span className="w-2 h-2 bg-green-400 rounded-full animate-pulse" />
                            Impulsado por Claude AI · Generación en menos de 10 segundos
                        </div>

                        <h1 className="text-5xl sm:text-7xl font-black mb-6 leading-tight tracking-tight">
                            <span className="bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
                                The web that
                            </span>
                            <br />
                            <span className="bg-gradient-to-r from-violet-400 via-fuchsia-400 to-cyan-400 bg-clip-text text-transparent">
                                you get
                            </span>
                        </h1>

                        <p className="text-xl text-gray-400 mb-4 max-w-2xl mx-auto leading-relaxed">
                            Describe tu negocio. La IA crea tu web completa en segundos.
                            Edita, publica con tu dominio. Sin código, sin diseñador.
                        </p>

                        <div className="mb-10 mx-auto max-w-2xl bg-gray-900/80 border border-gray-700/50 rounded-2xl p-5 backdrop-blur-sm text-left shadow-xl">
                            <div className="flex items-center gap-2 mb-3">
                                <span className="w-3 h-3 rounded-full bg-red-500/80" />
                                <span className="w-3 h-3 rounded-full bg-yellow-500/80" />
                                <span className="w-3 h-3 rounded-full bg-green-500/80" />
                                <span className="ml-2 text-xs text-gray-500">Generando tu web con IA…</span>
                            </div>
                            <p className="text-sm text-gray-500 mb-2">Quiero una web para mi…</p>
                            <p className="text-base min-h-[28px]"><TypingAnimation /></p>
                        </div>

                        <div className="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                            <Link
                                href="/register"
                                className="bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:opacity-90 text-white px-8 py-4 rounded-2xl font-bold text-lg transition-opacity shadow-xl shadow-violet-900/40"
                            >
                                Crear mi web gratis →
                            </Link>
                            <Link
                                href="/plantillas"
                                className="border border-gray-700 text-gray-300 px-8 py-4 rounded-2xl font-semibold text-lg hover:border-violet-600/60 hover:text-white transition-colors"
                            >
                                Ver plantillas
                            </Link>
                        </div>
                        <p className="text-sm text-gray-600">7 días gratis · Sin tarjeta de crédito · Cancela cuando quieras</p>

                        <div className="mt-14 grid grid-cols-3 gap-6 max-w-md mx-auto">
                            {[
                                { n: '+2.000', l: 'webs creadas' },
                                { n: '<10s',   l: 'tiempo de generación' },
                                { n: '4.9★',   l: 'valoración media' },
                            ].map(s => (
                                <div key={s.n} className="text-center">
                                    <div className="text-2xl font-black text-white">{s.n}</div>
                                    <div className="text-xs text-gray-500 mt-1">{s.l}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CÓMO FUNCIONA */}
                <section className="py-24 px-6 bg-gray-900/40 border-y border-gray-800/50">
                    <div className="max-w-5xl mx-auto">
                        <div className="text-center mb-16">
                            <h2 className="text-4xl font-black mb-3">Tu web en 3 pasos</h2>
                            <p className="text-gray-400">Menos de 5 minutos del principio al fin.</p>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {[
                                { n: '01', icon: '🎨', title: 'Elige una plantilla',  desc: 'Más de 20 diseños profesionales. Restaurante, portfolio, tienda, SaaS, clínica…' },
                                { n: '02', icon: '✍️', title: 'Describe tu negocio', desc: 'Escribe en español qué haces, qué colores quieres, qué necesitas. La IA genera todo al instante.' },
                                { n: '03', icon: '🚀', title: 'Publica y listo',      desc: 'Subdominio gratis en pagepolis.com o tu propio .com. Online en segundos, completamente automático.' },
                            ].map(step => (
                                <div key={step.n} className="bg-gray-900 border border-gray-800 rounded-2xl p-8 relative">
                                    <span className="text-5xl font-black text-gray-800/70 absolute top-5 right-6">{step.n}</span>
                                    <div className="text-4xl mb-4">{step.icon}</div>
                                    <h3 className="text-xl font-bold mb-3">{step.title}</h3>
                                    <p className="text-gray-400 text-sm leading-relaxed">{step.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* FEATURES */}
                <section className="py-24 px-6">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h2 className="text-4xl font-black mb-3">Todo lo que necesitas para crecer</h2>
                            <p className="text-gray-400 max-w-xl mx-auto">Sin complicaciones. Una herramienta que trabaja para ti.</p>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                            {FEATURES.map(f => (
                                <div key={f.title} className="bg-gray-900 border border-gray-800 rounded-2xl p-7 hover:border-violet-800/60 transition-colors group cursor-default">
                                    <div className="text-3xl mb-4">{f.icon}</div>
                                    <h3 className="font-bold text-lg mb-2 group-hover:text-violet-300 transition-colors">{f.title}</h3>
                                    <p className="text-gray-400 text-sm leading-relaxed">{f.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* TESTIMONIALS */}
                <section className="py-24 px-6 bg-gray-900/40 border-y border-gray-800/50">
                    <div className="max-w-5xl mx-auto">
                        <div className="text-center mb-16">
                            <h2 className="text-4xl font-black mb-3">Lo que dicen nuestros clientes</h2>
                            <p className="text-gray-400">Negocios reales con webs reales.</p>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {TESTIMONIALS.map(t => (
                                <div key={t.name} className="bg-gray-900 border border-gray-800 rounded-2xl p-7 flex flex-col">
                                    <div className="flex items-center gap-3 mb-5">
                                        <div className={`w-10 h-10 rounded-full bg-gradient-to-br ${t.color} flex items-center justify-center text-white font-bold text-sm flex-shrink-0`}>
                                            {t.avatar}
                                        </div>
                                        <div>
                                            <div className="font-bold text-sm">{t.name}</div>
                                            <div className="text-xs text-gray-500">{t.role}</div>
                                        </div>
                                        <div className="ml-auto text-yellow-400 text-xs">★★★★★</div>
                                    </div>
                                    <p className="text-gray-300 text-sm leading-relaxed flex-1">"{t.text}"</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* COMPARATIVA */}
                <section className="py-24 px-6">
                    <div className="max-w-4xl mx-auto">
                        <div className="text-center mb-16">
                            <h2 className="text-4xl font-black mb-3">¿Por qué Pagepolis?</h2>
                            <p className="text-gray-400">La única plataforma que genera tu web con IA y te deja editarla con código real.</p>
                        </div>
                        <div className="overflow-x-auto rounded-2xl border border-gray-800">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-gray-800">
                                        <th className="text-left py-4 px-5 text-gray-400 font-semibold">Característica</th>
                                        <th className="py-4 px-5 text-violet-300 font-bold text-center">pagepolis</th>
                                        <th className="py-4 px-5 text-gray-500 font-semibold text-center">Wix</th>
                                        <th className="py-4 px-5 text-gray-500 font-semibold text-center hidden sm:table-cell">Squarespace</th>
                                        <th className="py-4 px-5 text-gray-500 font-semibold text-center hidden sm:table-cell">Webflow</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {COMPARE.map((row, i) => (
                                        <tr key={row.feature} className={i % 2 === 0 ? 'bg-gray-900/30' : ''}>
                                            <td className="py-3.5 px-5 text-gray-300">{row.feature}</td>
                                            <td className="py-3.5 px-5 text-center"><Cell val={row.pagepolis} /></td>
                                            <td className="py-3.5 px-5 text-center text-gray-500"><Cell val={row.wix} /></td>
                                            <td className="py-3.5 px-5 text-center text-gray-500 hidden sm:table-cell"><Cell val={row.ss} /></td>
                                            <td className="py-3.5 px-5 text-center text-gray-500 hidden sm:table-cell"><Cell val={row.webflow} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                {/* PRICING */}
                <section ref={pricingRef} id="precios" className="py-24 px-6 bg-gray-900/40 border-y border-gray-800/50">
                    <div className="max-w-4xl mx-auto">
                        <div className="text-center mb-12">
                            <h2 className="text-4xl font-black mb-3">Precios simples y honestos</h2>
                            <p className="text-gray-400 mb-8">Sin costes ocultos. Sin comisiones. Todo incluido.</p>
                            <div className="inline-flex items-center bg-gray-900 border border-gray-800 rounded-xl p-1 gap-1">
                                <button
                                    onClick={() => setCycle('monthly')}
                                    className={`px-5 py-2 rounded-lg text-sm font-semibold transition-colors ${cycle === 'monthly' ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white'}`}
                                >
                                    Mensual
                                </button>
                                <button
                                    onClick={() => setCycle('yearly')}
                                    className={`px-5 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2 ${cycle === 'yearly' ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white'}`}
                                >
                                    Anual
                                    <span className="text-xs bg-green-600/20 text-green-400 border border-green-600/30 px-2 py-0.5 rounded-full font-bold">-33%</span>
                                </button>
                            </div>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto">
                            <div className="bg-gray-900 border border-gray-800 rounded-2xl p-8">
                                <div className="text-gray-400 text-sm font-semibold uppercase tracking-wider mb-3">Básico</div>
                                <div className="flex items-end gap-1 mb-4">
                                    <span className="text-5xl font-black">{cycle === 'yearly' ? '6,99€' : '9,99€'}</span>
                                    <span className="text-gray-500 text-sm mb-2">/mes</span>
                                </div>
                                {cycle === 'yearly' && <p className="text-xs text-gray-500 -mt-2 mb-5">83,88€/año · 2 meses gratis</p>}
                                <ul className="space-y-2.5 text-sm text-gray-300 mb-8">
                                    {['3 proyectos activos', 'IA ilimitada', 'Subdominio en pagepolis.com', 'Editor de código', 'SSL incluido', 'Soporte por email'].map(f => (
                                        <li key={f} className="flex items-center gap-2">
                                            <span className="text-green-400 text-xs font-bold">✓</span>{f}
                                        </li>
                                    ))}
                                </ul>
                                <Link href="/register" className="block text-center border border-gray-700 hover:border-violet-600 text-white py-3 rounded-xl transition-colors font-semibold text-sm">
                                    Empezar · 7 días gratis
                                </Link>
                            </div>
                            <div className="bg-gradient-to-b from-violet-950/50 to-gray-900 border border-violet-700/60 rounded-2xl p-8 relative shadow-2xl shadow-violet-950/30">
                                <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white text-xs font-bold px-4 py-1.5 rounded-full whitespace-nowrap">
                                    MÁS POPULAR
                                </div>
                                <div className="text-violet-300 text-sm font-semibold uppercase tracking-wider mb-3">Pro</div>
                                <div className="flex items-end gap-1 mb-4">
                                    <span className="text-5xl font-black">{cycle === 'yearly' ? '9,99€' : '14,99€'}</span>
                                    <span className="text-gray-500 text-sm mb-2">/mes</span>
                                </div>
                                {cycle === 'yearly' && <p className="text-xs text-gray-500 -mt-2 mb-5">119,88€/año · 2 meses gratis</p>}
                                <ul className="space-y-2.5 text-sm text-gray-300 mb-8">
                                    {['Proyectos ilimitados', 'IA ilimitada', 'Dominio propio incluido (.com)', 'Editor avanzado', 'SSL incluido', 'Soporte prioritario', 'Analytics básico', 'Sin publicidad de Pagepolis'].map(f => (
                                        <li key={f} className="flex items-center gap-2">
                                            <span className="text-green-400 text-xs font-bold">✓</span>{f}
                                        </li>
                                    ))}
                                </ul>
                                <Link href="/register" className="block text-center bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:opacity-90 text-white py-3 rounded-xl transition-opacity font-bold text-sm shadow-lg shadow-violet-900/40">
                                    Empezar · 7 días gratis
                                </Link>
                            </div>
                        </div>
                        <p className="text-center text-sm text-gray-600 mt-8">
                            ¿Necesitas más? <a href="mailto:hola@pagepolis.com" className="text-violet-400 hover:underline">Escríbenos</a> para un plan a medida.
                        </p>
                    </div>
                </section>

                {/* FAQ */}
                <section className="py-24 px-6">
                    <div className="max-w-2xl mx-auto">
                        <h2 className="text-4xl font-black text-center mb-12">Preguntas frecuentes</h2>
                        <div className="space-y-3">
                            {[
                                { q: '¿Necesito saber programar?',          a: 'No. Describes lo que quieres en español y la IA genera todo. Si quieres retocar el código, tienes un editor completo, pero no es obligatorio.' },
                                { q: '¿Cuánto tarda en generar mi web?',     a: 'Entre 5 y 15 segundos. La IA genera HTML, CSS y JavaScript completo y profesional.' },
                                { q: '¿Qué pasa con mi dominio si cancelo?', a: 'Tienes 30 días de periodo de gracia. Tu web sigue activa. Si no reactivas, el dominio se libera y los datos se eliminan automáticamente.' },
                                { q: '¿Puedo usar mi propio dominio?',       a: 'Sí. En el plan Pro incluimos la compra y configuración automática de un dominio .com. También puedes usar un subdominio gratuito en pagepolis.com.' },
                                { q: '¿Puedo exportar mi web?',              a: 'Sí. El código HTML/CSS/JS generado es tuyo. Puedes copiarlo y alojarlo donde quieras.' },
                                { q: '¿Cómo funciona el periodo de prueba?', a: '7 días completamente gratis. Sin tarjeta de crédito. Acceso completo a todas las funciones.' },
                            ].map(item => (
                                <details key={item.q} className="group bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
                                    <summary className="flex justify-between items-center p-5 cursor-pointer list-none font-semibold text-sm hover:text-violet-300 transition-colors select-none">
                                        {item.q}
                                        <span className="text-gray-500 group-open:rotate-45 transition-transform text-xl ml-4 flex-shrink-0">+</span>
                                    </summary>
                                    <p className="px-5 pb-5 text-gray-400 text-sm leading-relaxed">{item.a}</p>
                                </details>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA FINAL */}
                <section className="py-28 px-6 relative overflow-hidden">
                    <div className="absolute inset-0 pointer-events-none">
                        <div className="absolute bottom-0 left-1/2 -translate-x-1/2 w-[800px] h-[350px] bg-violet-800/15 rounded-full blur-[110px]" />
                    </div>
                    <div className="max-w-2xl mx-auto text-center relative">
                        <h2 className="text-4xl sm:text-5xl font-black mb-4 leading-tight">
                            Tu web merece ser
                            <span className="bg-gradient-to-r from-violet-400 to-cyan-400 bg-clip-text text-transparent"> la mejor</span>
                        </h2>
                        <p className="text-gray-400 text-lg mb-8">Empieza gratis hoy. Sin tarjeta. En 7 días decides.</p>
                        <Link
                            href="/register"
                            className="inline-block bg-gradient-to-r from-violet-600 via-fuchsia-600 to-cyan-600 hover:opacity-90 text-white px-12 py-5 rounded-2xl font-black text-xl transition-opacity shadow-2xl shadow-violet-900/50"
                        >
                            Crear mi web gratis →
                        </Link>
                        <p className="text-gray-600 text-sm mt-5">7 días gratis · Sin tarjeta · Cancela cuando quieras</p>
                    </div>
                </section>

                {/* FOOTER */}
                <footer className="border-t border-gray-800 py-10 px-6">
                    <div className="max-w-5xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6 text-sm text-gray-600">
                        <div className="flex items-center gap-2">
                            <span className="font-black text-lg bg-gradient-to-r from-violet-400 to-cyan-400 bg-clip-text text-transparent">pagepolis</span>
                            <span className="italic">the web that you get</span>
                        </div>
                        <div className="flex gap-6">
                            <a href="/login" className="hover:text-gray-400 transition-colors">Iniciar sesión</a>
                            <a href="/register" className="hover:text-gray-400 transition-colors">Registrarse</a>
                            <a href="mailto:hola@pagepolis.com" className="hover:text-gray-400 transition-colors">Contacto</a>
                        </div>
                        <p>© {new Date().getFullYear()} Pagepolis · Todos los derechos reservados</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
