import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useModalA11y } from '@/hooks/useModalA11y';

// Evita que al pulsar enlaces en la vista previa el iframe navegue a la app
// (lo que en local saturaba el servidor → "localhost rechaza la conexión").
const PREVIEW_GUARD = "(function(){document.addEventListener('click',function(e){var a=e.target&&e.target.closest&&e.target.closest('a');if(!a)return;var h=a.getAttribute('href')||'';if(h.charAt(0)==='#'){e.preventDefault();try{if(h.length>1){var el=document.querySelector(h);if(el)el.scrollIntoView({behavior:'smooth'});}}catch(x){}}else{e.preventDefault();}},true);document.addEventListener('submit',function(e){e.preventDefault();},true);})();";

interface Template {
    id: number;
    name: string;
    category: string;
    thumbnail: string | null;
    tags: string[] | null;
    is_premium: boolean;
    uses_count: number;
    html: string;
    css: string;
    js?: string;
    has_3d: boolean;
}

interface Props {
    templates: Template[];
    categories: string[];
}

// Miniatura viva: renderiza el HTML/CSS real de la plantilla en un iframe
// escalado. Las plantillas 3D llevan animaciones CSS (auroras, brillos, marquees)
// que se mueven aquí sin necesidad de ejecutar el WebGL, así la galería se ve
// animada como en las referencias del sector, pero sin freír el navegador con
// decenas de contextos WebGL a la vez (el 3D completo corre en la vista previa).
function TemplatePreview({ html, css }: { html: string; css: string }) {
    const srcDoc = `<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>*{margin:0;padding:0;box-sizing:border-box}body{overflow:hidden}${css}</style></head><body>${html}</body></html>`;
    return (
        <iframe
            srcDoc={srcDoc}
            sandbox="allow-scripts"
            scrolling="no"
            className="w-full h-full border-0 pointer-events-none"
            style={{ transform: 'scale(0.4)', transformOrigin: 'top left', width: '250%', height: '250%' }}
            title="preview"
            loading="lazy"
        />
    );
}

// Modal de vista previa. Se monta solo cuando hay plantilla seleccionada, así
// useModalA11y (foco al abrir, trampa de foco, cierre con Escape y devolución
// del foco al cerrar) se engancha en el momento correcto.
function PreviewModal({ template, creating, onClose, onUse }: {
    template: Template; creating: number | null; onClose: () => void; onUse: () => void;
}) {
    const dialogRef = useModalA11y(onClose);
    return (
        <div className="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4" onClick={onClose}>
            <div
                ref={dialogRef}
                role="dialog"
                aria-modal="true"
                aria-label={`Vista previa de ${template.name}`}
                className="bg-white rounded-xl overflow-hidden w-full max-w-4xl h-[85vh] flex flex-col"
                onClick={e => e.stopPropagation()}
            >
                <div className="flex items-center justify-between px-4 py-3 bg-gray-100 border-b border-gray-200 flex-shrink-0">
                    <div className="flex gap-1.5">
                        <span className="w-3 h-3 rounded-full bg-red-400"></span>
                        <span className="w-3 h-3 rounded-full bg-yellow-400"></span>
                        <span className="w-3 h-3 rounded-full bg-green-400"></span>
                    </div>
                    <span className="text-sm font-semibold text-gray-600 flex items-center gap-2">
                        {template.name}
                        {template.has_3d && (
                            <span className="bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">✨ 3D</span>
                        )}
                    </span>
                    <div className="flex items-center gap-3">
                        <button
                            onClick={onUse}
                            disabled={creating !== null}
                            className="bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition-colors disabled:opacity-50"
                        >
                            Usar esta plantilla →
                        </button>
                        <button onClick={onClose} aria-label="Cerrar vista previa" className="text-gray-500 hover:text-gray-700 text-xl font-bold">×</button>
                    </div>
                </div>
                <iframe
                    srcDoc={`<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${template.css}</style></head><body>${template.html}<script>${template.js || ''}<\/script><script>${PREVIEW_GUARD}<\/script></body></html>`}
                    sandbox="allow-scripts"
                    className="flex-1 w-full border-0 min-h-0"
                    title={template.name}
                />
            </div>
        </div>
    );
}

