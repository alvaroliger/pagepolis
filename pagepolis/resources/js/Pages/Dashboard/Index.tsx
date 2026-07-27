import { useId, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import toast from 'react-hot-toast';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Reveal, FadeIn } from '@/Components/Motion';
import { Eye, Download, Trash2, ChevronDown, ChevronUp, Sparkles } from 'lucide-react';
import { useModalA11y } from '@/hooks/useModalA11y';

// Cierra con Escape (mismo patrón que el menú de avatar de AuthenticatedLayout).
function useEscapeToClose(onClose: () => void) {
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [onClose]);
}

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
    onboarding: { published: boolean; domain: boolean };
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

function OnboardingChecklist({ hasProject, hasPublished, hasDomain, firstProjectId }: { hasProject: boolean; hasPublished: boolean; hasDomain: boolean; firstProjectId?: number }) {
    if (hasProject && hasPublished && hasDomain) return null;

    // Sin project_id, /publicar no sabe qué proyecto publicar: el paso de dominio se
    // salta en silencio y un usuario puede llegar a pagar sin que se reserve dominio.
    // Si aún no hay proyecto, se manda a crearlo primero en vez de a /publicar a secas.
    const publishHref = firstProjectId ? `/publicar?project_id=${firstProjectId}` : '/crear';

    const steps = [
        { done: hasProject,   label: 'Crea tu primera web',       href: '/crear' },
        { done: hasPublished, label: 'Publica tu web',             href: publishHref },
        { done: hasDomain,    label: 'Conecta tu dominio propio',  href: publishHref },
    ];
    const doneCount = steps.filter(s => s.done).length;

    return (
        <div className="mb-6 bg-gray-900 border border-gray-800 rounded-xl p-4">
            <div className="flex items-center justify-between mb-3">
                <p className="text-sm font-semibold text-white">Primeros pasos</p>
                <span className="text-xs text-gray-500 font-medium tabular-nums">{doneCount} / 3</span>
            </div>
            <div className="space-y-2.5">
                {steps.map(step => (
                    <div key={step.label} className="flex items-center gap-3">
                        <div className={`w-5 h-5 rounded-full flex-shrink-0 flex items-center justify-center border ${step.done ? 'bg-green-900/50 border-green-700' : 'border-gray-700'}`}>
                            {step.done && (
                                <svg className="w-3 h-3 text-green-400" fill="none" viewBox="0 0 12 12" stroke="currentColor" strokeWidth={2.5}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M2 6l3 3 5-5" />
                                </svg>
                            )}
                        </div>
                        <span className={`text-sm flex-1 ${step.done ? 'text-gray-600 line-through' : 'text-gray-300'}`}>
                            {step.label}
                        </span>
                        {!step.done && (
                            <Link href={step.href} className="text-xs font-semibold text-violet-400 hover:text-violet-300 whitespace-nowrap">
                                Ir →
                            </Link>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}

function DeleteModal({ project, onConfirm, onCancel }: { project: Project; onConfirm: () => void; onCancel: () => void }) {
    const titleId = useId();
    const modalRef = useModalA11y(onCancel);
    return (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 px-4">
            <FadeIn
                ref={modalRef}
                role="dialog"
                aria-modal
                aria-labelledby={titleId}
                tabIndex={-1}
                y={12}
                className="bg-gray-900 border border-gray-700 rounded-2xl p-6 max-w-sm w-full shadow-2xl outline-none"
            >
                <h3 id={titleId} className="text-white font-semibold mb-2">Mover a la papelera</h3>
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
            </FadeIn>
        </div>
    );
}

// A diferencia de "mover a la papelera" (reversible), esto borra el proyecto
// para siempre — merece la misma advertencia clara que ya tiene la papelera,
// no un confirm() nativo del navegador.
function PurgeModal({ name, onConfirm, onCancel }: { name: string; onConfirm: () => void; onCancel: () => void }) {
    useEscapeToClose(onCancel);
    return (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 px-4">
            <FadeIn y={12} className="bg-gray-900 border border-red-900/40 rounded-2xl p-6 max-w-sm w-full shadow-2xl">
                <h3 className="text-white font-semibold mb-2">Eliminar definitivamente</h3>
                <p className="text-gray-400 text-sm mb-5">
                    ¿Seguro que quieres borrar <strong className="text-white">"{name}"</strong> para siempre?
                    <span className="block mt-2 text-red-400">Esta acción no se puede deshacer.</span>
                </p>
                <div className="flex gap-3">
                    <button onClick={onCancel} className="flex-1 border border-gray-700 text-gray-300 hover:text-white py-2 rounded-lg text-sm transition-colors">
                        Cancelar
                    </button>
                    <button onClick={onConfirm} className="flex-1 bg-red-700 hover:bg-red-600 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                        Borrar para siempre
                    </button>
                </div>
            </FadeIn>
        </div>
    );
}

function ProjectCard({ project, onDelete }: { project: Project; onDelete: (p: Project) => void }) {
    const [duplicating, setDuplicating] = useState(false);

    const duplicate = () => {
        if (duplicating) return;
        setDuplicating(true);
        router.post(`/proyectos/${project.id}/duplicar`, {}, {
            onFinish: () => setDuplicating(false),
        });
    };

    return (
        <div className="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden hover:border-violet-800/50 hover:-translate-y-1 hover:shadow-xl hover:shadow-violet-950/25 transition-all duration-300 group flex flex-col h-full">
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
                        <a
                            href={project.preview_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            title="Vista previa"
                            className="px-3 flex items-center bg-gray-800 hover:bg-gray-700 text-gray-500 hover:text-white rounded-lg transition-colors"
                        >
                            <Eye className="w-4 h-4" strokeWidth={1.75} />
                        </a>
                    )}
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
                    {project.status === 'published' && (
                        <button
                            onClick={() => onUnpublish(project)}
                            title="Despublicar (puedes volver a publicarla cuando quieras)"
                            className="px-3 flex items-center bg-gray-800 hover:bg-yellow-900/30 text-gray-500 hover:text-yellow-400 rounded-lg transition-colors"
                        >
                            <EyeOff className="w-4 h-4" strokeWidth={1.75} />
                        </button>
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

export default function Dashboard({ projects, trashed, isSubscribed, inGracePeriod, onboarding }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<Project | null>(null);
    const [purgeTarget, setPurgeTarget] = useState<TrashedProject | null>(null);
    const [showTrash, setShowTrash] = useState(false);
    const [query, setQuery] = useState('');

    const filteredProjects = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return projects;
        return projects.filter(p =>
            [p.name, p.domain].some(field => field?.toLowerCase().includes(q))
        );
    }, [projects, query]);

    const confirmDelete = () => {
        if (!deleteTarget) return;
        router.delete(`/proyectos/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Proyecto movido a la papelera'),
            onError: () => toast.error('No se pudo mover a la papelera. Inténtalo de nuevo.'),
        });
        setDeleteTarget(null);
    };

    const unpublish = (project: Project) => {
        router.post('/publicar/despublicar', { project_id: project.id }, { preserveScroll: true });
    };

    const restore = (id: number) => router.post(`/proyectos/${id}/restaurar`, {}, { preserveScroll: true });
    const confirmPurge = () => {
        if (!purgeTarget) return;
        router.delete(`/proyectos/${purgeTarget.id}/eliminar-definitivo`, { preserveScroll: true });
        setPurgeTarget(null);
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

            {purgeTarget && (
                <PurgeModal
                    name={purgeTarget.name}
                    onConfirm={confirmPurge}
                    onCancel={() => setPurgeTarget(null)}
                />
            )}

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                <OnboardingChecklist
                    hasProject={projects.length > 0}
                    hasPublished={onboarding.published}
                    hasDomain={onboarding.domain}
                    firstProjectId={projects[0]?.id}
                />

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
                        <Link href={projects[0] ? `/publicar?project_id=${projects[0].id}` : '/crear'} className="text-violet-400 underline font-semibold hover:text-violet-300">
                            Ver planes
                        </Link>
                    </div>
                )}

                {projects.length === 0 ? (
                    <FadeIn className="text-center py-24 relative">
                        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[420px] h-[260px] bg-violet-700/10 rounded-full blur-[100px] pointer-events-none" aria-hidden="true" />
                        <div className="relative">
                            <div className="w-16 h-16 bg-gradient-to-br from-violet-600/20 to-fuchsia-600/20 border border-violet-700/30 rounded-2xl mx-auto mb-6 flex items-center justify-center">
                                <Sparkles className="w-8 h-8 text-violet-400" strokeWidth={1.5} />
                            </div>
                            <h2 className="text-2xl font-black text-white mb-2 tracking-tight">Crea tu primera web</h2>
                            <p className="text-gray-500 text-sm mb-8">Elige una plantilla y la IA generará tu web en menos de 30 segundos.</p>
                            <Link
                                href="/plantillas"
                                className="bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:opacity-90 text-white px-8 py-3 rounded-xl font-semibold transition-all text-sm shadow-lg shadow-violet-900/40 hover:-translate-y-0.5"
                            >
                                Explorar plantillas
                            </Link>
                        </div>
                    </FadeIn>
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

                        {projects.length > 3 && (
                            <input
                                type="search"
                                value={query}
                                onChange={e => setQuery(e.target.value)}
                                placeholder="Buscar por nombre o dominio..."
                                aria-label="Buscar proyectos"
                                className="w-full max-w-md mb-6 bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500 placeholder-gray-500"
                            />
                        )}

                        {filteredProjects.length === 0 ? (
                            <div className="text-center py-16 border border-dashed border-gray-800 rounded-2xl">
                                <p className="text-gray-400 font-semibold mb-1">Sin resultados para "{query}"</p>
                                <p className="text-gray-600 text-sm mb-4">Prueba con otro nombre o dominio</p>
                                <button
                                    onClick={() => setQuery('')}
                                    className="text-violet-400 hover:text-violet-300 text-sm font-semibold"
                                >
                                    Quitar búsqueda
                                </button>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                                {filteredProjects.map((project, i) => (
                                    <Reveal key={project.id} delay={Math.min(i, 8) * 0.05} y={18}>
                                        <ProjectCard project={project} onDelete={setDeleteTarget} />
                                    </Reveal>
                                ))}
                            </div>
                        )}
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
                                                onClick={() => setPurgeTarget(t)}
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
