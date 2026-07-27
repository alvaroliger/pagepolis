import { Head, Link } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import EmptyState from '@/Components/EmptyState';

interface DayPoint { date: string; count: number; }
interface ProjectStat { id: number; name: string; status: string; views_total: number; views_30d: number; }

interface Props {
    series: DayPoint[];
    totals: { all: number; d30: number; d7: number };
    projects: ProjectStat[];
}

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="bg-gray-900 border border-gray-800 rounded-2xl p-5">
            <p className="text-3xl font-black text-white">{value.toLocaleString('es-ES')}</p>
            <p className="text-sm text-gray-500 mt-1">{label}</p>
        </div>
    );
}

export default function AnalyticsIndex({ series, totals, projects }: Props) {
    const max = Math.max(1, ...series.map(d => d.count));

    return (
        <AuthenticatedLayout header={<h1 className="text-xl font-bold text-white">Analítica</h1>}>
            <Head title="Analítica" />

            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Totales */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Stat label="Visitas (7 días)" value={totals.d7} />
                    <Stat label="Visitas (30 días)" value={totals.d30} />
                    <Stat label="Visitas totales" value={totals.all} />
                </div>

                {/* Gráfico de barras (30 días) */}
                <div className="bg-gray-900 border border-gray-800 rounded-2xl p-6">
                    <h2 className="text-sm font-semibold text-gray-400 mb-5">Visitas diarias · últimos 30 días</h2>
                    {totals.d30 === 0 ? (
                        <p className="text-sm text-gray-600 py-8 text-center">
                            Aún no hay visitas. Publica y comparte tus webs para empezar a recibir tráfico.
                        </p>
                    ) : (
                        <div className="flex items-end gap-1 h-40">
                            {series.map(d => {
                                const dayLabel = new Date(d.date).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
                                return (
                                    <button
                                        key={d.date}
                                        type="button"
                                        aria-label={`${d.count} visitas el ${dayLabel}`}
                                        className="flex-1 group relative flex flex-col justify-end h-full bg-transparent border-0 p-0 cursor-default rounded-t focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-900"
                                    >
                                        <div
                                            className="bg-violet-600/70 group-hover:bg-violet-500 group-focus-visible:bg-violet-500 rounded-t transition-colors"
                                            style={{ height: `${Math.max(2, (d.count / max) * 100)}%` }}
                                        />
                                        <div className="absolute -top-7 left-1/2 -translate-x-1/2 hidden group-hover:block group-focus-visible:block bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap z-10">
                                            {d.count} · {dayLabel}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* Por proyecto */}
                <div className="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
                    <h2 className="text-sm font-semibold text-gray-400 px-6 pt-6 pb-3">Por proyecto</h2>
                    {projects.length === 0 ? (
                        <div className="px-6 pb-8 pt-2">
                            <EmptyState
                                icon={Sparkles}
                                title="Aún no tienes proyectos"
                                description="Crea tu primera web para empezar a ver sus visitas aquí."
                                cta={{ label: 'Explorar plantillas', href: '/plantillas' }}
                            />
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-800">
                            {projects.map(p => (
                                <div key={p.id} className="flex items-center justify-between px-6 py-3.5">
                                    <Link href={`/editor/${p.id}`} className="text-sm text-gray-300 hover:text-white truncate min-w-0 flex-1">
                                        {p.name}
                                    </Link>
                                    <div className="flex items-center gap-6 text-sm flex-shrink-0">
                                        <span className="text-gray-500" title="Últimos 30 días">{p.views_30d.toLocaleString('es-ES')} <span className="text-gray-600 text-xs">30d</span></span>
                                        <span className="text-white font-semibold w-16 text-right" title="Total">{p.views_total.toLocaleString('es-ES')}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
