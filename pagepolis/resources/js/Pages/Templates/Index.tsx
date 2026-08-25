import { useState } from 'react';
import React from 'react';
import { Head, router, useRemember } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useModalA11y } from '@/hooks/useModalA11y';

interface Template {
    id: number;
    name: string;
    category: string;
    thumbnail: string | null;
    tags: string[] | null;
    is_premium: boolean;
    uses_count: number;
    has_3d: boolean;
}

interface Props {
    templates: Template[];
    categories: string[];
}

/**
 * Miniatura de la rejilla: una IMAGEN, no la plantilla renderizada.
 *
 * Antes cada tarjeta montaba un iframe con el html/css real. Medido con 218
 * plantillas: 299 documentos, 91.873 nodos, 38 MB de red y 12,5 s de CPU en
 * el hilo principal, porque los iframes del mismo origen NO tienen proceso
 * propio: los 218 documentos hacían cola en el único hilo del renderizador.
 * Por eso escalonar el montaje no servía — repartía el trabajo, pero el
 * trabajo total no bajaba.
 *
 * La imagen la genera `php artisan templates:thumbnails` renderizando la
 * plantilla de verdad en Chrome, así que se ve lo mismo que se publicará.
 * El navegador la descarga en paralelo, la descodifica fuera del hilo
 * principal y con loading="lazy" ni pide las que no se ven.
 */
// ── Gestor global de "cupos" de preview en vivo ────────────────────────────
// Aunque haya muchas tarjetas en pantalla, nunca hay más de MAX_LIVE iframes
// vivos a la vez. Como el IntersectionObserver ya desmonta lo que sale del
// viewport, en la práctica solo compiten las visibles (~9), pero este tope es
// el seguro que garantiza el rendimiento pase lo que pase (móviles, ventanas
// altas, scroll rápido).
const MAX_LIVE_PREVIEWS = 9;
let livePreviewCount = 0;
const livePreviewWaiters = new Set<() => void>();

function acquireLiveSlot(retry: () => void): boolean {
    if (livePreviewCount < MAX_LIVE_PREVIEWS) {
        livePreviewCount++;
        return true;
    }
    livePreviewWaiters.add(retry); // sin cupo: se reintenta cuando se libere uno
    return false;
}
function releaseLiveSlot() {
    livePreviewCount = Math.max(0, livePreviewCount - 1);
    const next = livePreviewWaiters.values().next();
    if (!next.done) {
        livePreviewWaiters.delete(next.value);
        next.value();
    }
}