export default function TemplatesIndex({ templates, categories }: Props) {
    const [filter, setFilter] = useState('Todos');
    const [creating, setCreating] = useState<number | null>(null);
    const [name, setName] = useState('');
    const [previewId, setPreviewId] = useState<number | null>(null);

    const byCategory = templates.filter(t => filter === 'Todos' || t.category === filter);
    const threeDee = byCategory.filter(t => t.has_3d);
    const classic = byCategory.filter(t => !t.has_3d);
    const previewTemplate = templates.find(t => t.id === previewId);

    const useTemplate = (templateId: number) => {
        const projectName = name.trim() || templates.find(t => t.id === templateId)?.name || 'Mi proyecto';
        setCreating(templateId);
        router.post('/proyectos', { name: projectName, template_id: templateId }, {
            onFinish: () => setCreating(null),
        });
    };

    const Card = ({ template, big }: { template: Template; big?: boolean }) => (
        <div
            className={`group relative rounded-2xl overflow-hidden transition-all hover:-translate-y-1 ${
                big
                    ? 'bg-gray-950 p-[1.5px] bg-gradient-to-br from-violet-500/60 via-fuchsia-500/30 to-cyan-400/50 shadow-[0_10px_40px_-12px_rgba(124,92,255,0.5)] hover:shadow-[0_20px_60px_-12px_rgba(124,92,255,0.7)]'
                    : 'bg-gray-900 border border-gray-800 hover:border-violet-700/50'
            }`}
        >
            <div className={big ? 'rounded-2xl overflow-hidden bg-gray-900' : ''}>
                <button
                    type="button"
                    aria-label={`Vista previa de ${template.name}`}
                    className={`block w-full text-left overflow-hidden relative cursor-pointer bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 ${big ? 'h-60' : 'h-44'}`}
                    onClick={() => setPreviewId(template.id)}
                >
                    <TemplatePreview html={template.html} css={template.css} />
                    {template.has_3d && (
                        <span className="absolute top-2 left-2 bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full z-10 shadow-lg">
                            ✨ 3D
                        </span>
                    )}
                    {template.is_premium && (
                        <span className="absolute top-2 right-2 bg-yellow-500 text-yellow-900 text-xs font-bold px-2 py-0.5 rounded-full z-10">
                            PRO
                        </span>
                    )}
                    <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                        <span className="opacity-0 group-hover:opacity-100 transition-opacity text-white text-xs font-semibold bg-black/60 px-3 py-1.5 rounded-full backdrop-blur-sm">
                            Vista previa
                        </span>
                    </div>
                </button>

                <div className="p-4">
                    <div className="flex justify-between items-start mb-1">
                        <h3 className={`font-bold text-white ${big ? 'text-base' : 'text-sm'}`}>{template.name}</h3>
                        <span className="text-xs text-gray-600 flex-shrink-0 ml-2">{template.uses_count} usos</span>
                    </div>
                    <p className="text-xs text-violet-400 mb-3">{template.category}</p>
                    {template.tags && (
                        <div className="flex flex-wrap gap-1 mb-4">
                            {template.tags.slice(0, 3).map(tag => (
                                <span key={tag} className="text-xs bg-gray-800 text-gray-400 px-2 py-0.5 rounded-full">{tag}</span>
                            ))}
                        </div>
                    )}
                    <button
                        onClick={() => useTemplate(template.id)}
                        disabled={creating !== null}
                        className="w-full bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors"
                    >
                        {creating === template.id ? 'Creando proyecto...' : 'Usar plantilla →'}
                    </button>
                </div>
            </div>
        </div>
    );

    return (
        <AuthenticatedLayout header={
            <div>
                <h1 className="text-2xl font-bold text-white">Galería de plantillas</h1>
                <p className="text-gray-400 text-sm mt-1">Elige una base y personalízala con IA</p>
            </div>
        }>
            <Head title="Plantillas" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Nombre del proyecto */}
                <div className="mb-6 flex gap-3 items-center">
                    <input
                        type="text"
                        value={name}
                        onChange={e => setName(e.target.value)}
                        maxLength={100}
                        placeholder="Nombre de tu proyecto (ej: Mi restaurante)"
                        className="flex-1 max-w-md bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500 placeholder-gray-500"
                    />
                    <p className="text-xs text-gray-500">Opcional — puedes cambiarlo luego</p>
                </div>

                {/* Filtros por categoría */}
                <div className="flex gap-2 flex-wrap mb-8" role="group" aria-label="Filtrar por categoría">
                    {['Todos', ...categories].map(cat => (
                        <button
                            key={cat}
                            onClick={() => setFilter(cat)}
                            aria-pressed={filter === cat}
                            className={`px-4 py-2 rounded-full text-sm font-semibold transition-colors ${
                                filter === cat ? 'bg-violet-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-white'
                            }`}
                        >
                            {cat}
                        </button>
                    ))}
                </div>

                {byCategory.length === 0 && (
                    <div className="text-center py-16 text-gray-500 text-sm">
                        No hay plantillas en esta categoría.
                    </div>
                )}

                {/* ── SECCIÓN 3D (destacada, primero) ── */}
                {threeDee.length > 0 && (
                    <section className="mb-14">
                        <div className="flex items-end justify-between mb-5">
                            <div>
                                <h2 className="text-xl font-black text-white flex items-center gap-2">
                                    <span className="bg-gradient-to-r from-violet-400 via-fuchsia-400 to-cyan-400 bg-clip-text text-transparent">✨ Plantillas 3D</span>
                                </h2>
                                <p className="text-sm text-gray-400 mt-1">Las más modernas: fondos WebGL interactivos, motion y diseño premium.</p>
                            </div>
                            <span className="hidden sm:inline text-xs font-semibold text-violet-300 bg-violet-500/10 border border-violet-500/30 px-3 py-1 rounded-full">
                                Recomendadas
                            </span>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            {threeDee.map(t => <Card key={t.id} template={t} big />)}
                        </div>
                    </section>
                )}

                {/* ── SECCIÓN CLÁSICAS ── */}
                {classic.length > 0 && (
                    <section>
                        <div className="mb-5">
                            <h2 className="text-xl font-black text-white">Plantillas clásicas</h2>
                            <p className="text-sm text-gray-400 mt-1">Rápidas, sobrias y listas para cualquier negocio.</p>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                            {classic.map(t => <Card key={t.id} template={t} />)}
                        </div>
                    </section>
                )}
            </div>

            {/* Modal de preview (aquí SÍ se ejecuta el JS: 3D WebGL completo + interacciones) */}
            {previewTemplate && (
                <PreviewModal
                    template={previewTemplate}
                    creating={creating}
                    onClose={() => setPreviewId(null)}
                    onUse={() => { setPreviewId(null); useTemplate(previewTemplate.id); }}
                />
            )}
        </AuthenticatedLayout>
    );
}
