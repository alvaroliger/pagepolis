import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import axios from 'axios';
import { Link2, Globe, Sparkles, Rocket } from 'lucide-react';

interface Props {
    project: { id: number; name: string; slug: string } | null;
    isSubscribed: boolean;
    inGracePeriod: boolean;
    stripeKey: string;
    baseDomain: string;
}

type Tier = 'free' | 'subdomain' | 'custom';

export default function PublishIndex({ project, baseDomain }: Props) {
    const [tier, setTier]           = useState<Tier>('free');
    const [step, setStep]           = useState(1);
    const [subdomain, setSubdomain] = useState('');
    const [customDomain, setCustomDomain] = useState('');
    const [plan, setPlan]           = useState<'monthly' | 'yearly'>('yearly');
    const [loading, setLoading]     = useState(false);
    const [error, setError]         = useState('');
    const [published, setPublished] = useState<string | null>(null);
    const [provisioning, setProvisioning] = useState<string | null>(null);

    const freeUrl = project ? `${window.location.origin}/s/${project.slug}` : '';

    const publishFree = async () => {
        if (!project) return;
        setLoading(true);
        setError('');
        try {
            const res = await axios.post('/publicar/gratis', { project_id: project.id });
            setPublished(res.data.url);
        } catch (e: any) {
            setError(e.response?.data?.error ?? e.response?.data?.message ?? 'Error al publicar.');
        } finally {
            setLoading(false);
        }
    };

    const reserveDomain = async () => {
        const domain = tier === 'subdomain'
            ? (subdomain ? `${subdomain}.${baseDomain}` : '')
            : customDomain;
        if (!domain || !project) return;
        setLoading(true);
        setError('');
        try {
            const res = await axios.post('/dominios/reservar', { domain, type: tier, project_id: project.id });
            if (res.data.needs_payment) {
                setStep(2);                      // hay que pagar primero
            } else {
                setProvisioning(domain);         // ya suscrito: se está publicando
            }
        } catch (e: any) {
            setError(e.response?.data?.message ?? e.response?.data?.error ?? 'Error al reservar el dominio.');
        } finally {
            setLoading(false);
        }
    };

    const goToCheckout = async () => {
        setLoading(true);
        try {
            const res = await axios.post('/facturacion/checkout', { plan });
            if (res.data.url) window.location.href = res.data.url;
        } catch (e: any) {
            setError(e.response?.data?.error ?? 'Error al iniciar el pago.');
            setLoading(false);
        }
    };

    if (provisioning) {
        return (
            <AuthenticatedLayout header={<h1 className="text-2xl font-bold text-white">Publicando…</h1>}>
                <Head title="Publicando" />
                <div className="max-w-xl mx-auto px-4 py-16 text-center">
                    <div className="w-16 h-16 mx-auto mb-6 rounded-2xl bg-violet-500/10 border border-violet-500/30 flex items-center justify-center">
                        <Globe className="w-8 h-8 text-violet-400 animate-pulse" strokeWidth={1.75} />
                    </div>
                    <h2 className="text-3xl font-black text-white mb-4">Estamos publicando tu web</h2>
                    <p className="text-gray-400 mb-2">
                        Tu web se está registrando y publicando en{' '}
                        <span className="text-violet-300 font-mono break-all">{provisioning}</span>.
                    </p>
                    <p className="text-gray-500 text-sm mb-8">
                        Tarda unos minutos en estar disponible (registro del dominio y propagación de DNS).
                        Lo verás en tu panel cuando esté activo.
                    </p>
                    <a href="/dashboard" className="bg-violet-600 hover:bg-violet-500 text-white px-6 py-3 rounded-xl font-semibold transition-colors text-sm">
                        Ir a mi panel
                    </a>
                </div>
            </AuthenticatedLayout>
        );
    }

    if (published) {
        return (
            <AuthenticatedLayout header={<h1 className="text-2xl font-bold text-white">¡Publicado!</h1>}>
                <Head title="Publicado" />
                <div className="max-w-xl mx-auto px-4 py-16 text-center">
                    <div className="w-16 h-16 mx-auto mb-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center">
                        <Rocket className="w-8 h-8 text-emerald-400" strokeWidth={1.75} />
                    </div>
                    <h2 className="text-3xl font-black text-white mb-4">Tu web está online</h2>
                    <p className="text-gray-400 mb-8">Accede a ella en:</p>
                    <a
                        href={published}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-block bg-gray-800 border border-gray-700 text-violet-300 font-mono px-6 py-3 rounded-xl hover:border-violet-600 transition-colors text-sm break-all"
                    >
                        {published}
                    </a>
                    <div className="mt-8 flex gap-3 justify-center">
                        <a
                            href={project ? `/editor/${project.id}` : '/dashboard'}
                            className="bg-gray-700 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-colors text-sm"
                        >
                            {project ? '← Volver al editor' : '← Volver al panel'}
                        </a>
                        <a
                            href={published}
                            target="_blank"
                            rel="noreferrer"
                            className="bg-violet-600 hover:bg-violet-500 text-white px-6 py-3 rounded-xl font-semibold transition-colors text-sm"
                        >
                            Ver mi web →
                        </a>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-2xl font-bold text-white">Publicar proyecto</h1>}>
            <Head title="Publicar" />

            <div className="max-w-2xl mx-auto px-4 py-10">
                {project && (
                    <div className="mb-6 p-3 bg-violet-900/20 border border-violet-800/30 rounded-xl text-sm text-gray-300">
                        Publicando: <strong className="text-white">{project.name}</strong>
                    </div>
                )}

                {/* Selector de tier */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                    {/* Tier gratuito */}
                    <button
                        onClick={() => setTier('free')}
                        className={`p-5 rounded-2xl border text-left transition-all ${
                            tier === 'free'
                                ? 'border-emerald-600 bg-emerald-950/30'
                                : 'border-gray-700 hover:border-gray-600'
                        }`}
                    >
                        <Link2 className="w-6 h-6 mb-2 text-emerald-400" strokeWidth={1.75} />
                        <div className="font-bold text-white text-sm mb-1">Ruta gratuita</div>
                        <div className="text-xs text-gray-400">
                            pagepolis.com/s/{project?.slug ?? 'tu-web'}
                        </div>
                        <div className="mt-2 text-xs text-emerald-400 font-semibold">Gratis · Sin pago</div>
                    </button>

                    {/* Subdominio */}
                    <button
                        onClick={() => setTier('subdomain')}
                        className={`p-5 rounded-2xl border text-left transition-all ${
                            tier === 'subdomain'
                                ? 'border-violet-600 bg-violet-950/30'
                                : 'border-gray-700 hover:border-gray-600'
                        }`}
                    >
                        <Globe className="w-6 h-6 mb-2 text-violet-400" strokeWidth={1.75} />
                        <div className="font-bold text-white text-sm mb-1">Subdominio</div>
                        <div className="text-xs text-gray-400">tu-nombre.{baseDomain}</div>
                        <div className="mt-2 text-xs text-violet-400 font-semibold">Plan Básico</div>
                    </button>

                    {/* Dominio propio */}
                    <button
                        onClick={() => setTier('custom')}
                        className={`p-5 rounded-2xl border text-left transition-all ${
                            tier === 'custom'
                                ? 'border-violet-600 bg-violet-950/30'
                                : 'border-gray-700 hover:border-gray-600'
                        }`}
                    >
                        <Sparkles className="w-6 h-6 mb-2 text-violet-400" strokeWidth={1.75} />
                        <div className="font-bold text-white text-sm mb-1">Dominio propio</div>
                        <div className="text-xs text-gray-400">tu-negocio.com</div>
                        <div className="mt-2 text-xs text-violet-400 font-semibold">Plan Pro</div>
                    </button>
                </div>

                {/* Panel según tier */}
                {tier === 'free' && (
                    <div className="bg-gray-900 border border-gray-800 rounded-2xl p-8">
                        <h2 className="text-xl font-bold text-white mb-2">Publicar gratis</h2>
                        <p className="text-gray-400 text-sm mb-6">
                            Tu web quedará disponible en{' '}
                            <span className="text-emerald-400 font-mono">{freeUrl}</span>.
                            Sin necesidad de tarjeta ni suscripción.
                        </p>
                        <div className="bg-gray-800/60 rounded-xl p-4 mb-6 text-sm text-gray-300 space-y-1.5">
                            <div className="flex items-center gap-2"><span className="text-green-400">✓</span> Publicación inmediata</div>
                            <div className="flex items-center gap-2"><span className="text-green-400">✓</span> SSL incluido</div>
                            <div className="flex items-center gap-2"><span className="text-green-400">✓</span> SEO básico</div>
                            <div className="flex items-center gap-2"><span className="text-yellow-500">~</span> URL en pagepolis.com/s/ (no dominio propio)</div>
                            <div className="flex items-center gap-2"><span className="text-yellow-500">~</span> Pequeño sello “Hecho con Pagepolis” (se quita con un plan de pago)</div>
                        </div>
                        {error && <p className="text-red-400 text-sm mb-4">{error}</p>}
                        <button
                            onClick={publishFree}
                            disabled={loading || !project}
                            className="w-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:opacity-90 disabled:opacity-40 text-white py-3.5 rounded-xl font-bold transition-opacity inline-flex items-center justify-center gap-2"
                        >
                            <Rocket className="w-4 h-4" strokeWidth={2} />
                            {loading ? 'Publicando…' : 'Publicar gratis ahora'}
                        </button>
                        <p className="text-center text-xs text-gray-600 mt-3">
                            ¿Quieres un dominio propio? Cambia al plan de pago arriba.
                        </p>
                    </div>
                )}

                {(tier === 'subdomain' || tier === 'custom') && (
                    <>
                        {/* Paso 1: dominio */}
                        {step === 1 && (
                            <div className="bg-gray-900 border border-gray-800 rounded-2xl p-8">
                                <h2 className="text-xl font-bold text-white mb-6">
                                    {tier === 'subdomain' ? 'Elige tu subdominio' : 'Tu dominio propio'}
                                </h2>

                                {tier === 'subdomain' ? (
                                    <div className="flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 mb-4">
                                        <input
                                            type="text"
                                            value={subdomain}
                                            onChange={e => setSubdomain(e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
                                            placeholder="mi-negocio"
                                            className="bg-transparent text-white focus:outline-none flex-1"
                                        />
                                        <span className="text-gray-500 text-sm">.{baseDomain}</span>
                                    </div>
                                ) : (
                                    <input
                                        type="text"
                                        value={customDomain}
                                        onChange={e => setCustomDomain(e.target.value.toLowerCase())}
                                        placeholder="minegocio.com"
                                        className="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-violet-500 mb-4"
                                    />
                                )}

                                {error && <p className="text-red-400 text-sm mb-4">{error}</p>}
                                <button
                                    onClick={project ? reserveDomain : () => setStep(2)}
                                    disabled={loading || (tier === 'subdomain' ? !subdomain : !customDomain)}
                                    className="w-full bg-violet-600 hover:bg-violet-500 disabled:opacity-40 text-white py-3.5 rounded-xl font-bold transition-colors"
                                >
                                    {loading ? 'Reservando…' : 'Continuar →'}
                                </button>
                            </div>
                        )}

                        {/* Paso 2: plan y pago */}
                        {step === 2 && (
                            <div className="bg-gray-900 border border-gray-800 rounded-2xl p-8">
                                <h2 className="text-xl font-bold text-white mb-2">Elige tu plan</h2>
                                <p className="text-gray-400 text-sm mb-6">7 días de prueba gratis.</p>

                                <div className="space-y-3 mb-6">
                                    <button
                                        onClick={() => setPlan('yearly')}
                                        className={`w-full p-5 rounded-xl border text-left transition-colors relative ${plan === 'yearly' ? 'border-violet-600 bg-violet-600/10' : 'border-gray-700 hover:border-gray-600'}`}
                                    >
                                        <div className="absolute top-3 right-3 bg-green-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">AHORRA 33%</div>
                                        <div className="font-bold text-white mb-1">Plan Anual</div>
                                        <div className="text-2xl font-black text-white">9,99€<span className="text-sm font-normal text-gray-400">/mes · 119,88€/año</span></div>
                                    </button>
                                    <button
                                        onClick={() => setPlan('monthly')}
                                        className={`w-full p-5 rounded-xl border text-left transition-colors ${plan === 'monthly' ? 'border-violet-600 bg-violet-600/10' : 'border-gray-700 hover:border-gray-600'}`}
                                    >
                                        <div className="font-bold text-white mb-1">Plan Mensual</div>
                                        <div className="text-2xl font-black text-white">14,99€<span className="text-sm font-normal text-gray-400">/mes</span></div>
                                    </button>
                                </div>

                                {error && <p className="text-red-400 text-sm mb-4">{error}</p>}
                                <div className="flex gap-3">
                                    <button onClick={() => setStep(1)} className="px-4 py-3 border border-gray-700 text-gray-400 rounded-xl hover:border-gray-600 transition-colors">
                                        ← Atrás
                                    </button>
                                    <button
                                        onClick={goToCheckout}
                                        disabled={loading}
                                        className="flex-1 bg-violet-600 hover:bg-violet-500 disabled:opacity-40 text-white py-3 rounded-xl font-bold transition-colors"
                                    >
                                        {loading ? 'Redirigiendo…' : `Ir al pago (${plan === 'yearly' ? '119,88€/año' : '14,99€/mes'}) →`}
                                    </button>
                                </div>
                                <p className="mt-3 text-xs text-gray-600 text-center">
                                    El dominio se compra y la web se publica solo tras confirmar el pago.
                                </p>
                            </div>
                        )}
                    </>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