function TemplateThumb({ template }: { template: Template }) {
    const [failed, setFailed] = React.useState(false);
    // Preview SIEMPRE EN VIVO para lo que está en pantalla: la tarjeta muestra la
    // plantilla REAL en movimiento (Ken Burns / vídeo / WebGL). Para que la rejilla
    // no monte 271 iframes a la vez —eso tumbaba el navegador—, cada tarjeta se pone
    // en vivo SOLO cuando entra en el viewport (IntersectionObserver) y se congela
    // (vuelve a la imagen) al salir. Así todo lo que ves se mueve, pero el navegador
    // solo trabaja con las visibles (con tope duro de MAX_LIVE_PREVIEWS).
    const [live, setLive] = React.useState(false);
    // El iframe tarda un instante en cargar el render; hasta que su onLoad no
    // dispara, se mantiene invisible y se ve el POSTER (la miniatura) debajo, así
    // no hay parpadeo en negro al entrar en pantalla: se ve bien desde el primer
    // frame y, cuando el render está listo, aparece ya con movimiento.
    const [loaded, setLoaded] = React.useState(false);
    const [dims, setDims] = React.useState({ w: 1280, h: 800 });
    const boxRef = React.useRef<HTMLDivElement>(null);
    const visibleRef = React.useRef(false);
    const hasSlotRef = React.useRef(false);

    React.useEffect(() => {
        const el = boxRef.current;
        if (!el) return;

        const measure = () => {
            const scale = el.clientWidth / 1280 || 0.3;
            setDims({ w: 1280, h: el.clientHeight / scale });
        };
        const goLive = () => {
            if (hasSlotRef.current) return;
            if (acquireLiveSlot(retry)) {
                hasSlotRef.current = true;
                setLoaded(false); // el iframe nuevo arranca invisible hasta que cargue
                measure();
                setLive(true);
            }
        };
        const retry = () => { if (visibleRef.current) goLive(); };
        const stopLive = () => {
            if (hasSlotRef.current) {
                hasSlotRef.current = false;
                setLive(false);
                releaseLiveSlot();
            } else {
                livePreviewWaiters.delete(retry);
            }
        };

        const io = new IntersectionObserver((entries) => {
            const vis = entries[0].isIntersecting;
            visibleRef.current = vis;
            if (vis) goLive(); else stopLive();
        }, { rootMargin: '120px' }); // arranca un pelín antes de entrar en pantalla
        io.observe(el);

        return () => { io.disconnect(); if (hasSlotRef.current) releaseLiveSlot(); livePreviewWaiters.delete(retry); };
    }, []);

    const scale = boxRef.current ? boxRef.current.clientWidth / 1280 : 0.3;

    return (
        <div ref={boxRef} className="w-full h-full relative overflow-hidden bg-gray-800">
            {(!template.thumbnail || failed) ? (
                <div className="w-full h-full flex items-center justify-center">
                    <span className="text-4xl font-bold text-gray-600">{template.name.charAt(0)}</span>
                </div>
            ) : (
                // La imagen se queda debajo como cartel: se ve al instante y mientras
                // el iframe carga, evitando parpadeos al entrar/salir del viewport.
                <img
                    src={template.thumbnail}
                    alt=""
                    loading="lazy"
                    decoding="async"
                    width={640}
                    height={400}
                    onError={() => setFailed(true)}
                    className="w-full h-full object-cover object-top"
                />
            )}
            {live && (
                <iframe
                    src={`/plantillas/${template.id}/render`}
                    sandbox="allow-scripts allow-same-origin"
                    tabIndex={-1}
                    aria-hidden="true"
                    title=""
                    scrolling="no"
                    onLoad={() => setLoaded(true)}
                    style={{
                        width: dims.w, height: dims.h,
                        transform: `scale(${scale})`, transformOrigin: 'top left',
                        // invisible hasta que el render carga -> se ve el poster debajo, sin negro
                        opacity: loaded ? 1 : 0,
                        transition: 'opacity .45s ease',
                    }}
                    className="absolute inset-0 border-0 pointer-events-none"
                />
            )}
        </div>
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
                            <span className="bg-violet-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">3D</span>
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
                {/*
                    La plantilla se pide al servidor como página suelta en vez de
                    inyectarla con srcDoc. Así el motor compartido se enlaza como
                    archivo normal (y el navegador lo cachea entre vistas previas)
                    y la galería no tiene que llevarse el código de las 218.
                    Un solo iframe abierto a la vez: aquí el coste sí es asumible.
                */}
                {/* allow-same-origin es IMPRESCINDIBLE: sin él el iframe es de
                    "origen opaco" y el motor de la plantilla (kinetic/classic) no
                    puede pintar la escena — la preview salía NEGRA. El contenido es
                    una plantilla del catálogo (first-party, no input del usuario),
                    así que compartir origen es seguro. Igual que el Editor. */}
                <iframe
                    src={`/plantillas/${template.id}/render`}
                    sandbox="allow-scripts allow-same-origin"
                    className="flex-1 w-full border-0 min-h-0"
                    title={template.name}
                />
            </div>
        </div>
    );
}

