import { useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface Lead {
    id: number;
    project: string | null;
    name: string | null;
    email: string | null;
    phone: string | null;
    message: string | null;
    is_new: boolean;
    created_at: string;
}

export default function LeadsIndex({ leads = [] as Lead[] }: { leads: Lead[] }) {
    const [query, setQuery] = useState('');

    const filteredLeads = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return leads;
        return leads.filter((lead) =>
            [lead.name, lead.email, lead.phone, lead.message, lead.project]
                .some((field) => field?.toLowerCase().includes(q))
        );
    }, [leads, query]);

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 className="text-2xl font-bold text-white">Mensajes</h1>
                    <p className="text-gray-400 text-sm mt-1">Los contactos que llegan desde tus webs publicadas.</p>
                </div>
                {leads.length > 0 && (
                    <a
                        href="/mensajes/exportar"
                        className="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-700 text-gray-200 text-sm font-medium px-4 py-2 rounded-lg border border-gray-700 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Exportar CSV
                    </a>
                )}
            </div>
        }>
            <Head title="Mensajes" />

            <div className="max-w-4xl mx-auto px-4 py-8">
                {leads.length === 0 ? (
                    <div className="bg-gray-900 border border-gray-800 rounded-2xl p-10 text-center">
                        <div className="text-4xl mb-3" aria-hidden="true">📭</div>
                        <h2 className="text-lg font-bold text-white">Aún no tienes mensajes</h2>
                        <p className="text-gray-400 text-sm mt-2 max-w-md mx-auto">
                            Cuando alguien rellene el formulario de contacto de tu web, su mensaje aparecerá aquí
                            y te avisaremos por email al instante.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <input
                            type="search"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Buscar por nombre, email, teléfono, mensaje o web..."
                            aria-label="Buscar mensajes"
                            className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500 placeholder-gray-500"
                        />

                        {filteredLeads.length === 0 ? (
                            <div className="bg-gray-900 border border-gray-800 rounded-2xl p-10 text-center">
                                <div className="text-4xl mb-3">🔍</div>
                                <h2 className="text-lg font-bold text-white">Sin resultados para &quot;{query}&quot;</h2>
                                <p className="text-gray-400 text-sm mt-2">Prueba con otro nombre, email o palabra del mensaje.</p>
                            </div>
                        ) : filteredLeads.map((lead) => (
                            <div
                                key={lead.id}
                                className={`bg-gray-900 border rounded-2xl p-5 ${lead.is_new ? 'border-violet-600/60' : 'border-gray-800'}`}
                            >
                                <div className="flex items-start justify-between gap-3 flex-wrap">
                                    <div className="flex items-center gap-2">
                                        {lead.is_new && (
                                            <span className="bg-violet-600 text-white text-[10px] font-bold uppercase tracking-wide rounded-full px-2 py-0.5">
                                                Nuevo
                                            </span>
                                        )}
                                        <span className="font-bold text-white">{lead.name || 'Sin nombre'}</span>
                                    </div>
                                    <div className="text-xs text-gray-500">
                                        {lead.created_at}{lead.project ? ` · ${lead.project}` : ''}
                                    </div>
                                </div>

                                <div className="flex flex-wrap gap-x-5 gap-y-1 mt-2 text-sm">
                                    {lead.email && (
                                        <a href={`mailto:${lead.email}`} className="text-violet-400 hover:underline">
                                            <span aria-hidden="true">✉️</span> {lead.email}
                                        </a>
                                    )}
                                    {lead.phone && (
                                        <a href={`tel:${lead.phone}`} className="text-violet-400 hover:underline">
                                            <span aria-hidden="true">📞</span> {lead.phone}
                                        </a>
                                    )}
                                </div>

                                {lead.message && (
                                    <p className="mt-3 text-gray-300 text-sm leading-relaxed whitespace-pre-wrap bg-gray-950/60 border border-gray-800 rounded-xl p-3">
                                        {lead.message}
                                    </p>
                                )}

                                {lead.email && (
                                    <div className="mt-3">
                                        <a
                                            href={`mailto:${lead.email}`}
                                            className="inline-block bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:opacity-90 transition-opacity"
                                        >
                                            Responder
                                        </a>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
