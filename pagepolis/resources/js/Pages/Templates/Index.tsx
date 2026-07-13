import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

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
    has_3d: boolean;
}

interface Props {
    templates: Template[];
    categories: string[];
}

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
        />
    );
}

type PageType = 'Todos' | 'Simple' | '3D';

export default function TemplatesIndex({ templates, categories }: Props) {
    const [filter, setFilter] = useState('Todos');
    const [pageType, setPageType] = useState<PageType>('Todos');
    const [creating, setCreating] = useState<number | null>(null);
    const [name, setName] = useState('');
    const [previewId, setPreviewId] = useState<number | null>(null);

    const filtered = templates
        .filter(t => filter === 'Todos' || t.category === filter)
        .filter(t => pageType === 'Todos' || (pageType === '3D' ? t.has_3d : !t.has_3d));
    const previewTemplate = templates.find(t => t.id === previewId);

    const useTemplate = (templateId: number) => {
        const projectName = name.trim() || templates.find(t => t.id === templateId)?.name || 'Mi proyecto';
        setCreating(templateId);
        router.post('/proyectos', {
            name: projectName,
            template_id: templateId,
        }, {
            onError: () => setCreating(null),
        });
    };

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
                        placeholder="Nombre de tu proyecto (ej: Mi restaurante)"
                        className="flex-1 max-w-md bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500 placeholder-gray-500"
                    />
                    <p className="text-xs text-gray-500">Opcional — puedes cambiarlo luego</p>
                </div>

                {/* Tipo de página: clásica o interactiva en 3D */}
                <div className="mb-5">
                    <div className="inline-flex bg-gray-900 border border-gray-800 rounded-xl p-1 gap-1">
                        {(['Todos', 'Simple', '3D'] as PageType[]).map(type => (
                            <button
                                key={type}
                                onClick={() => setPageType(type)}
                                className={`px-4 py-2 rounded-lg text-sm font-semibold transition-colors ${
                                    pageType === type
                                        ? 'bg-violet-600 text-white'
                                        : 'text-gray-400 hover:text-white'
                                }`}
                            >
                                {type === '3D' ? '✨ 3D interactivas' : type}
                            </button>
                        ))}
                    </div>
                    {pageType === '3D' && (
                        <p className="text-xs text-gray-500 mt-2">
                            Héroes con profundidad, luz e inclinación al mover el ratón — el nivel visual de una agencia premium.
                        </p>
                    )}
                </div>

                {/* Filtros */}
                <div className="flex gap-2 flex-wrap mb-8">
                    {['Todos', ...categories].map(cat => (
                        <button
                            key={cat}
                            onClick={() => setFilter(cat)}
                            className={`px-4 py-2 rounded-full text-sm font-semibold transition-colors ${
                                filter === cat
                                    ? 'bg-violet-600 text-white'
                                    : 'bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-white'
                            }`}
                        >
                            {cat}
                        </button>
                    ))}
                </div>

                {/* Grid de plantillas */}
                {filtered.length === 0 ? (
                    <div className="text-center py-16 border border-dashed border-gray-800 rounded-2xl">
                        <p className="text-gray-400 font-medium">Ninguna plantilla combina esos filtros todavía.</p>
                        <p className="text-gray-600 text-sm mt-1">Prueba otra categoría o cambia el tipo de página.</p>
                    </div>
                ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    {filtered.map(template => (
                        <div
                            key={template.id}
                            className="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden hover:border-violet-700/50 transition-all hover:-translate-y-1 group"
                        >
                            {/* Preview thumbnail */}
                            <div
                                className="h-44 overflow-hidden relative cursor-pointer bg-white"
                                onClick={() => setPreviewId(template.id)}
                            >
                                <TemplatePreview html={template.html} css={template.css} />
                                <div className="absolute top-2 right-2 flex flex-col items-end gap-1 z-10">
                                    {template.is_premium && (
                                        <span className="bg-yellow-500 text-yellow-900 text-xs font-bold px-2 py-0.5 rounded-full">
                                            PRO
                                        </span>
                                    )}
                                    {template.has_3d && (
                                        <span className="bg-violet-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                            ✨ 3D
                                        </span>
                                    )}
                                </div>
                                <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                    <span className="opacity-0 group-hover:opacity-100 transition-opacity text-white text-xs font-semibold bg-black/60 px-3 py-1.5 rounded-full backdrop-blur-sm">
                                        Vista previa
                                    </span>
                                </div>
                            </div>

                            <div className="p-4">
                                <div className="flex justify-between items-start mb-1">
                                    <h3 className="font-bold text-white text-sm">{template.name}</h3>
                                    <span className="text-xs text-gray-600">{template.uses_count} usos</span>
                                </div>
                                <p className="text-xs text-violet-400 mb-3">{template.category}</p>
                                {template.tags && (
                                    <div className="flex flex-wrap gap-1 mb-4">
                                        {template.tags.slice(0, 3).map(tag => (
                                            <span key={tag} className="text-xs bg-gray-800 text-gray-400 px-2 py-0.5 rounded-full">
                                                {tag}
                                            </span>
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
                    ))}
                </div>
                )}
            </div>

            {/* Modal de preview */}
            {previewTemplate && (
                <div
                    className="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4"
                    onClick={() => setPreviewId(null)}
                >
                    <div
                        className="bg-white rounded-xl overflow-hidden w-full max-w-4xl h-[85vh] flex flex-col"
                        onClick={e => e.stopPropagation()}
                    >
                        <div className="flex items-center justify-between px-4 py-3 bg-gray-100 border-b border-gray-200 flex-shrink-0">
                            <div className="flex gap-1.5">
                                <span className="w-3 h-3 rounded-full bg-red-400"></span>
                                <span className="w-3 h-3 rounded-full bg-yellow-400"></span>
                                <span className="w-3 h-3 rounded-full bg-green-400"></span>
                            </div>
                            <span className="text-sm font-semibold text-gray-600">{previewTemplate.name}</span>
                            <div className="flex items-center gap-3">
                                <button
                                    onClick={() => { setPreviewId(null); useTemplate(previewTemplate.id); }}
                                    disabled={creating !== null}
                                    className="bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition-colors disabled:opacity-50"
                                >
                                    Usar esta plantilla →
                                </button>
                                <button onClick={() => setPreviewId(null)} className="text-gray-500 hover:text-gray-700 text-xl font-bold">
                                    ×
                                </button>
                            </div>
                        </div>
                        <iframe
                            srcDoc={`<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${previewTemplate.css}</style></head><body>${previewTemplate.html}<script>${PREVIEW_GUARD}<\/script></body></html>`}
                            sandbox="allow-scripts"
                            className="flex-1 w-full border-0 min-h-0"
                            title={previewTemplate.name}
                        />
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
