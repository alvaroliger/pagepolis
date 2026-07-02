import { useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

const STYLES = ['Moderno', 'Elegante', 'Minimalista', 'Colorido', 'Clásico', 'Atrevido'];

export default function CreateWizard() {
    const [businessName, setBusinessName] = useState('');
    const [description, setDescription]   = useState('');
    const [sells, setSells]               = useState(false);
    const [whatsapp, setWhatsapp]         = useState('');
    const [style, setStyle]               = useState('Moderno');
    const [location, setLocation]         = useState('');
    const [loading, setLoading]           = useState(false);
    const [error, setError]               = useState('');
    const [touched, setTouched]           = useState({ businessName: false, description: false });
    const descriptionRef                  = useRef<HTMLTextAreaElement>(null);

    const businessNameOk = businessName.trim().length > 1;
    const descriptionOk  = description.trim().length > 4;
    const canSubmit      = businessNameOk && descriptionOk && !loading;

    const businessNameError = touched.businessName && !businessNameOk
        ? 'Escribe el nombre de tu negocio (mínimo 2 caracteres).'
        : '';
    const descriptionError  = touched.description && !descriptionOk
        ? 'Cuéntanos un poco más (mínimo 5 caracteres).'
        : '';

    const touchAll = () => setTouched({ businessName: true, description: true });

    const submit = async () => {
        touchAll();
        if (!canSubmit) return;
        setLoading(true);
        setError('');
        try {
            const { data } = await axios.post('/crear-con-ia', {
                business_name: businessName,
                description,
                sells,
                whatsapp: sells ? whatsapp : null,
                style,
                location,
            });
            if (data.success) {
                router.visit(data.redirect);   // el editor muestra el progreso
            }
        } catch (err: any) {
            setError(err.response?.data?.error ?? 'No se pudo crear la web. Inténtalo de nuevo.');
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout header={
            <div>
                <h1 className="text-2xl font-bold text-white">Crea tu web con IA</h1>
                <p className="text-gray-400 text-sm mt-1">Responde 4 preguntas y la inteligencia artificial construye tu web entera.</p>
            </div>
        }>
            <Head title="Crear web con IA" />

            <div className="max-w-2xl mx-auto px-4 py-8">
                <div className="bg-gray-900 border border-gray-800 rounded-2xl p-6 sm:p-8 space-y-7">

                    {/* 1. Nombre */}
                    <div>
                        <label className="block text-sm font-semibold text-white mb-2">
                            1. ¿Cómo se llama tu negocio? <span className="text-violet-400">*</span>
                        </label>
                        <input
                            value={businessName}
                            onChange={e => setBusinessName(e.target.value)}
                            onBlur={() => setTouched(t => ({ ...t, businessName: true }))}
                            onKeyDown={e => { if (e.key === 'Enter') { e.preventDefault(); descriptionRef.current?.focus(); } }}
                            placeholder="Ej: Panadería La Espiga"
                            className={`w-full bg-gray-800 border ${businessNameError ? 'border-red-500' : 'border-gray-700'} rounded-xl px-4 py-3 text-white focus:outline-none focus:border-violet-500 placeholder-gray-600`}
                        />
                        {businessNameError && <p className="text-xs text-red-400 mt-1.5">{businessNameError}</p>}
                    </div>

                    {/* 2. Descripción */}
                    <div>
                        <label className="block text-sm font-semibold text-white mb-2">
                            2. ¿A qué te dedicas? Cuéntalo con tus palabras <span className="text-violet-400">*</span>
                        </label>
                        <textarea
                            ref={descriptionRef}
                            value={description}
                            onChange={e => setDescription(e.target.value)}
                            onBlur={() => setTouched(t => ({ ...t, description: true }))}
                            rows={4}
                            placeholder="Ej: Somos una panadería de barrio. Hacemos pan artesano, bollería y tartas por encargo. Llevamos 30 años en el centro de Sevilla."
                            className={`w-full bg-gray-800 border ${descriptionError ? 'border-red-500' : 'border-gray-700'} rounded-xl px-4 py-3 text-white focus:outline-none focus:border-violet-500 resize-none placeholder-gray-600 leading-relaxed`}
                        />
                        {descriptionError
                            ? <p className="text-xs text-red-400 mt-1.5">{descriptionError}</p>
                            : <p className="text-xs text-gray-600 mt-1.5">Cuanto más cuentes, mejor quedará. No necesitas saber nada de informática.</p>
                        }
                    </div>

                    {/* 3. ¿Vende? */}
                    <div>
                        <label className="block text-sm font-semibold text-white mb-2">3. ¿Quieres vender productos por internet?</label>
                        <div className="flex gap-3">
                            <button
                                type="button"
                                onClick={() => setSells(true)}
                                className={`flex-1 py-3 rounded-xl border text-sm font-semibold transition ${sells ? 'bg-violet-600 border-violet-500 text-white' : 'bg-gray-800 border-gray-700 text-gray-300 hover:border-gray-600'}`}
                            >
                                Sí, quiero una tienda
                            </button>
                            <button
                                type="button"
                                onClick={() => setSells(false)}
                                className={`flex-1 py-3 rounded-xl border text-sm font-semibold transition ${!sells ? 'bg-violet-600 border-violet-500 text-white' : 'bg-gray-800 border-gray-700 text-gray-300 hover:border-gray-600'}`}
                            >
                                No, solo informar
                            </button>
                        </div>
                        {sells && (
                            <div className="mt-3">
                                <label className="block text-xs font-medium text-gray-400 mb-1.5">¿En qué WhatsApp quieres recibir los pedidos? (opcional)</label>
                                <input
                                    value={whatsapp}
                                    onChange={e => setWhatsapp(e.target.value)}
                                    placeholder="Ej: +34 600 123 456"
                                    className="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500 placeholder-gray-600"
                                />
                            </div>
                        )}
                    </div>

                    {/* 4. Estilo + ubicación */}
                    <div>
                        <label className="block text-sm font-semibold text-white mb-2">4. ¿Qué estilo prefieres?</label>
                        <div className="flex flex-wrap gap-2">
                            {STYLES.map(s => (
                                <button
                                    key={s}
                                    type="button"
                                    onClick={() => setStyle(s)}
                                    className={`px-4 py-2 rounded-full text-sm font-medium border transition ${style === s ? 'bg-violet-600 border-violet-500 text-white' : 'bg-gray-800 border-gray-700 text-gray-300 hover:border-gray-600'}`}
                                >
                                    {s}
                                </button>
                            ))}
                        </div>
                        <div className="mt-3">
                            <label className="block text-xs font-medium text-gray-400 mb-1.5">¿Dónde estás? (opcional, ayuda al SEO local)</label>
                            <input
                                value={location}
                                onChange={e => setLocation(e.target.value)}
                                placeholder="Ej: Sevilla"
                                className="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500 placeholder-gray-600"
                            />
                        </div>
                    </div>

                    {error && (
                        <div className="p-3 bg-red-900/20 border border-red-800/50 rounded-xl">
                            <p className="text-sm text-red-400">{error}</p>
                        </div>
                    )}

                    <button
                        onClick={submit}
                        disabled={!canSubmit}
                        className="w-full bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white font-bold py-4 rounded-xl text-base hover:opacity-90 transition disabled:opacity-40"
                    >
                        {loading ? 'Creando tu web…' : '✨ Crear mi web con IA'}
                    </button>
                    <p className="text-center text-xs text-gray-600">
                        Tardará 1-3 minutos. Verás cómo se construye en pantalla.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
