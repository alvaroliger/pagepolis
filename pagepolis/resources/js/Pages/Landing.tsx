import '@/i18n';
import { useState, useEffect, useRef } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import LanguageSelector from '@/Components/LanguageSelector';
import PagepolisLogo from '@/Components/PagepolisLogo';
import Hero3D from '@/Components/Hero3D';
import { Reveal, FadeIn, TiltCard } from '@/Components/Motion';
import { LayoutTemplate, PencilLine, Rocket, Zap, Code2, Globe, ShieldCheck, Smartphone, Sparkles, Check } from 'lucide-react';

const STEP_ICONS = [LayoutTemplate, PencilLine, Rocket];
const FEATURE_ICONS = [Zap, Code2, Globe, ShieldCheck, Smartphone, Sparkles];

const DEMO_PROMPTS: Record<string, string[]> = {
    es: ['restaurante italiano moderno en Madrid, colores cálidos, carta, reservas', 'portfolio de diseñadora UX minimalista, proyectos, sobre mí, contacto', 'tienda online de ropa vintage, productos destacados, newsletter', 'landing de app de productividad, hero, features, pricing, testimonios', 'clínica dental profesional, servicios, equipo médico, cita previa'],
    en: ['modern Italian restaurant in London, warm colors, menu, reservations', 'minimalist UX designer portfolio, projects, about me, contact', 'online vintage clothing store, featured products, newsletter', 'productivity app landing, hero, features, pricing, testimonials', 'professional dental clinic, services, medical team, appointment'],
    fr: ['restaurant italien moderne à Paris, couleurs chaudes, menu, réservations', 'portfolio designer UX minimaliste, projets, à propos, contact', 'boutique en ligne vêtements vintage, produits vedettes, newsletter', 'landing page app productivité, hero, fonctionnalités, tarifs', 'clinique dentaire professionnelle, services, équipe, rendez-vous'],
    de: ['modernes italienisches Restaurant in Berlin, warme Farben, Speisekarte, Reservierungen', 'minimalistisches UX-Designer Portfolio, Projekte, Über mich, Kontakt', 'Online-Shop für Vintage-Kleidung, Featured Products, Newsletter', 'Produktivitäts-App Landing, Hero, Features, Preise, Testimonials', 'professionelle Zahnarztpraxis, Leistungen, Team, Terminbuchung'],
    pt: ['restaurante italiano moderno em São Paulo, cores quentes, cardápio, reservas', 'portfólio de designer UX minimalista, projetos, sobre mim, contato', 'loja online de roupas vintage, produtos em destaque, newsletter', 'landing de app de produtividade, hero, features, preços, depoimentos', 'clínica odontológica profissional, serviços, equipe, agendamento'],
    it: ['ristorante italiano moderno a Milano, colori caldi, menu, prenotazioni', 'portfolio designer UX minimalista, progetti, chi sono, contatti', 'negozio online abbigliamento vintage, prodotti in evidenza, newsletter', 'landing page app produttività, hero, funzionalità, prezzi, testimonianze', 'studio dentistico professionale, servizi, team medico, appuntamento'],
};

function TypingAnimation() {
    const { i18n } = useTranslation();
    const lang = i18n.language in DEMO_PROMPTS ? i18n.language : 'es';
    const prompts = DEMO_PROMPTS[lang];
    const [displayed, setDisplayed] = useState('');
    const [promptIdx, setPromptIdx] = useState(0);
    const [charIdx, setCharIdx] = useState(0);
    const [deleting, setDeleting] = useState(false);

    useEffect(() => {
        setDisplayed('');
        setPromptIdx(0);
        setCharIdx(0);
        setDeleting(false);
    }, [lang]);

    useEffect(() => {
        const current = prompts[promptIdx];
        const speed = deleting ? 18 : 45;
        const timer = setTimeout(() => {
            if (!deleting) {
                if (charIdx < current.length) { setDisplayed(current.slice(0, charIdx + 1)); setCharIdx(c => c + 1); }
                else { setTimeout(() => setDeleting(true), 1800); }
            } else {
                if (charIdx > 0) { setDisplayed(current.slice(0, charIdx - 1)); setCharIdx(c => c - 1); }
                else { setDeleting(false); setPromptIdx(i => (i + 1) % prompts.length); }
            }
        }, speed);
        return () => clearTimeout(timer);
    }, [charIdx, deleting, promptIdx, prompts]);

    return <span className="text-violet-300 font-mono">{displayed}<span className="animate-pulse">|</span></span>;
}