// Tarjeta de una plantilla. VIVE FUERA del render de TemplatesIndex a
// propósito: si se define ahí dentro, React la ve como un componente nuevo en
// cada re-render del padre (escribir en el nombre, abrir la vista previa…) y
// desmonta+remonta las 218 tarjetas de golpe cada vez. Aquí, al ser siempre la
// MISMA función, React solo actualiza lo que cambió; memo evita incluso eso
// cuando ninguna prop de esta tarjeta en concreto ha cambiado.
const Card = React.memo(function Card({ template, big, creating, onPreview, onUse }: {
    template: Template; big?: boolean; creating: number | null;
    onPreview: (id: number) => void; onUse: (id: number) => void;
}) {
    return (
        <div
            data-tpl-card
            className={`group relative rounded-2xl overflow-hidden border transition-colors ${
                big
                    ? 'bg-gray-900 border-violet-800/40 hover:border-violet-600/60'
                    : 'bg-gray-900 border-gray-800 hover:border-violet-700/50'
            }`}
            // Las tarjetas que quedan fuera de pantalla no gastan maquetación
            // ni pintado. "auto" en el tamaño hace que el navegador recuerde
            // el alto real ya medido, así la barra de scroll no da saltos.
            style={{ contentVisibility: 'auto', containIntrinsicSize: 'auto 420px' }}
        >
            {/* La proporción es la MISMA que la de la miniatura (640×400 = 8:5)
                a propósito: con una altura fija, object-cover recortaba los
                lados y se comía el principio de los titulares. */}
            <button
                type="button"
                aria-label={`Vista previa de ${template.name}`}
                className="block w-full text-left overflow-hidden relative cursor-pointer bg-gray-800 aspect-[8/5] focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"
                onClick={() => onPreview(template.id)}
            >
                <TemplateThumb template={template} />
                <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                    <span className="opacity-0 group-hover:opacity-100 transition-opacity text-white text-xs font-semibold bg-black/60 px-3 py-1.5 rounded-full backdrop-blur-sm">
                        Vista previa
                    </span>
                </div>
            </button>

            {/* Debajo de la imagen, no encima: los badges superpuestos en la
                miniatura competían visualmente con el propio diseño. */}
            <div className="p-4">
                <div className="flex items-center justify-between gap-2 mb-1.5">
                    <h3 className={`font-bold text-white truncate ${big ? 'text-lg' : 'text-base'}`}>{template.name}</h3>
                    {(template.has_3d || template.is_premium) && (
                        <div className="flex items-center gap-1.5 flex-shrink-0">
                            {template.has_3d && (
                                <span className="bg-violet-600 text-white text-[11px] font-bold px-2 py-0.5 rounded-full">3D</span>
                            )}
                            {template.is_premium && (
                                <span className="bg-yellow-500 text-yellow-900 text-[11px] font-bold px-2 py-0.5 rounded-full">PRO</span>
                            )}
                        </div>
                    )}
                </div>
                <p className="text-xs text-violet-400 uppercase tracking-wide mb-4">{template.category}</p>
                <button
                    onClick={() => onUse(template.id)}
                    disabled={creating !== null}
                    className="w-full bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors"
                >
                    {creating === template.id ? 'Creando proyecto...' : 'Usar plantilla →'}
                </button>
            </div>
        </div>
    );
});

