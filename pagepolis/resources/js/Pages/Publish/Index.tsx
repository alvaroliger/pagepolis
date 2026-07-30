import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import axios from 'axios';
import { useTranslation } from 'react-i18next';
import { Link2, Globe, Sparkles, Rocket, Copy, Check } from 'lucide-react';

interface Props {
    project: { id: number; name: string; slug: string } | null;
    isSubscribed: boolean;
    inGracePeriod: boolean;
    stripeKey: string;
    baseDomain: string;
}

type Tier = 'free' | 'subdomain' | 'custom';

export default function PublishIndex({ project, baseDomain }: Props) {
    const { t }                     = useTranslation();
    const [tier, setTier]           = useState<Tier>('free');
    const [step, setStep]           = useState(1);
    const [subdomain, setSubdomain] = useState('');
    const [customDomain, setCustomDomain] = useState('');
    const [plan, setPlan]           = useState<'monthly' | 'yearly'>('yearly');
    const [loading, setLoading]     = useState(false);
    const [error, setError]         = useState('');
    const [published, setPublished] = useState<string | null>(null);
    const [provisioning, setProvisioning] = useState<string | null>(null);
    const [copied, setCopied] = useState(false);

    const asObj   = (val: unknown): Record<string, string> => (val && typeof val === 'object' && !Array.isArray(val)) ? val as Record<string, string> : {};
    const asArray = (val: unknown): string[] => Array.isArray(val) ? val as string[] : [];
    const cur       = asObj(t('currency', { returnObjects: true }));
    const sym       = cur.symbol ?? '€';
    const checklist = asArray(t('publish.free_checklist', { returnObjects: true }));
    const yearlyTotal  = `${sym}${cur.pro_yearly_total ?? '119,88'}${t('publish.per_year')}`;
    const monthlyPrice = `${sym}${cur.pro_monthly ?? '14,99'}${t('publish.per_month')}`;

    const freeUrl = project ? `${window.location.origin}/s/${project.slug}` : '';

    const copyUrl = async () => {
        if (!published) return;
        try {
            await navigator.clipboard.writeText(published);
        } catch {
            const el = document.createElement('textarea');
            el.value = published;
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            try { document.execCommand('copy'); } catch { /* noop */ }
            document.body.removeChild(el);
        }
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const publishFree = async () => {
        if (!project) return;
        setLoading(true);
        setError('');
        try {
            const res = await axios.post('/publicar/gratis', { project_id: project.id });
            setPublished(res.data.url);
        } catch (e: any) {
            setError(e.response?.data?.error ?? e.response?.data?.message ?? t('publish.error_publish'));
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
            setError(e.response?.data?.message ?? e.response?.data?.error ?? t('publish.error_domain'));
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
            setError(e.response?.data?.error ?? t('publish.error_checkout'));
            setLoading(false);
        }
    };

    if (provisioning) {
        return (
            <AuthenticatedLayout header={<h1 className="text-2xl font-bold text-white">{t('publish.publishing_header')}</h1>}>
                <Head title={t('publish.publishing_header')} />
                <div className="max-w-xl mx-auto px-4 py-16 text-center">
                    <div className="w-16 h-16 mx-auto mb-6 rounded-2xl bg-violet-500/10 border border-violet-500/30 flex items-center justify-center">
                        <Globe className="w-8 h-8 text-violet-400 animate-pulse" strokeWidth={1.75} />
                    </div>
                    <h2 className="text-3xl font-black text-white mb-4">{t('publish.provisioning_title')}</h2>
                    <p className="text-gray-400 mb-2">
                        {t('publish.provisioning_desc', { domain: provisioning ?? '' })}
                    </p>
                    <p className="text-gray-500 text-sm mb-8">
                        {t('publish.provisioning_note')}
                    </p>
                    <Link href="/dashboard" className="bg-violet-600 hover:bg-violet-500 text-white px-6 py-3 rounded-xl font-semibold transition-colors text-sm">
                        {t('publish.go_dashboard')}
                    </Link>
                </div>
            </AuthenticatedLayout>
        );
    }

    if (published) {
        return (
            <AuthenticatedLayout header={<h1 className="text-2xl font-bold text-white">{t('publish.published_header')}</h1>}>
                <Head title={t('publish.published_header')} />
                <div className="max-w-xl mx-auto px-4 py-16 text-center">
                    <div className="w-16 h-16 mx-auto mb-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center">
                        <Rocket className="w-8 h-8 text-emerald-400" strokeWidth={1.75} />
                    </div>
                    <h2 className="text-3xl font-black text-white mb-4">{t('publish.published_title')}</h2>
                    <p className="text-gray-400 mb-8">{t('publish.published_access')}</p>
                    <a
                        href={published}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-block bg-gray-800 border border-gray-700 text-violet-300 font-mono px-6 py-3 rounded-xl hover:border-violet-600 transition-colors text-sm break-all"
                    >
                        {published}
                    </a>
                    <div className="mt-3">
                        <button
                            type="button"
                            onClick={copyUrl}
                            className="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-400 hover:text-white transition-colors"
                        >
                            {copied ? (
                                <>
                                    <Check className="w-3.5 h-3.5 text-emerald-400" strokeWidth={2.5} />
                                    <span className="text-emerald-400">¡Enlace copiado!</span>
                                </>
                            ) : (
                                <>
                                    <Copy className="w-3.5 h-3.5" strokeWidth={2} />
                                    Copiar enlace
                                </>
                            )}
                        </button>
                    </div>
                    <div className="mt-8 flex gap-3 justify-center">
                        <a
                            href={project ? `/editor/${project.id}` : '/dashboard'}
                            className="bg-gray-700 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-colors text-sm"
                        >
                            {project ? t('publish.back_editor') : t('publish.back_dashboard')}
                        </a>
                        <a
                            href={published}
                            target="_blank"
                            rel="noreferrer"
                            className="bg-violet-600 hover:bg-violet-500 text-white px-6 py-3 rounded-xl font-semibold transition-colors text-sm"
                        >
                            {t('publish.view_site')}
                        </a>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-2xl font-bold text-white">{t('publish.header')}</h1>}>
            <Head title={t('publish.header')} />

            <div className="max-w-2xl mx-auto px-4 py-10">
                {project ? (
                    <div className="mb-6 p-3 bg-violet-900/20 border border-violet-800/30 rounded-xl text-sm text-gray-300">
                        {t('publish.publishing_label', { name: '' })}<strong className="text-white">{project.name}</strong>
                    </div>
                ) : (
                    <div className="mb-6 p-3 bg-yellow-900/20 border border-yellow-800/30 rounded-xl text-sm text-yellow-300">
                        No hay ningún proyecto seleccionado.{' '}
                        <Link href="/dashboard" className="underline font-semibold hover:text-yellow-200">
                            Vuelve al panel
                        </Link>{' '}
                        y elige "Publicar" desde tu web.
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
                        <div className="font-bold text-white text-sm mb-1">{t('publish.tier_free_name')}</div>
                        <div className="text-xs text-gray-400">
                            pagepolis.com/s/{project?.slug ?? 'tu-web'}
                        </div>
                        <div className="mt-2 text-xs text-emerald-400 font-semibold">{t('publish.tier_free_badge')}</div>
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
                        <div className="font-bold text-white text-sm mb-1">{t('publish.tier_subdomain_name')}</div>
                        <div className="text-xs text-gray-400">tu-nombre.{baseDomain}</div>
                        <div className="mt-2 text-xs text-violet-400 font-semibold">{t('publish.tier_subdomain_badge')}</div>
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
                        <div className="font-bold text-white text-sm mb-1">{t('publish.tier_custom_name')}</div>
                        <div className="text-xs text-gray-400">{t('publish.tier_custom_placeholder')}</div>
                        <div className="mt-2 text-xs text-violet-400 font-semibold">{t('publish.tier_custom_badge')}</div>
                    </button>
                </div>

                {/* Panel según tier */}
                {tier === 'free' && (
                    <div className="bg-gray-900 border border-gray-800 rounded-2xl p-8">
                        <h2 className="text-xl font-bold text-white mb-2">{t('publish.free_title')}</h2>
                        <p className="text-gray-400 text-sm mb-6">
                            {t('publish.free_desc', { url: freeUrl })}
                        </p>
                        <div className="bg-gray-800/60 rounded-xl p-4 mb-6 text-sm text-gray-300 space-y-1.5">
                            {checklist.map((item, i) => (
                                <div key={i} className="flex items-center gap-2">
                                    <span className={i < 3 ? 'text-green-400' : 'text-yellow-500'}>{i < 3 ? '✓' : '~'}</span> {item}
                                </div>
                            ))}
                        </div>
                        {error && <p className="text-red-400 text-sm mb-4">{error}</p>}
                        <button
                            onClick={publishFree}
                            disabled={loading || !project}
                            className="w-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:opacity-90 disabled:opacity-40 text-white py-3.5 rounded-xl font-bold transition-opacity inline-flex items-center justify-center gap-2"
                        >
                            <Rocket className="w-4 h-4" strokeWidth={2} />
                            {loading ? t('publish.free_cta_loading') : t('publish.free_cta')}
                        </button>
                        <p className="text-center text-xs text-gray-600 mt-3">
                            {t('publish.free_upgrade_hint')}
                        </p>
                    </div>
                )}

                {(tier === 'subdomain' || tier === 'custom') && (
                    <>
                        {/* Paso 1: dominio */}
                        {step === 1 && (
                            <div className="bg-gray-900 border border-gray-800 rounded-2xl p-8">
                                <h2 className="text-xl font-bold text-white mb-6">
                                    {tier === 'subdomain' ? t('publish.domain_step_title_subdomain') : t('publish.domain_step_title_custom')}
                                </h2>

                                {tier === 'subdomain' ? (
                                    <div className="flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 mb-4">
                                        <input
                                            type="text"
                                            value={subdomain}
                                            onChange={e => setSubdomain(e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
                                            placeholder={t('publish.subdomain_placeholder')}
                                            className="bg-transparent text-white focus:outline-none flex-1"
                                        />
                                        <span className="text-gray-500 text-sm">.{baseDomain}</span>
                                    </div>
                                ) : (
                                    <input
                                        type="text"
                                        value={customDomain}
                                        onChange={e => setCustomDomain(e.target.value.toLowerCase())}
                                        placeholder={t('publish.custom_domain_placeholder')}
                                        className="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-violet-500 mb-4"
                                    />
                                )}

                                {error && <p className="text-red-400 text-sm mb-4">{error}</p>}
                                <button
                                    onClick={reserveDomain}
                                    disabled={loading || !project || (tier === 'subdomain' ? !subdomain : !customDomain)}
                                    className="w-full bg-violet-600 hover:bg-violet-500 disabled:opacity-40 text-white py-3.5 rounded-xl font-bold transition-colors"
                                >
                                    {loading ? t('publish.continue_loading') : t('publish.continue_cta')}
                                </button>
                            </div>
                        )}

                        {/* Paso 2: plan y pago */}
                        {step === 2 && (
                            <div className="bg-gray-900 border border-gray-800 rounded-2xl p-8">
                                <h2 className="text-xl font-bold text-white mb-2">{t('publish.plan_step_title')}</h2>
                                <p className="text-gray-400 text-sm mb-6">{t('publish.plan_step_subtitle')}</p>

                                <div className="space-y-3 mb-6">
                                    <button
                                        onClick={() => setPlan('yearly')}
                                        className={`w-full p-5 rounded-xl border text-left transition-colors relative ${plan === 'yearly' ? 'border-violet-600 bg-violet-600/10' : 'border-gray-700 hover:border-gray-600'}`}
                                    >
                                        <div className="absolute top-3 right-3 bg-green-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">{t('publish.plan_yearly_badge')}</div>
                                        <div className="font-bold text-white mb-1">{t('publish.plan_yearly_name')}</div>
                                        <div className="text-2xl font-black text-white">{sym}{cur.pro_yearly ?? '9,99'}<span className="text-sm font-normal text-gray-400">{t('publish.per_month')} · {sym}{cur.pro_yearly_total ?? '119,88'}{t('publish.per_year')}</span></div>
                                    </button>
                                    <button
                                        onClick={() => setPlan('monthly')}
                                        className={`w-full p-5 rounded-xl border text-left transition-colors ${plan === 'monthly' ? 'border-violet-600 bg-violet-600/10' : 'border-gray-700 hover:border-gray-600'}`}
                                    >
                                        <div className="font-bold text-white mb-1">{t('publish.plan_monthly_name')}</div>
                                        <div className="text-2xl font-black text-white">{sym}{cur.pro_monthly ?? '14,99'}<span className="text-sm font-normal text-gray-400">{t('publish.per_month')}</span></div>
                                    </button>
                                </div>

                                {error && <p className="text-red-400 text-sm mb-4">{error}</p>}
                                <div className="flex gap-3">
                                    <button onClick={() => setStep(1)} className="px-4 py-3 border border-gray-700 text-gray-400 rounded-xl hover:border-gray-600 transition-colors">
                                        {t('publish.back')}
                                    </button>
                                    <button
                                        onClick={goToCheckout}
                                        disabled={loading}
                                        className="flex-1 bg-violet-600 hover:bg-violet-500 disabled:opacity-40 text-white py-3 rounded-xl font-bold transition-colors"
                                    >
                                        {loading ? t('publish.checkout_loading') : t('publish.checkout_cta', { price: plan === 'yearly' ? yearlyTotal : monthlyPrice })}
                                    </button>
                                </div>
                                <p className="mt-3 text-xs text-gray-600 text-center">
                                    {t('publish.checkout_note')}
                                </p>
                            </div>
                        )}
                    </>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