export default function Landing() {
    const { t, i18n } = useTranslation();
    const [cycle, setCycle] = useState<'monthly' | 'yearly'>('yearly');
    const pricingRef = useRef<HTMLElement>(null);
    const supportEmail = (usePage().props as any).support?.email ?? 'soporte@pagepolis.com';
    // Prueba social real: nº de webs publicadas (lo inyecta LandingController; 0 = aún sin dato).
    const sitesPublished: number = (usePage().props as any).stats?.sites_published ?? 0;

    const asArray = <T,>(val: unknown): T[] => Array.isArray(val) ? val as T[] : [];
    const asObj   = (val: unknown): Record<string, string> => (val && typeof val === 'object' && !Array.isArray(val)) ? val as Record<string, string> : {};

    const cur        = asObj(t('currency',                  { returnObjects: true }));
    const basicPrice = cycle === 'yearly' ? (cur.basic_yearly  ?? '6,99') : (cur.basic_monthly ?? '9,99');
    const proPrice   = cycle === 'yearly' ? (cur.pro_yearly    ?? '9,99') : (cur.pro_monthly   ?? '14,99');
    const basicTotal = cur.basic_yearly_total ?? '83,88';
    const proTotal   = cur.pro_yearly_total   ?? '119,88';
    const sym        = cur.symbol ?? '€';

    const steps       = asArray<{ n: string; icon: string; title: string; desc: string }>(t('how.steps',            { returnObjects: true }));
    const featureList = asArray<{ icon: string; title: string; desc: string }>(t('features.items',                  { returnObjects: true }));
    const testimonials= asArray<{ name: string; role: string; text: string; avatar: string; color: string }>(t('testimonials.items', { returnObjects: true }));
    const basicFeats  = asArray<string>(t('pricing.basic_features', { returnObjects: true }));
    const proFeats    = asArray<string>(t('pricing.pro_features',   { returnObjects: true }));
    const faqItems    = asArray<{ q: string; a: string }>(t('faq.items', { returnObjects: true }));

    return (
        <>
            <Head title={`Pagepolis — ${t('hero.h1_1')} ${t('hero.h1_2')}`} />

            <div className="bg-gray-950 text-white min-h-screen">

                {/* NAV */}
                <nav className="fixed top-0 left-0 right-0 z-50 border-b border-white/5 bg-gray-950/85 backdrop-blur-xl">
                    <div className="max-w-7xl mx-auto px-6 flex justify-between items-center h-16">
                        <a href="/" className="flex items-center gap-2.5">
                            <PagepolisLogo size={32} />
                            <span className="text-xl font-black tracking-tight bg-gradient-to-r from-violet-400 via-fuchsia-400 to-cyan-400 bg-clip-text text-transparent">
                                pagepolis
                            </span>
                        </a>
                        <div className="hidden md:flex items-center gap-8 text-sm text-gray-400">
                            <button onClick={() => pricingRef.current?.scrollIntoView({ behavior: 'smooth' })} className="hover:text-white transition-colors">
                                {t('nav.prices')}
                            </button>
                            <a href="/plantillas" className="hover:text-white transition-colors">{t('nav.templates')}</a>
                        </div>
                        <div className="flex items-center gap-2">
                            <LanguageSelector />
                            <Link href="/login" className="text-sm text-gray-400 hover:text-white transition-colors hidden sm:block px-3">
                                {t('nav.login')}
                            </Link>
                            <Link href="/register" className="bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:opacity-90 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-opacity">
                                {t('nav.start')}
                            </Link>
                        </div>
                    </div>
                </nav>

                {/* HERO */}
                <section className="pt-36 pb-20 px-6 relative overflow-hidden">
                    <div className="absolute inset-0 pointer-events-none">
                        <div className="absolute top-1/3 left-1/2 -translate-x-1/2 w-[700px] h-[450px] bg-violet-700/20 rounded-full blur-[130px]" />
                        <div className="absolute top-1/2 left-1/3 w-[300px] h-[300px] bg-fuchsia-700/12 rounded-full blur-[100px]" />
                        <Hero3D className="absolute inset-0 w-full h-full opacity-70" />
                    </div>
                    <div className="max-w-5xl mx-auto text-center relative">
                        <FadeIn>
                            <div className="inline-flex items-center gap-2 bg-violet-950/70 border border-violet-700/40 text-violet-300 text-xs font-semibold px-4 py-2 rounded-full mb-8 backdrop-blur-sm">
                                <span className="w-2 h-2 bg-green-400 rounded-full animate-pulse" />
                                {t('hero.badge')}
                            </div>
                        </FadeIn>

                        <FadeIn delay={0.08}>
                            <h1 className="text-5xl sm:text-7xl font-black mb-6 leading-tight tracking-tight">
                                <span className="bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
                                    {t('hero.h1_1')}
                                </span>
                                <br />
                                <span className="bg-gradient-to-r from-violet-400 via-fuchsia-400 to-cyan-400 bg-clip-text text-transparent">
                                    {t('hero.h1_2')}
                                </span>
                            </h1>
                        </FadeIn>

                        <FadeIn delay={0.16}>
                            <p className="text-xl text-gray-400 mb-4 max-w-2xl mx-auto leading-relaxed">
                                {t('hero.subtitle')}
                            </p>
                        </FadeIn>

                        <FadeIn delay={0.24}>
                            <div className="mb-10 mx-auto max-w-2xl bg-gray-900/80 border border-gray-700/50 rounded-2xl p-5 backdrop-blur-sm text-left shadow-xl">
                                <div className="flex items-center gap-2 mb-3">
                                    <span className="w-3 h-3 rounded-full bg-red-500/80" />
                                    <span className="w-3 h-3 rounded-full bg-yellow-500/80" />
                                    <span className="w-3 h-3 rounded-full bg-green-500/80" />
                                    <span className="ml-2 text-xs text-gray-500">{t('hero.input_hint')}</span>
                                </div>
                                <p className="text-sm text-gray-500 mb-2">{t('hero.input_label')}</p>
                                <p className="text-base min-h-[28px]"><TypingAnimation /></p>
                            </div>
                        </FadeIn>

                        <FadeIn delay={0.32}>
                            <div className="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                                <Link href="/register" className="bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:opacity-90 text-white px-8 py-4 rounded-2xl font-bold text-lg transition-all shadow-xl shadow-violet-900/40 hover:shadow-2xl hover:shadow-violet-800/50 hover:-translate-y-0.5">
                                    {t('hero.cta_primary')}
                                </Link>
                                <Link href="/plantillas" className="border border-gray-700 text-gray-300 px-8 py-4 rounded-2xl font-semibold text-lg hover:border-violet-600/60 hover:text-white transition-all hover:-translate-y-0.5">
                                    {t('hero.cta_secondary')}
                                </Link>
                            </div>
                            <p className="text-sm text-gray-600">{t('hero.disclaimer')}</p>
                        </FadeIn>

                        <FadeIn delay={0.42}>
                            <div className="mt-14 grid grid-cols-3 gap-6 max-w-md mx-auto">
                                {[
                                    { n: sitesPublished > 0 ? `+${sitesPublished.toLocaleString()}` : t('hero.stat_sites_soon'), l: t('hero.stat_sites') },
                                    { n: '<10s',   l: t('hero.stat_time') },
                                    { n: '4.9★',   l: t('hero.stat_rating') },
                                ].map(s => (
                                    <div key={s.l} className="text-center">
                                        <div className="text-2xl font-black text-white">{s.n}</div>
                                        <div className="text-xs text-gray-500 mt-1">{s.l}</div>
                                    </div>
                                ))}
                            </div>
                        </FadeIn>
                    </div>
                </section>

                {/* CÓMO FUNCIONA */}
                <section className="py-24 px-6 bg-gray-900/40 border-y border-gray-800/50">
                    <div className="max-w-5xl mx-auto">
                        <Reveal className="text-center mb-16">
                            <h2 className="text-4xl font-black mb-3 tracking-tight">{t('how.title')}</h2>
                            <p className="text-gray-400">{t('how.subtitle')}</p>
                        </Reveal>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8" style={{ perspective: '1200px' }}>
                            {steps.map((step, i) => {
                                const Icon = STEP_ICONS[i] ?? LayoutTemplate;
                                return (
                                    <Reveal key={step.n} delay={i * 0.1}>
                                        <TiltCard className="bg-gray-900 border border-gray-800 rounded-2xl p-8 relative h-full hover:border-violet-800/50 hover:shadow-xl hover:shadow-violet-950/30">
                                            <span className="text-5xl font-black text-gray-800/70 absolute top-5 right-6">{step.n}</span>
                                            <Icon className="w-8 h-8 text-violet-400 mb-4" strokeWidth={1.5} />
                                            <h3 className="text-xl font-bold mb-3">{step.title}</h3>
                                            <p className="text-gray-400 text-sm leading-relaxed">{step.desc}</p>
                                        </TiltCard>
                                    </Reveal>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* FEATURES */}
                <section className="py-24 px-6">
                    <div className="max-w-6xl mx-auto">
                        <Reveal className="text-center mb-16">
                            <h2 className="text-4xl font-black mb-3 tracking-tight">{t('features.title')}</h2>
                            <p className="text-gray-400 max-w-xl mx-auto">{t('features.subtitle')}</p>
                        </Reveal>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" style={{ perspective: '1200px' }}>
                            {featureList.map((f, i) => {
                                const Icon = FEATURE_ICONS[i] ?? Sparkles;
                                return (
                                    <Reveal key={f.title} delay={(i % 3) * 0.08}>
                                        <TiltCard className="bg-gray-900 border border-gray-800 rounded-2xl p-7 h-full hover:border-violet-800/60 hover:shadow-xl hover:shadow-violet-950/30 group cursor-default">
                                            <Icon className="w-7 h-7 text-violet-400 mb-4" strokeWidth={1.5} />
                                            <h3 className="font-bold text-lg mb-2 group-hover:text-violet-300 transition-colors">{f.title}</h3>
                                            <p className="text-gray-400 text-sm leading-relaxed">{f.desc}</p>
                                        </TiltCard>
                                    </Reveal>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* TESTIMONIALS */}
                <section className="py-24 px-6 bg-gray-900/40 border-y border-gray-800/50">
                    <div className="max-w-5xl mx-auto">
                        <Reveal className="text-center mb-16">
                            <h2 className="text-4xl font-black mb-3 tracking-tight">{t('testimonials.title')}</h2>
                            <p className="text-gray-400">{t('testimonials.subtitle')}</p>
                        </Reveal>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {testimonials.map((t2, i) => (
                                <Reveal key={t2.name} delay={i * 0.1} className="bg-gray-900 border border-gray-800 rounded-2xl p-7 flex flex-col">
                                    <div className="flex items-center gap-3 mb-5">
                                        <div className={`w-10 h-10 rounded-full bg-gradient-to-br ${t2.color} flex items-center justify-center text-white font-bold text-sm flex-shrink-0`}>
                                            {t2.avatar}
                                        </div>
                                        <div>
                                            <div className="font-bold text-sm">{t2.name}</div>
                                            <div className="text-xs text-gray-500">{t2.role}</div>
                                        </div>
                                        <div className="ml-auto text-yellow-400 text-xs">★★★★★</div>
                                    </div>
                                    <p className="text-gray-300 text-sm leading-relaxed flex-1">"{t2.text}"</p>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>

                {/* PRICING */}
                <section ref={pricingRef} id="precios" className="py-24 px-6 bg-gray-900/40 border-y border-gray-800/50">
                    <div className="max-w-4xl mx-auto">
                        <Reveal className="text-center mb-12">
                            <h2 className="text-4xl font-black mb-3 tracking-tight">{t('pricing.title')}</h2>
                            <p className="text-gray-400 mb-8">{t('pricing.subtitle')}</p>
                            <div className="inline-flex items-center bg-gray-900 border border-gray-800 rounded-xl p-1 gap-1">
                                <button onClick={() => setCycle('monthly')} className={`px-5 py-2 rounded-lg text-sm font-semibold transition-colors ${cycle === 'monthly' ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white'}`}>
                                    {t('pricing.monthly')}
                                </button>
                                <button onClick={() => setCycle('yearly')} className={`px-5 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2 ${cycle === 'yearly' ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white'}`}>
                                    {t('pricing.yearly')}
                                    <span className="text-xs bg-green-600/20 text-green-400 border border-green-600/30 px-2 py-0.5 rounded-full font-bold">{t('pricing.discount')}</span>
                                </button>
                            </div>
                            <p className="max-w-xl mx-auto mt-6 flex items-start gap-2 text-sm text-emerald-300/90 bg-emerald-950/30 border border-emerald-800/40 rounded-xl px-4 py-3 text-left">
                                <Check className="w-4 h-4 mt-0.5 flex-shrink-0" strokeWidth={2.5} />
                                <span>{t('pricing.free_note')}</span>
                            </p>
                        </Reveal>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto" style={{ perspective: '1200px' }}>
                            <Reveal className="bg-gray-900 border border-gray-800 rounded-2xl p-8">
                                <div className="text-gray-400 text-sm font-semibold uppercase tracking-wider mb-3">{t('pricing.basic_name')}</div>
                                <div className="flex items-end gap-1 mb-4">
                                    <span className="text-5xl font-black">{sym}{basicPrice}</span>
                                    <span className="text-gray-500 text-sm mb-2">{t('pricing.per_month')}</span>
                                </div>
                                {cycle === 'yearly' && (
                                    <p className="text-xs text-gray-500 -mt-2 mb-5">
                                        {t('pricing.yearly_note_basic', { total: `${sym}${basicTotal}` })}
                                    </p>
                                )}
                                <ul className="space-y-2.5 text-sm text-gray-300 mb-8">
                                    {basicFeats.map(f => (
                                        <li key={f} className="flex items-center gap-2">
                                            <span className="text-green-400 text-xs font-bold">✓</span>{f}
                                        </li>
                                    ))}
                                </ul>
                                <Link href="/register" className="block text-center border border-gray-700 hover:border-violet-600 text-white py-3 rounded-xl transition-colors font-semibold text-sm">
                                    {t('pricing.cta')}
                                </Link>
                            </Reveal>
                            <Reveal delay={0.12}>
                            <TiltCard max={4} className="bg-gradient-to-b from-violet-950/50 to-gray-900 border border-violet-700/60 rounded-2xl p-8 relative shadow-2xl shadow-violet-950/30 h-full">
                                <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white text-xs font-bold px-4 py-1.5 rounded-full whitespace-nowrap">
                                    {t('pricing.popular')}
                                </div>
                                <div className="text-violet-300 text-sm font-semibold uppercase tracking-wider mb-3">{t('pricing.pro_name')}</div>
                                <div className="flex items-end gap-1 mb-4">
                                    <span className="text-5xl font-black">{sym}{proPrice}</span>
                                    <span className="text-gray-500 text-sm mb-2">{t('pricing.per_month')}</span>
                                </div>
                                {cycle === 'yearly' && (
                                    <p className="text-xs text-gray-500 -mt-2 mb-5">
                                        {t('pricing.yearly_note_pro', { total: `${sym}${proTotal}` })}
                                    </p>
                                )}
                                <ul className="space-y-2.5 text-sm text-gray-300 mb-8">
                                    {proFeats.map(f => (
                                        <li key={f} className="flex items-center gap-2">
                                            <span className="text-green-400 text-xs font-bold">✓</span>{f}
                                        </li>
                                    ))}
                                </ul>
                                <Link href="/register" className="block text-center bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:opacity-90 text-white py-3 rounded-xl transition-opacity font-bold text-sm shadow-lg shadow-violet-900/40">
                                    {t('pricing.cta')}
                                </Link>
                            </TiltCard>
                            </Reveal>
                        </div>
                        <p className="text-center text-sm text-gray-600 mt-8">
                            {t('pricing.custom')}{' '}
                            <a href={`mailto:${supportEmail}`} className="text-violet-400 hover:underline">{t('pricing.custom_link')}</a>{' '}
                            {t('pricing.custom_suffix')}
                        </p>
                    </div>
                </section>

                {/* FAQ */}
                <section className="py-24 px-6">
                    <div className="max-w-2xl mx-auto">
                        <Reveal>
                            <h2 className="text-4xl font-black text-center mb-12 tracking-tight">{t('faq.title')}</h2>
                        </Reveal>
                        <Reveal delay={0.1} className="space-y-3">
                            {faqItems.map(item => (
                                <details key={item.q} className="group bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
                                    <summary className="flex justify-between items-center p-5 cursor-pointer list-none font-semibold text-sm hover:text-violet-300 transition-colors select-none">
                                        {item.q}
                                        <span className="text-gray-500 group-open:rotate-45 transition-transform text-xl ml-4 flex-shrink-0">+</span>
                                    </summary>
                                    <p className="px-5 pb-5 text-gray-400 text-sm leading-relaxed">{item.a}</p>
                                </details>
                            ))}
                        </Reveal>
                    </div>
                </section>

                {/* CTA FINAL */}
                <section className="py-28 px-6 relative overflow-hidden">
                    <div className="absolute inset-0 pointer-events-none">
                        <div className="absolute bottom-0 left-1/2 -translate-x-1/2 w-[800px] h-[350px] bg-violet-800/15 rounded-full blur-[110px]" />
                    </div>
                    <Reveal className="max-w-2xl mx-auto text-center relative">
                        <h2 className="text-4xl sm:text-5xl font-black mb-4 leading-tight tracking-tight">
                            {t('cta.title_1')}
                            <span className="bg-gradient-to-r from-violet-400 to-cyan-400 bg-clip-text text-transparent"> {t('cta.title_2')}</span>
                        </h2>
                        <p className="text-gray-400 text-lg mb-8">{t('cta.subtitle')}</p>
                        <Link href="/register" className="inline-block bg-gradient-to-r from-violet-600 via-fuchsia-600 to-cyan-600 hover:opacity-90 text-white px-12 py-5 rounded-2xl font-black text-xl transition-all shadow-2xl shadow-violet-900/50 hover:-translate-y-0.5 hover:shadow-violet-800/60">
                            {t('cta.button')}
                        </Link>
                        <p className="text-gray-600 text-sm mt-5">{t('cta.disclaimer')}</p>
                    </Reveal>
                </section>

                {/* FOOTER */}
                <footer className="border-t border-gray-800 py-10 px-6">
                    <div className="max-w-5xl mx-auto flex flex-col md:flex-row justify-between items-center gap-6 text-sm text-gray-600">
                        <div className="flex items-center gap-2">
                            <PagepolisLogo size={24} />
                            <span className="font-black text-lg bg-gradient-to-r from-violet-400 to-cyan-400 bg-clip-text text-transparent">pagepolis</span>
                        </div>
                        <div className="flex gap-6">
                            <a href="/login" className="hover:text-gray-400 transition-colors">{t('footer.login')}</a>
                            <a href="/register" className="hover:text-gray-400 transition-colors">{t('footer.register')}</a>
                            <a href={`mailto:${supportEmail}`} className="hover:text-gray-400 transition-colors">{t('footer.contact')}</a>
                        </div>
                        <p>© {new Date().getFullYear()} Pagepolis · {t('footer.rights')}</p>
                        <a
                            href="https://www.linkedin.com/in/alvaroliger/"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-gray-700 hover:text-gray-500 transition-colors text-xs"
                        >
                            by Álvaro Liger
                        </a>
                    </div>
                </footer>
            </div>
        </>
    );
}