export default function TemplatesIndex({ templates, categories }: Props) {
    // useRemember guarda estos valores en el historial del navegador. Al ir a
    // una plantilla y volver atrás se recupera el filtro, la búsqueda y las
    // tarjetas que ya estaban cargadas, en vez de empezar de cero cada vez.
    const [filter, setFilter] = useRemember('Todos', 'plantillas:filtro');
    const [query, setQuery] = useRemember('', 'plantillas:busqueda');
    const [creating, setCreating] = useState<number | null>(null);
    const [name, setName] = useState('');
    const nameRef = React.useRef('');
    const [previewId, setPreviewId] = useState<number | null>(null);

    const q = query.trim().toLowerCase();
    const byCategory = React.useMemo(() => templates
        .filter(t => filter === 'Todos' || t.category === filter)
        .filter(t => !q || t.name.toLowerCase().includes(q) || t.category.toLowerCase().includes(q)
            || (t.tags || []).some(tag => tag.toLowerCase().includes(q))), [templates, filter, q]);

    // Cuántas tarjetas hay puestas en el DOM. Montar las 218 de golpe dejaba
    // una página de 30.000 px con 218 imágenes compitiendo por descargarse y
    // descodificarse: aunque cada una sea diferida, el scroll iba a tirones.
    // Se muestran unas pocas y se añaden más al acercarse al final, que es lo
    // que hacen las galerías del sector.
    const PAGE = 24;
    const [shown, setShown] = useRemember(PAGE, 'plantillas:mostradas');
    const sentinelRef = React.useRef<HTMLDivElement | null>(null);

    // Al cambiar filtro o búsqueda se vuelve al primer bloque: si no, pasar de
    // "Todos" a una categoría pequeña dejaría montadas tarjetas de más.
    // La primera pasada no cuenta: al volver atrás hay que respetar lo que ya
    // estaba cargado en vez de recortarlo a 24 otra vez.
    const firstRun = React.useRef(true);
    React.useEffect(() => {
        if (firstRun.current) { firstRun.current = false; return; }
        setShown(PAGE);
    }, [filter, q]);

    React.useEffect(() => {
        const node = sentinelRef.current;
        if (!node || shown >= byCategory.length) return;
        const observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) {
                setShown(s => Math.min(s + PAGE, byCategory.length));
            }
        }, { rootMargin: '600px' });
        observer.observe(node);
        return () => observer.disconnect();
    }, [shown, byCategory.length]);

    const visible = byCategory.slice(0, shown);
    const threeDee = visible.filter(t => t.has_3d);
    const classic = visible.filter(t => !t.has_3d);
    const previewTemplate = templates.find(t => t.id === previewId);

    // Identidad estable (deps vacías): lee el nombre desde una ref en vez de
    // cerrar sobre el state, así escribir en el campo no cambia esta función
    // en cada tecla y no invalida el memo de las tarjetas.
    const useTemplate = React.useCallback((templateId: number) => {
        const projectName = nameRef.current.trim() || templates.find(t => t.id === templateId)?.name || 'Mi proyecto';
        setCreating(templateId);
        router.post('/proyectos', { name: projectName, template_id: templateId }, {
            onFinish: () => setCreating(null),
        });
    }, [templates]);

    // ── Scroll POR FILAS ────────────────────────────────────────────────────
    // Cada gesto de rueda baja/sube exactamente a la siguiente/anterior fila de
    // plantillas, con animación suave (más profesional que el scroll libre del
    // navegador). Se bloquea durante la animación para que el rebote del trackpad
    // no salte varias filas: 1 gesto = 1 fila. NO se toca el teclado (flechas,
    // AvPág, inicio/fin siguen normales) ni el táctil (móvil = scroll normal), ni
    // se activa con el modal abierto ni al hacer zoom (Ctrl+rueda). Respeta
    // "prefers-reduced-motion" saltando sin animación.
    React.useEffect(() => {
        if (previewId !== null) return;                 // modal abierto → scroll normal
        const OFFSET = 64 + 16;                         // nav sticky (64) + aire
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let locked = false;

        const snapPoints = () => {
            const tops = new Set<number>([0]);          // 0 = principio de la página
            document.querySelectorAll('[data-tpl-card]').forEach(el => {
                const top = Math.round(el.getBoundingClientRect().top + window.scrollY - OFFSET);
                tops.add(Math.max(0, top));
            });
            return Array.from(tops).sort((a, b) => a - b);
        };

        const onWheel = (e: WheelEvent) => {
            if (e.ctrlKey || Math.abs(e.deltaY) < 6) return;   // zoom o ruido → ignora
            e.preventDefault();
            if (locked) return;
            const pts = snapPoints();
            const y = window.scrollY;
            const target = e.deltaY > 0
                ? pts.find(p => p > y + 4)                      // siguiente fila
                : [...pts].reverse().find(p => p < y - 4);      // fila anterior
            if (target === undefined) return;                  // ya en el extremo
            locked = true;
            window.scrollTo({ top: target, behavior: reduced ? 'auto' : 'smooth' });
            window.setTimeout(() => { locked = false; }, reduced ? 120 : 600);
        };

        window.addEventListener('wheel', onWheel, { passive: false });
        return () => window.removeEventListener('wheel', onWheel);
    }, [previewId]);

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
                <div className="mb-4 flex gap-3 items-center">
                    <input
                        type="text"
                        value={name}
                        onChange={e => { setName(e.target.value); nameRef.current = e.target.value; }}
                        maxLength={100}
                        placeholder="Nombre de tu proyecto (ej: Mi restaurante)"
                        className="flex-1 max-w-md bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500 placeholder-gray-500"
                    />
                    <p className="text-xs text-gray-500">Opcional — puedes cambiarlo luego</p>
                </div>

                {/* Buscador */}
                <div className="mb-6">
                    <div className="relative max-w-md">
                        <svg className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                        <input
                            type="search"
                            value={query}
                            onChange={e => setQuery(e.target.value)}
                            placeholder="Buscar por nombre, categoría o etiqueta…"
                            aria-label="Buscar plantillas"
                            className="w-full bg-gray-800 border border-gray-700 rounded-lg pl-10 pr-4 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500 placeholder-gray-500"
                        />
                    </div>
                </div>

                {/* Filtro de categoría: un desplegable compacto en vez de una pared
                    de ~22 botones, que competía por atención con las tarjetas. */}
                <div className="flex items-center gap-3 mb-8">
                    <label htmlFor="category-filter" className="sr-only">Filtrar por categoría</label>
                    <div className="relative">
                        <select
                            id="category-filter"
                            value={filter}
                            onChange={e => setFilter(e.target.value)}
                            className="appearance-none bg-gray-800 border border-gray-700 rounded-full pl-4 pr-9 py-2.5 text-sm font-semibold text-white focus:outline-none focus:border-violet-500 cursor-pointer"
                        >
                            {['Todos', ...categories].map(cat => (
                                <option key={cat} value={cat}>{cat}</option>
                            ))}
                        </select>
                        <svg className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    {filter !== 'Todos' && (
                        <button onClick={() => setFilter('Todos')} className="text-xs text-gray-400 hover:text-white underline">
                            Quitar filtro
                        </button>
                    )}
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
                                <h2 className="text-xl font-semibold text-white">Plantillas 3D</h2>
                                <p className="text-sm text-gray-400 mt-1">Las más modernas: fondos WebGL interactivos, motion y diseño premium.</p>
                            </div>
                            <span className="hidden sm:inline text-xs font-semibold text-violet-300 bg-violet-500/10 border border-violet-500/30 px-3 py-1 rounded-full">
                                Recomendadas
                            </span>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                            {threeDee.map(t => (
                                <Card key={t.id} template={t} big creating={creating} onPreview={setPreviewId} onUse={useTemplate} />
                            ))}
                        </div>
                    </section>
                )}

                {/* ── SECCIÓN CLÁSICAS ── */}
                {classic.length > 0 && (
                    <section>
                        <div className="mb-5">
                            <h2 className="text-xl font-semibold text-white">Plantillas clásicas</h2>
                            <p className="text-sm text-gray-400 mt-1">Rápidas, sobrias y listas para cualquier negocio.</p>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-7">
                            {classic.map(t => (
                                <Card key={t.id} template={t} creating={creating} onPreview={setPreviewId} onUse={useTemplate} />
                            ))}
                        </div>
                    </section>
                )}

                {/* Testigo: al acercarse, monta el siguiente bloque. El margen
                    de 600 px hace que las tarjetas ya estén puestas antes de
                    que lleguen a verse, así no hay saltos ni huecos vacíos. */}
                {shown < byCategory.length && (
                    <div ref={sentinelRef} className="py-10 text-center text-sm text-gray-500">
                        Cargando más plantillas… ({shown} de {byCategory.length})
                    </div>
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
