import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Eye, Download, Trash2, ChevronDown, ChevronUp } from 'lucide-react';

interface Project {
    id: number;
    name: string;
    status: string;
    slug: string;
    updated_at: string;
    views_30d: number;
    domain: string | null;
    live_url: string | null;
    preview_url: string;
}

interface TrashedProject {
    id: number;
    name: string;
    deleted_at: string;
}

interface Props {
    projects: Project[];
    trashed: TrashedProject[];
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

function DeleteModal({ project, onConfirm, onCancel }: { project: Project; onConfirm: () => void; onCancel: () => void }) {
    return (
        <div className="fixed inset-0 bg-black/60 flex items-center justify-center z-50 px-4">
            <div className="bg-gray-900 border border-gray-700 rounded-2xl p-6 max-w-sm w-full shadow-2xl">
                <h3 className="text-white font-semibold mb-2">Mover a la papelera</h3>
                <p className="text-gray-400 text-sm mb-5">
                    ¿Seguro que quieres eliminar <strong className="text-white">"{project.name}"</strong>?
                    {project.status === 'published' && (
                        <span className="block mt-1 text-yellow-400">Este proyecto está publicado y dejará de ser accesible.</span>
                    )}
                    <span className="block mt-2 text-gray-500">Podrás restaurarlo desde la papelera.</span>
                </p>
                <div className="flex gap-3">
                    <button onClick={onCancel} className="flex-1 border border-gray-700 text-gray-300 hover:text-white py-2 rounded-lg text-sm transition-colors">
                        Cancelar
                    </button>
                    <button onClick={onConfirm} className="flex-1 bg-red-700 hover:bg-red-600 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    );
}

function ProjectCard({ project, onDelete }: { project: Project; onDelete: (p: Project) => void }) {
    return (
        <div className="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden hover:border-gray-700 transition-colors group flex flex-col">
            {/* Thumbnail */}
            <div className="h-36 bg-gradient-to-br from-violet-950/40 via-gray-900 to-gray-800 flex items-center justify-center border-b border-gray-800 relative overflow-hidden">
                <span className="text-5xl font-black text-gray-800 select-none">{project.name.charAt(0).toUpperCase()}</span>
                {project.status === 'published' && (
                    <span className="absolute top-2 right-2 w-2 h-2 bg-green-400 rounded-full" title="Publicado" />
                )}
            </div>

            <div className="p-4 flex flex-col flex-1">
                <div className="flex items-start justify-between gap-2 mb-2">
                    <h3 className="font-semibold text-white text-sm leading-snug flex-1 min-w-0 truncate">{project.name}</h3>
                    <span className={`text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0 ${statusColors[project.status] ?? 'bg-gray-700 text-gray-300'}`}>
                        {statusLabels[project.status] ?? project.status}
                    </span>
                </div>

                {project.domain && (
                    <p className="text-xs text-gray-500 mb-2 truncate" title={project.domain}>
                        {project.domain}
                    </p>
                )}

                <div className="flex items-center gap-3 text-xs text-gray-600 mb-4">
                    <span>Editado {project.updated_at}</span>
                    {project.status === 'published' && (
                        <span className="flex items-center gap-1 text-gray-500" title="Visitas en los últimos 30 días">
                            <Eye className="w-3.5 h-3.5" strokeWidth={1.75} />
                            {project.views_30d} (30d)
                        </span>
                    )}
                </div>

                <div className="flex gap-2 mt-auto">
                    <Link
                        href={`/editor/${project.id}`}
                        className="flex-1 text-center bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white text-xs font-semibold py-2 rounded-lg transition-colors"
                    >
                        Editar
                    </Link>
                    {project.status !== 'published' && (
                        <Link
                            href={`/publicar?project_id=${project.id}`}
                            className="flex-1 text-center bg-violet-700/30 hover:bg-violet-700/60 text-violet-300 text-xs font-semibold py-2 rounded-lg transition-colors"
                        >
                            Publicar
                        </Link>
                    )}
                    {project.status === 'published' && project.live_url && (
                        <a
                            href={project.live_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex-1 text-center bg-green-900/20 hover:bg-green-900/40 text-green-400 text-xs font-semibold py-2 rounded-lg transition-colors"
                        >
                            Ver web
                        </a>
                    )}
                    <a
                        href={`/proyectos/${project.id}/zip`}
                        title="Descargar web (ZIP)"
                        className="px-3 flex items-center bg-gray-800 hover:bg-gray-700 text-gray-500 hover:text-white rounded-lg transition-colors"
                    >
                        <Download className="w-4 h-4" strokeWidth={1.75} />
                    </a>
                    <button
                        onClick={() => onDelete(project)}
                        title="Mover a la papelera"
                        className="px-3 flex items-center bg-gray-800 hover:bg-red-900/30 text-gray-600 hover:text-red-400 rounded-lg transition-colors"
                    >
                        <Trash2 className="w-4 h-4" strokeWidth={1.75} />
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function Dashboard({ projects, trashed, isSubscribed, inGracePeriod }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<Project | null>(null);
    const [showTrash, setShowTrash] = useState(false);

    const confirmDelete = () => {
        if (!deleteTarget) return;
        router.delete(`/proyectos/${deleteTarget.id}`, { preserveScroll: true });
        setDeleteTarget(null);
    };

    const restore = (id: number) => router.post(`/proyectos/${id}/restaurar`, {}, { preserveScroll: true });
    const purge = (id: number) => {
        if (!confirm('¿Eliminar definitivamente? Esta acción no se puede deshacer.')) return;
        router.delete(`/proyectos/${id}/eliminar-definitivo`, { preserveScroll: true });
    };

    const totalViews = projects.reduce((sum, p) => sum + (p.views_30d || 0), 0);

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center justify-between">
                <h1 className="text-xl font-bold text-white">Mis proyectos</h1>
                <div className="flex items-center gap-2">
                    <Link
                        href="/plantillas"
                        className="bg-gray-800 hover:bg-gray-700 text-gray-200 text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
                    >
                        Plantillas
                    </Link>
                    <Link
                        href="/crear"
                        className="bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:opacity-90 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-opacity"
                    >
                        ✨ Crear con IA
                    </Link>
                </div>
            </div>
        }>
            <Head title="Mis proyectos" />

            {deleteTarget && (
                <DeleteModal
                    project={deleteTarget}
                    onConfirm={confirmDelete}
                    onCancel={() => setDeleteTarget(null)}
                />
            )}

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                {inGracePeriod && (
                    <div className="mb-6 p-4 bg-yellow-900/20 border border-yellow-700/40 rounded-xl text-sm text-yellow-300">
                        Tu suscripción ha expirado. Tus proyectos seguirán activos durante el periodo de gracia.{' '}
                        <Link href="/facturacion/portal" className="underline font-semibold hover:text-yellow-200">
                            Reactivar suscripción
                        </Link>
                    </div>
                )}

                {!isSubscribed && !inGracePeriod && (
                    <div className="mb-6 p-4 bg-violet-900/20 border border-violet-700/40 rounded-xl text-sm text-gray-300">
                        <strong className="text-white">Publica gratis en pagepolis.com/s/tu-web.</strong>{' '}
                        Mejora a un plan de pago para usar tu propio dominio y quitar la marca.{' '}
                        <Link href="/publicar" className="text-violet-400 underline font-semibold hover:text-violet-300">
                            Ver planes
                        </Link>
                    </div>
                )}

                {projects.length === 0 ? (
                    <div className="text-center py-24">
                        <div className="w-16 h-16 bg-gray-800 rounded-2xl mx-auto mb-6 flex items-center justify-center">
                            <svg className="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <h2 className="text-xl font-bold text-white mb-2">Crea tu primera web</h2>
                        <p className="text-gray-500 text-sm mb-8">Elige una plantilla y la IA generará tu web en menos de 30 segundos.</p>
                        <Link
                            href="/plantillas"
                            className="bg-violet-600 hover:bg-violet-500 text-white px-8 py-3 rounded-xl font-semibold transition-colors text-sm"
                        >
                            Explorar plantillas
                        </Link>
                    </div>
                ) : (
                    <>
                        {/* Resumen de visitas */}
                        <div className="mb-6 flex flex-wrap items-center gap-4">
                            <div className="flex items-center gap-2 text-sm text-gray-400">
                                <span className="text-2xl font-black text-white">{totalViews.toLocaleString('es-ES')}</span>
                                visitas en los últimos 30 días
                            </div>
                            <Link href="/analytics" className="text-sm text-violet-400 hover:text-violet-300 font-semibold">
                                Ver analítica completa →
                            </Link>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                            {projects.map(project => (
                                <ProjectCard key={project.id} project={project} onDelete={setDeleteTarget} />
                            ))}
                        </div>
                    </>
                )}

                {/* Papelera */}
                {trashed.length > 0 && (
                    <div className="mt-12">
                        <button
                            onClick={() => setShowTrash(s => !s)}
                            className="text-sm text-gray-500 hover:text-gray-300 font-semibold flex items-center gap-2"
                        >
                            <Trash2 className="w-4 h-4" strokeWidth={1.75} />
                            Papelera ({trashed.length})
                            {showTrash ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
                        </button>

                        {showTrash && (
                            <div className="mt-4 space-y-2">
                                {trashed.map(t => (
                                    <div key={t.id} className="flex items-center justify-between bg-gray-900/60 border border-gray-800 rounded-xl px-4 py-3">
                                        <div className="min-w-0">
                                            <p className="text-sm text-gray-300 truncate">{t.name}</p>
                                            <p className="text-xs text-gray-600">Eliminado {t.deleted_at}</p>
                                        </div>
                                        <div className="flex gap-2 flex-shrink-0">
                                            <button
                                                onClick={() => restore(t.id)}
                                                className="text-xs font-semibold px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-violet-300 transition-colors"
                                            >
                                                Restaurar
                                            </button>
                                            <button
                                                onClick={() => purge(t.id)}
                                                className="text-xs font-semibold px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-red-900/40 text-gray-500 hover:text-red-400 transition-colors"
                                            >
                                                Borrar definitivo
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
