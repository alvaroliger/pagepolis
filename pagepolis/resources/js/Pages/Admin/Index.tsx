import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import axios from 'axios';
import { useState } from 'react';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
    projects_count: number;
    domains_count: number;
    grace_period_ends_at: string | null;
    admin_suspended_at: string | null;
}

interface Stats {
    total_users: number;
    active_subs: number;
    total_projects: number;
    active_domains: number;
}

interface AiSpend {
    month_to_date: number;
    today: number;
    monthly_budget: number;
    daily_budget: number;
    used_percent: number;
    paused: boolean;
}

interface Props {
    users: { data: User[]; total: number };
    stats: Stats;
    ai: AiSpend;
}

export default function AdminIndex({ users, stats, ai }: Props) {
    const [loading, setLoading] = useState<number | null>(null);

    const action = async (url: string, userId: number, data = {}) => {
        setLoading(userId);
        try {
            await axios.post(url, data);
            window.location.reload();
        } finally {
            setLoading(null);
        }
    };

    return (
        <AuthenticatedLayout header={<h1 className="text-2xl font-bold text-white">Panel de Administración</h1>}>
            <Head title="Admin" />

            <div className="max-w-7xl mx-auto px-4 py-8">
                {/* Stats */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    {[
                        { label: 'Usuarios', value: stats.total_users, color: 'violet' },
                        { label: 'Suscripciones activas', value: stats.active_subs, color: 'green' },
                        { label: 'Proyectos', value: stats.total_projects, color: 'blue' },
                        { label: 'Dominios activos', value: stats.active_domains, color: 'cyan' },
                    ].map(s => (
                        <div key={s.label} className="bg-gray-900 border border-gray-800 rounded-xl p-5">
                            <p className="text-gray-400 text-sm mb-1">{s.label}</p>
                            <p className="text-3xl font-black text-white">{s.value}</p>
                        </div>
                    ))}
                </div>

                {/* Gasto de IA (control de coste) */}
                <div className={`mb-8 rounded-xl p-6 border ${ai.paused ? 'border-red-700/60 bg-red-950/20' : 'border-gray-800 bg-gray-900'}`}>
                    <div className="flex items-center justify-between mb-3">
                        <h2 className="font-bold text-white">Gasto de IA este mes</h2>
                        {ai.paused
                            ? <span className="text-xs font-bold px-2.5 py-1 rounded-full bg-red-900/50 text-red-300">IA EN PAUSA (tope alcanzado)</span>
                            : <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-green-900/40 text-green-400">Activa</span>}
                    </div>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                        <div>
                            <p className="text-2xl font-black text-white">${ai.month_to_date.toFixed(2)}</p>
                            <p className="text-xs text-gray-500">Gastado este mes</p>
                        </div>
                        <div>
                            <p className="text-2xl font-black text-white">${ai.today.toFixed(2)}</p>
                            <p className="text-xs text-gray-500">Gastado hoy</p>
                        </div>
                        <div>
                            <p className="text-2xl font-black text-gray-300">${ai.monthly_budget.toFixed(2)}</p>
                            <p className="text-xs text-gray-500">Tope mensual</p>
                        </div>
                        <div>
                            <p className="text-2xl font-black text-gray-300">${ai.daily_budget.toFixed(2)}</p>
                            <p className="text-xs text-gray-500">Tope diario</p>
                        </div>
                    </div>
                    {ai.monthly_budget > 0 && (
                        <div className="w-full bg-gray-800 rounded-full h-2.5 overflow-hidden">
                            <div
                                className={`h-full rounded-full ${ai.used_percent >= 90 ? 'bg-red-500' : ai.used_percent >= 70 ? 'bg-yellow-500' : 'bg-violet-500'}`}
                                style={{ width: `${Math.min(100, ai.used_percent)}%` }}
                            />
                        </div>
                    )}
                    <p className="text-xs text-gray-500 mt-3">
                        Para permitir más gasto, sube <code className="text-gray-400">AI_MONTHLY_BUDGET_USD</code> / <code className="text-gray-400">AI_DAILY_BUDGET_USD</code> en el <code className="text-gray-400">.env</code> del servidor.
                    </p>
                </div>

                {/* Usuarios */}
                <div className="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-800">
                        <h2 className="font-bold text-white">Usuarios ({users.total})</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-gray-800">
                                    {['Nombre', 'Email', 'Rol', 'Proyectos', 'Dominios', 'Prueba', 'Acciones'].map(h => (
                                        <th key={h} className="text-left px-6 py-3 text-gray-400 font-medium">{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {users.data.map(user => (
                                    <tr key={user.id} className={`border-b border-gray-800/50 hover:bg-gray-800/30 ${user.admin_suspended_at ? 'bg-red-950/10' : ''}`}>
                                        <td className="px-6 py-4 text-white font-medium">{user.name}</td>
                                        <td className="px-6 py-4 text-gray-400">{user.email}</td>
                                        <td className="px-6 py-4">
                                            {user.admin_suspended_at ? (
                                                <span className="text-xs font-semibold px-2 py-1 rounded-full bg-red-900/50 text-red-300">
                                                    suspendido
                                                </span>
                                            ) : (
                                                <span className={`text-xs font-semibold px-2 py-1 rounded-full ${user.role === 'admin' ? 'bg-violet-900/50 text-violet-300' : 'bg-gray-800 text-gray-400'}`}>
                                                    {user.role}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-gray-400">{user.projects_count}</td>
                                        <td className="px-6 py-4 text-gray-400">{user.domains_count}</td>
                                        <td className="px-6 py-4">
                                            {user.grace_period_ends_at ? (
                                                <span className="text-yellow-400 text-xs">Hasta {new Date(user.grace_period_ends_at).toLocaleDateString('es-ES')}</span>
                                            ) : (
                                                <span className="text-gray-600 text-xs">—</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex gap-2">
                                                <button
                                                    onClick={() => action(`/admin/usuarios/${user.id}/reactivar`, user.id)}
                                                    disabled={loading === user.id}
                                                    className="text-xs bg-green-900/30 text-green-400 hover:bg-green-900/50 px-3 py-1.5 rounded-lg transition-colors disabled:opacity-50"
                                                >
                                                    Reactivar
                                                </button>
                                                <button
                                                    onClick={() => action(`/admin/usuarios/${user.id}/suspender`, user.id)}
                                                    disabled={loading === user.id}
                                                    className="text-xs bg-red-900/30 text-red-400 hover:bg-red-900/50 px-3 py-1.5 rounded-lg transition-colors disabled:opacity-50"
                                                >
                                                    Suspender
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
