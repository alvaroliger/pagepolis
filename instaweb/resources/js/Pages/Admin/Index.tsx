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
}

interface Stats {
    total_users: number;
    active_subs: number;
    total_projects: number;
    active_domains: number;
}

interface Props {
    users: { data: User[]; total: number };
    stats: Stats;
}

export default function AdminIndex({ users, stats }: Props) {
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

                {/* Usuarios */}
                <div className="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-800">
                        <h2 className="font-bold text-white">Usuarios ({users.total})</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-gray-800">
                                    {['Nombre', 'Email', 'Rol', 'Proyectos', 'Dominios', 'Gracia', 'Acciones'].map(h => (
                                        <th key={h} className="text-left px-6 py-3 text-gray-400 font-medium">{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {users.data.map(user => (
                                    <tr key={user.id} className="border-b border-gray-800/50 hover:bg-gray-800/30">
                                        <td className="px-6 py-4 text-white font-medium">{user.name}</td>
                                        <td className="px-6 py-4 text-gray-400">{user.email}</td>
                                        <td className="px-6 py-4">
                                            <span className={`text-xs font-semibold px-2 py-1 rounded-full ${user.role === 'admin' ? 'bg-violet-900/50 text-violet-300' : 'bg-gray-800 text-gray-400'}`}>
                                                {user.role}
                                            </span>
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
