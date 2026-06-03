import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface Project {
    id: number;
    name: string;
    status: string;
    slug: string;
    updated_at: string;
    domain: string | null;
    preview_url: string;
}

interface Props {
    projects: Project[];
    isSubscribed: boolean;
    inGracePeriod: boolean;
}

const statusColors: Record<string, string> = {
    draft:     'bg-gray-700 text-gray-300',
    published: 'bg-green-900/50 text-green-400',
    suspended: 'bg-red-900/50 text-red-400',
    deleted:   'bg-gray-800 text-gray-500',
};

const statusLabels: Record<string, string> = {
    draft:     'Borrador',
    published: 'Publicado',
    suspended: 'Suspendido',
    deleted:   'Eliminado',
};

export default function Dashboard({ projects, isSubscribed, inGracePeriod }: Props) {
    const deleteProject = (id: number) => {
        if (!confirm('¿Eliminar este proyecto?')) return;
        router.delete(`/proyectos/${id}`);
    };

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold text-white">Mis proyectos</h1>
                <Link href="/plantillas" className="bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                    + Nuevo proyecto
                </Link>
            </div>
        }>
            <Head title="Dashboard" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {inGracePeriod && (
                    <div className="mb-6 p-4 bg-yellow-900/30 border border-yellow-700/50 rounded-xl text-yellow-300 text-sm">
                        ⚠️ Tu suscripción ha expirado. Tus proyectos seguirán activos durante el periodo de gracia.{' '}
                        <Link href="/facturacion/portal" className="underline font-semibold">Reactivar ahora</Link>
                    </div>
                )}

                {!isSubscribed && !inGracePeriod && (
                    <div className="mb-6 p-4 bg-violet-900/20 border border-violet-700/40 rounded-xl text-sm text-gray-300">
                        🚀 <strong className="text-white">Prueba Pagepolis 7 días gratis.</strong> Crea y publica tu web sin límites.{' '}
                        <Link href="/publicar" className="text-violet-400 underline font-semibold">Activar ahora</Link>
                    </div>
                )}

                {projects.length === 0 ? (
                    <div className="text-center py-24">
                        <div className="text-6xl mb-6">🎨</div>
                        <h2 className="text-2xl font-bold text-white mb-3">Aún no tienes proyectos</h2>
                        <p className="text-gray-400 mb-8">Elige una plantilla y crea tu primera web con IA en minutos.</p>
                        <Link href="/plantillas" className="bg-violet-600 hover:bg-violet-500 text-white px-8 py-4 rounded-xl font-bold transition-colors">
                            Explorar plantillas
                        </Link>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {projects.map(project => (
                            <div key={project.id} className="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden hover:border-gray-700 transition-colors group">
                                <div className="h-40 bg-gradient-to-br from-violet-900/30 to-gray-800 flex items-center justify-center">
                                    <span className="text-4xl">🌐</span>
                                </div>
                                <div className="p-5">
                                    <div className="flex items-start justify-between mb-3">
                                        <h3 className="font-bold text-white text-lg leading-tight">{project.name}</h3>
                                        <span className={`text-xs font-semibold px-2 py-1 rounded-full ${statusColors[project.status] || 'bg-gray-700 text-gray-300'}`}>
                                            {statusLabels[project.status] || project.status}
                                        </span>
                                    </div>
                                    {project.domain && (
                                        <p className="text-sm text-gray-500 mb-3">🔗 {project.domain}</p>
                                    )}
                                    <p className="text-xs text-gray-600 mb-4">{project.updated_at}</p>
                                    <div className="flex gap-2">
                                        <Link
                                            href={`/editor/${project.id}`}
                                            className="flex-1 text-center bg-violet-600/20 hover:bg-violet-600/40 text-violet-300 text-sm font-semibold py-2 rounded-lg transition-colors"
                                        >
                                            Editar
                                        </Link>
                                        {project.status !== 'published' && (
                                            <Link
                                                href={`/publicar?project_id=${project.id}`}
                                                className="flex-1 text-center bg-green-600/20 hover:bg-green-600/40 text-green-300 text-sm font-semibold py-2 rounded-lg transition-colors"
                                            >
                                                Publicar
                                            </Link>
                                        )}
                                        <button
                                            onClick={() => deleteProject(project.id)}
                                            className="px-3 bg-red-600/10 hover:bg-red-600/30 text-red-400 text-sm rounded-lg transition-colors"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
