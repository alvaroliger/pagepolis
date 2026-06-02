import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import axios from 'axios';

interface Props {
    project: { id: number; name: string } | null;
    isSubscribed: boolean;
    inGracePeriod: boolean;
    stripeKey: string;
}

export default function PublishIndex({ project, isSubscribed, stripeKey }: Props) {
    const [step, setStep]             = useState(1);
    const [domainType, setDomainType] = useState<'subdomain' | 'custom'>('subdomain');
    const [subdomain, setSubdomain]   = useState('');
    const [customDomain, setCustomDomain] = useState('');
    const [plan, setPlan]             = useState<'monthly' | 'yearly'>('yearly');
    const [loading, setLoading]       = useState(false);
    const [domainError, setDomainError] = useState('');

    const selectedDomain = domainType === 'subdomain'
        ? (subdomain ? `${subdomain}.twtyg.com` : '')
        : customDomain;

    const reserveDomain = async () => {
        if (!selectedDomain || !project) return;
        setLoading(true);
        setDomainError('');
        try {
            await axios.post('/dominios/reservar', {
                domain: selectedDomain,
                type: domainType,
                project_id: project.id,
            });
            setStep(2);
        } catch (err: any) {
            setDomainError(err.response?.data?.message || 'Error al reservar el dominio.');
        } finally {
            setLoading(false);
        }
    };

    const goToCheckout = async () => {
        setLoading(true);
        try {
            const res = await axios.post('/facturacion/checkout', { plan });
            if (res.data.url) {
                window.location.href = res.data.url;
            }
        } catch (err: any) {
            alert(err.response?.data?.error || 'Error al iniciar el pago.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout header={<h1 className="text-2xl font-bold text-white">Publicar proyecto</h1>}>
            <Head title="Publicar" />

            <div className="max-w-2xl mx-auto px-4 py-12">
                {/* Indicador de pasos */}
                <div className="flex items-center gap-4 mb-10">
                    {[1, 2, 3].map(s => (
                        <div key={s} className="flex items-center gap-2">
                            <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold ${step >= s ? 'bg-violet-600 text-white' : 'bg-gray-800 text-gray-500'}`}>
                                {s}
                            </div>
                            <span className={`text-sm ${step >= s ? 'text-white' : 'text-gray-600'}`}>
                                {s === 1 ? 'Dominio' : s === 2 ? 'Plan' : 'Pago'}
                            </span>
                            {s < 3 && <div className={`w-12 h-0.5 ${step > s ? 'bg-violet-600' : 'bg-gray-800'}`} />}
                        </div>
                    ))}
                </div>

                {/* Paso 1: Dominio */}
                {step === 1 && (
                    <div className="bg-gray-900 border border-gray-800 rounded-2xl p-8">
                        <h2 className="text-xl font-bold text-white mb-6">Elige tu dominio</h2>

                        {project && (
                            <div className="mb-6 p-3 bg-violet-900/20 border border-violet-800/30 rounded-lg text-sm text-gray-300">
                                Publicando: <strong className="text-white">{project.name}</strong>
                            </div>
                        )}

                        <div className="flex gap-3 mb-6">
                            <button
                                onClick={() => setDomainType('subdomain')}
                                className={`flex-1 py-3 rounded-lg text-sm font-semibold border transition-colors ${domainType === 'subdomain' ? 'border-violet-600 bg-violet-600/10 text-violet-300' : 'border-gray-700 text-gray-400 hover:border-gray-600'}`}
                            >
                                Subdominio gratis
                            </button>
                            <button
                                onClick={() => setDomainType('custom')}
                                className={`flex-1 py-3 rounded-lg text-sm font-semibold border transition-colors ${domainType === 'custom' ? 'border-violet-600 bg-violet-600/10 text-violet-300' : 'border-gray-700 text-gray-400 hover:border-gray-600'}`}
                            >
                                Dominio propio (+12€/año)
                            </button>
                        </div>

                        {domainType === 'subdomain' ? (
                            <div className="flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-lg px-4 py-3">
                                <input
                                    type="text"
                                    value={subdomain}
                                    onChange={e => setSubdomain(e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
                                    placeholder="mi-negocio"
                                    className="bg-transparent text-white focus:outline-none flex-1"
                                />
                                <span className="text-gray-500 text-sm">.twtyg.com</span>
                            </div>
                        ) : (
                            <input
                                type="text"
                                value={customDomain}
                                onChange={e => setCustomDomain(e.target.value.toLowerCase())}
                                placeholder="minegocio.com"
                                className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500"
                            />
                        )}

                        {selectedDomain && (
                            <p className="mt-3 text-sm text-gray-400">
                                Tu sitio quedará en: <span className="text-violet-300 font-mono">{selectedDomain}</span>
                            </p>
                        )}

                        {domainError && <p className="mt-3 text-sm text-red-400">{domainError}</p>}

                        <button
                            onClick={project ? reserveDomain : () => setStep(2)}
                            disabled={loading || (!selectedDomain && !!project)}
                            className="mt-6 w-full bg-violet-600 hover:bg-violet-500 disabled:opacity-40 text-white py-3 rounded-lg font-bold transition-colors"
                        >
                            {loading ? 'Reservando...' : 'Continuar →'}
                        </button>
                    </div>
                )}

                {/* Paso 2: Plan */}
                {step === 2 && (
                    <div className="bg-gray-900 border border-gray-800 rounded-2xl p-8">
                        <h2 className="text-xl font-bold text-white mb-2">Elige tu plan</h2>
                        <p className="text-gray-400 text-sm mb-6">7 días de prueba gratis. Sin tarjeta de crédito hasta que terminen.</p>

                        <div className="space-y-4 mb-6">
                            <button
                                onClick={() => setPlan('yearly')}
                                className={`w-full p-5 rounded-xl border text-left transition-colors relative ${plan === 'yearly' ? 'border-violet-600 bg-violet-600/10' : 'border-gray-700 hover:border-gray-600'}`}
                            >
                                <div className="absolute top-3 right-3 bg-green-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">AHORRA 33%</div>
                                <div className="font-bold text-white mb-1">Plan Anual</div>
                                <div className="text-2xl font-black text-white">9,99€<span className="text-sm font-normal text-gray-400">/mes · 119,88€/año</span></div>
                                <div className="text-sm text-gray-400 mt-1">2 meses gratis incluidos</div>
                            </button>

                            <button
                                onClick={() => setPlan('monthly')}
                                className={`w-full p-5 rounded-xl border text-left transition-colors ${plan === 'monthly' ? 'border-violet-600 bg-violet-600/10' : 'border-gray-700 hover:border-gray-600'}`}
                            >
                                <div className="font-bold text-white mb-1">Plan Mensual</div>
                                <div className="text-2xl font-black text-white">14,99€<span className="text-sm font-normal text-gray-400">/mes</span></div>
                                <div className="text-sm text-gray-400 mt-1">Cancela cuando quieras</div>
                            </button>
                        </div>

                        <div className="flex gap-3">
                            <button onClick={() => setStep(1)} className="px-4 py-3 border border-gray-700 text-gray-400 rounded-lg hover:border-gray-600 transition-colors">
                                ← Atrás
                            </button>
                            <button
                                onClick={goToCheckout}
                                disabled={loading}
                                className="flex-1 bg-violet-600 hover:bg-violet-500 disabled:opacity-40 text-white py-3 rounded-lg font-bold transition-colors"
                            >
                                {loading ? 'Redirigiendo...' : `Ir al pago (${plan === 'yearly' ? '119,88€/año' : '14,99€/mes'}) →`}
                            </button>
                        </div>

                        <p className="mt-4 text-xs text-gray-600 text-center">
                            El dominio se compra y el sitio se publica solo tras confirmar el pago.
                        </p>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
