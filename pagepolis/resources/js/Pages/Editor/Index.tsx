import { useState, useRef, useEffect, useCallback, lazy, Suspense } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import toast from 'react-hot-toast';
import type { ActiveTab } from './CodeMirrorEditor';

// CodeMirror (~530 kB) solo se carga cuando el usuario activa el modo avanzado
// (ver código), no en cada visita al editor.
const CodeMirrorEditor = lazy(() => import('./CodeMirrorEditor'));

// Guard para las vistas previas: evita que al pulsar enlaces el iframe navegue a la
// app (lo que en local saturaba el servidor → "localhost rechaza la conexión").
// Los enlaces de ancla hacen scroll; el resto no navega. Solo afecta a la PREVIEW,
// no al sitio publicado real.
const PREVIEW_GUARD = "(function(){document.addEventListener('click',function(e){var a=e.target&&e.target.closest&&e.target.closest('a');if(!a)return;var h=a.getAttribute('href')||'';if(h.charAt(0)==='#'){e.preventDefault();try{if(h.length>1){var el=document.querySelector(h);if(el)el.scrollIntoView({behavior:'smooth'});}}catch(x){}}else{e.preventDefault();}},true);document.addEventListener('submit',function(e){e.preventDefault();},true);})();";

// Sugerencias rápidas del chat (modo Modificar). Muchas se resuelven gratis al
// instante (color, fondo, fuente, WhatsApp, ocultar) sin gastar IA.
type Suggestion = { label: string; text?: string; action?: 'image' };
const SUGGESTIONS: Suggestion[] = [
    { label: '🎨 Color principal', text: 'Cambia el color principal a azul' },
    { label: '🖼️ Color de fondo',  text: 'Cambia el color de fondo a ' },
    { label: '🔤 Tipografía',       text: 'Cambia la tipografía a Poppins' },
    { label: '📱 Mi WhatsApp',      text: 'Mi WhatsApp para pedidos es +34 ' },
    { label: '✂️ Quitar sección',   text: 'Quita la sección de testimonios' },
    { label: '📷 Subir logo/foto',  action: 'image' },
];

interface ChatMessage {
    role: 'user' | 'assistant';
    content: string;
    created_at: string;
    type: 'generate' | 'update';
    changed?: string[];
}

interface Project {
    id: number;
    name: string;
    html: string;
    css: string;
    js: string;
    ai_history: ChatMessage[];
    seo_meta: { title?: string; description?: string; keywords?: string } | null;
    status: string;
    ai_status?: string | null;
    ai_progress?: string | null;
}

interface AiUsage {
    used: number;
    limit: number;
    tier: string;
    isSubscribed: boolean;
}

interface Props {
    project: Project;
    aiUsage: AiUsage;
}

type ViewMode  = 'desktop' | 'tablet' | 'mobile';
type AiMode    = 'update' | 'generate';

const viewWidths: Record<ViewMode, string> = {
    desktop: '100%',
    tablet:  '768px',
    mobile:  '375px',
};

function ChatBubble({ msg }: { msg: ChatMessage }) {
    const isUser = msg.role === 'user';
    return (
        <div className={`flex ${isUser ? 'justify-end' : 'justify-start'} mb-3`}>
            <div className={`max-w-[85%] rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed ${
                isUser
                    ? 'bg-violet-600 text-white rounded-br-sm'
                    : 'bg-gray-800 text-gray-200 rounded-bl-sm'
            }`}>
                {msg.content}
                {!isUser && msg.type === 'update' && msg.changed && msg.changed.length > 0 && (
                    <div className="mt-1.5 flex gap-1 flex-wrap">
                        {msg.changed.map(c => (
                            <span key={c} className="text-xs bg-green-900/50 text-green-400 border border-green-800/50 px-1.5 py-0.5 rounded font-mono">
                                {c}
                            </span>
                        ))}
                    </div>
                )}
                {!isUser && msg.type === 'generate' && (
                    <span className="ml-2 text-xs text-violet-400">regenerado</span>
                )}
            </div>
        </div>
    );
}

function ConfirmModal({ message, onConfirm, onCancel }: { message: string; onConfirm: () => void; onCancel: () => void }) {
    return (
        <div className="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
            <div className="bg-gray-900 border border-gray-700 rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl">
                <p className="text-white text-sm leading-relaxed mb-5">{message}</p>
                <div className="flex gap-3">
                    <button onClick={onCancel} className="flex-1 border border-gray-700 text-gray-300 hover:text-white py-2 rounded-lg text-sm transition-colors">
                        Cancelar
                    </button>
                    <button onClick={onConfirm} className="flex-1 bg-violet-600 hover:bg-violet-500 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                        Continuar
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function EditorIndex({ project, aiUsage }: Props) {
    const [name, setName]           = useState(project.name);
    const [html, setHtml]           = useState(project.html);
    const [css,  setCss]            = useState(project.css);
    const [js,   setJs]             = useState(project.js);
    const [prompt, setPrompt]       = useState('');
    const [aiMode, setAiMode]       = useState<AiMode>('update');
    const [aiLoading, setAiLoading] = useState(false);
    const [saving, setSaving]       = useState(false);
    const [saved,  setSaved]        = useState(false);
    const [dirty,  setDirty]        = useState(false);
    const [activeTab, setActiveTab] = useState<ActiveTab>('html');
    const [viewMode, setViewMode]   = useState<ViewMode>('desktop');
    const [aiError, setAiError]     = useState('');
    const [messages, setMessages]   = useState<ChatMessage[]>(project.ai_history ?? []);
    const [seoMeta, setSeoMeta]     = useState(project.seo_meta);
    const [seoLoading, setSeoLoading] = useState(false);
    const [confirmModal, setConfirmModal] = useState<{ message: string; onConfirm: () => void } | null>(null);
    const [showUsageTooltip, setShowUsageTooltip] = useState(false);
    const [progress, setProgress]   = useState('');
    const [usedToday, setUsedToday] = useState(aiUsage.used);
    const [images, setImages]       = useState<File[]>([]);
    const [simpleMode, setSimpleMode] = useState(true);   // el usuario no ve código por defecto

    const iframeRef  = useRef<HTMLIFrameElement>(null);
    const chatEndRef = useRef<HTMLDivElement>(null);
    const pollRef    = useRef<number | null>(null);
    const imageInput = useRef<HTMLInputElement>(null);
    const promptInput = useRef<HTMLTextAreaElement>(null);

    const addImages = (files: FileList | null) => {
        if (!files) return;
        const incoming = Array.from(files).filter(f => f.type.startsWith('image/'));
        setImages(prev => [...prev, ...incoming].slice(0, 4));   // máximo 4
    };

    const applySuggestion = (s: Suggestion) => {
        if (s.action === 'image') { imageInput.current?.click(); return; }
        if (aiMode !== 'update') setAiMode('update');
        setPrompt(s.text ?? '');
        setTimeout(() => {
            const el = promptInput.current;
            if (el) { el.focus(); el.setSelectionRange(el.value.length, el.value.length); }
        }, 0);
    };

    // Aviso al salir con cambios sin guardar
    useEffect(() => {
        const handler = (e: BeforeUnloadEvent) => {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        };
        window.addEventListener('beforeunload', handler);
        return () => window.removeEventListener('beforeunload', handler);
    }, [dirty]);

    const buildPreviewHtml = useCallback(() =>
        `<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}</style></head><body>${html}<script>${PREVIEW_GUARD}<\/script><script>${js}<\/script></body></html>`,
    [html, css, js]);

    useEffect(() => {
        if (iframeRef.current) iframeRef.current.srcdoc = buildPreviewHtml();
    }, [buildPreviewHtml]);

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const markDirty = () => setDirty(true);

    const handleHtmlChange = (v: string) => { setHtml(v); markDirty(); };
    const handleCssChange  = (v: string) => { setCss(v);  markDirty(); };
    const handleJsChange   = (v: string) => { setJs(v);   markDirty(); };

    const stopPolling = useCallback(() => {
        if (pollRef.current) { clearInterval(pollRef.current); pollRef.current = null; }
        setAiLoading(false);
        setProgress('');
    }, []);

    // Sondea el estado de la generación en segundo plano hasta que termina.
    const pollStatus = useCallback(() => {
        if (pollRef.current) return;
        setAiLoading(true);

        const tick = async () => {
            try {
                const { data } = await axios.get(`/ai/estado/${project.id}`);
                if (data.status === 'generating') {
                    setProgress(data.progress || 'Trabajando…');
                    return;
                }
                stopPolling();
                if (data.status === 'ready') {
                    setHtml(data.html); setCss(data.css); setJs(data.js ?? '');
                    setDirty(true);
                    setMessages(prev => [...prev, {
                        role: 'assistant',
                        content: data.description ?? 'Listo.',
                        created_at: new Date().toISOString(),
                        type: data.type ?? 'generate',
                        changed: data.changed ?? [],
                    }]);
                } else if (data.status === 'error') {
                    setAiError(data.error ?? 'No se pudo completar. Inténtalo de nuevo.');
                }
            } catch {
                stopPolling();
                setAiError('Se perdió la conexión con el servidor. Recarga la página.');
            }
        };

        tick();
        pollRef.current = window.setInterval(tick, 2500);
    }, [project.id, stopPolling]);

    // Si al abrir el editor ya hay una generación en curso, reanuda el sondeo.
    useEffect(() => {
        if (project.ai_status === 'generating') {
            setProgress(project.ai_progress || 'Generando…');
            pollStatus();
        }
        return () => { if (pollRef.current) clearInterval(pollRef.current); };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const generateSeo = async () => {
        setSeoLoading(true);
        try {
            const res = await axios.post('/ai/seo', { project_id: project.id });
            if (res.data.success) {
                setSeoMeta(res.data.meta);
                toast.success('SEO generado y guardado');
            }
        } catch (err: any) {
            toast.error(err.response?.data?.error ?? 'No se pudo generar el SEO. Inténtalo de nuevo.');
        } finally {
            setSeoLoading(false);
        }
    };

    const save = async () => {
        setSaving(true);
        try {
            await axios.post(`/editor/${project.id}/guardar`, { name, html, css, js });
            setSaved(true);
            setDirty(false);
            setTimeout(() => setSaved(false), 2500);
        } finally {
            setSaving(false);
        }
    };

    // Autosave con debounce: guarda solo 2,5 s después del último cambio.
    // Se pospone si ya hay un guardado en curso o la IA está generando;
    // al terminar cualquiera de los dos, el efecto se reevalúa y guarda.
    useEffect(() => {
        if (!dirty || saving || aiLoading) return;
        const timer = window.setTimeout(save, 2500);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [html, css, js, name, dirty, saving, aiLoading]);

    const requestModeSwitch = (newMode: AiMode) => {
        if (newMode === aiMode) return;
        if (newMode === 'generate') {
            setConfirmModal({
                message: 'El modo Regenerar reescribirá toda tu web desde cero. Los cambios que no hayas guardado se perderán. ¿Continuar?',
                onConfirm: () => { setAiMode('generate'); setConfirmModal(null); },
            });
        } else {
            setAiMode(newMode);
        }
    };

    const sendToAI = async () => {
        if (aiLoading) return;
        if (!prompt.trim() && images.length === 0) return;
        setAiError('');

        const mode = aiMode;
        const text = prompt.trim() || (mode === 'update'
            ? 'Aplica el contenido de las imágenes que te he adjuntado.'
            : 'Crea la web usando el contenido de las imágenes que te he adjuntado.');
        const shown = prompt.trim()
            ? prompt + (images.length ? `  📎 ${images.length} imagen(es)` : '')
            : `📎 ${images.length} imagen(es) adjunta(s)`;

        const userMsg: ChatMessage = {
            role: 'user', content: shown,
            created_at: new Date().toISOString(),
            type: mode,
        };
        setMessages(prev => [...prev, userMsg]);
        const attached = images;
        setPrompt('');
        setImages([]);
        setAiLoading(true);
        setProgress(mode === 'update' ? 'Aplicando tu cambio…' : 'Poniendo a trabajar a la IA…');

        try {
            const url  = mode === 'update' ? '/ai/actualizar' : '/ai/generar';
            const form = new FormData();
            form.append('project_id', String(project.id));
            form.append(mode === 'update' ? 'instruction' : 'prompt', text);
            attached.forEach(f => form.append('images[]', f));

            const res = await axios.post(url, form);
            if (res.data.status === 'done') {
                // Cambio resuelto en local, al instante y SIN gastar IA.
                setHtml(res.data.html); setCss(res.data.css); setJs(res.data.js ?? '');
                setDirty(true);
                setMessages(prev => [...prev, {
                    role: 'assistant',
                    content: res.data.description ?? 'Hecho.',
                    created_at: new Date().toISOString(),
                    type: 'update', changed: res.data.changed ?? [],
                }]);
                setAiLoading(false);
                setProgress('');
            } else if (res.data.status === 'generating') {
                setUsedToday(u => u + 1);
                pollStatus();   // empieza a sondear hasta que el job termine
            }
        } catch (err: any) {
            const errorText = err.response?.data?.error ?? 'Error al procesar. Inténtalo de nuevo.';
            setAiError(errorText);
            setMessages(prev => prev.slice(0, -1));
            setImages(attached);   // recupera las imágenes para reintentar
            setAiLoading(false);
            setProgress('');
        }
    };

    const currentValue = activeTab === 'html' ? html : activeTab === 'css' ? css : js;
    const handleChange = activeTab === 'html' ? handleHtmlChange : activeTab === 'css' ? handleCssChange : handleJsChange;

    const usagePct = aiUsage.limit > 0 ? usedToday / aiUsage.limit : 0;
    const usageColor = usagePct >= 1 ? 'text-red-400 bg-red-900/40'
        : usagePct >= 0.8 ? 'text-yellow-400 bg-yellow-900/40'
        : 'text-gray-500 bg-gray-800';

    return (
        <div className="h-screen flex flex-col bg-gray-950 text-white overflow-hidden">
            <Head title={`Editor — ${name}`} />

            {confirmModal && (
                <ConfirmModal
                    message={confirmModal.message}
                    onConfirm={confirmModal.onConfirm}
                    onCancel={() => setConfirmModal(null)}
                />
            )}

            {/* Barra superior */}
            <div className="flex items-center justify-between px-4 py-2.5 border-b border-gray-800 bg-gray-900 flex-shrink-0">
                <div className="flex items-center gap-4">
                    <a href="/dashboard" className="text-gray-500 hover:text-white transition-colors text-sm">
                        &larr; Mis proyectos
                    </a>
                    <input
                        value={name}
                        onChange={e => { setName(e.target.value); markDirty(); }}
                        className="bg-transparent text-white font-semibold text-base focus:outline-none border-b border-transparent focus:border-gray-600 px-1 min-w-0"
                    />
                    {dirty && <span className="text-xs text-yellow-500 flex-shrink-0">Sin guardar</span>}
                </div>
                <div className="flex items-center gap-2">
                    <button
                        onClick={generateSeo}
                        disabled={seoLoading || !html}
                        title="Genera título, descripción y datos SEO automáticamente"
                        className={`text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors ${
                            seoMeta
                                ? 'bg-green-900/40 text-green-400 border border-green-800/50'
                                : 'bg-gray-800 text-gray-400 hover:text-white border border-gray-700'
                        }`}
                    >
                        {seoLoading ? 'Generando…' : seoMeta ? 'SEO activo' : 'Generar SEO'}
                    </button>
                    <div className="flex gap-1 bg-gray-800 rounded-lg p-1">
                        {([
                            { id: 'desktop', label: 'PC' },
                            { id: 'tablet',  label: 'Tablet' },
                            { id: 'mobile',  label: 'Móvil' },
                        ] as { id: ViewMode; label: string }[]).map(m => (
                            <button
                                key={m.id}
                                onClick={() => setViewMode(m.id)}
                                className={`px-2.5 py-1 rounded text-xs font-semibold transition-colors ${viewMode === m.id ? 'bg-gray-600 text-white' : 'text-gray-400 hover:text-white'}`}
                            >
                                {m.label}
                            </button>
                        ))}
                    </div>
                    <button
                        onClick={() => setSimpleMode(s => !s)}
                        title={simpleMode ? 'Mostrar el código (modo avanzado)' : 'Ocultar el código (modo sencillo)'}
                        className={`text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors ${
                            simpleMode
                                ? 'bg-gray-800 text-gray-400 hover:text-white border-gray-700'
                                : 'bg-violet-900/40 text-violet-300 border-violet-800/50'
                        }`}
                    >
                        {simpleMode ? '‹ › Código' : '‹ › Ocultar código'}
                    </button>
                    <button
                        onClick={save}
                        disabled={saving}
                        className={`text-sm font-semibold px-4 py-1.5 rounded-lg transition-colors ${
                            saved ? 'bg-green-800 text-green-300'
                            : dirty ? 'bg-violet-700 hover:bg-violet-600 text-white'
                            : 'bg-gray-700 hover:bg-gray-600 text-white'
                        } disabled:opacity-50`}
                    >
                        {saving ? 'Guardando…' : saved ? 'Guardado' : 'Guardar'}
                    </button>
                    <a
                        href={`/publicar?project_id=${project.id}`}
                        className="bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold px-4 py-1.5 rounded-lg transition-colors"
                    >
                        Publicar
                    </a>
                </div>
            </div>

            {/* Cuerpo */}
            <div className="flex flex-1 overflow-hidden">

                {/* Panel IA */}
                <div className="w-72 flex-shrink-0 border-r border-gray-800 bg-gray-900 flex flex-col">

                    {/* Selector de modo */}
                    <div className="flex border-b border-gray-800 flex-shrink-0">
                        <button
                            onClick={() => requestModeSwitch('update')}
                            className={`flex-1 py-2.5 text-xs font-semibold transition-colors ${
                                aiMode === 'update'
                                    ? 'bg-gray-800 text-white border-b-2 border-emerald-500'
                                    : 'text-gray-500 hover:text-white'
                            }`}
                        >
                            Modificar
                        </button>
                        <button
                            onClick={() => requestModeSwitch('generate')}
                            className={`flex-1 py-2.5 text-xs font-semibold transition-colors ${
                                aiMode === 'generate'
                                    ? 'bg-gray-800 text-white border-b-2 border-violet-500'
                                    : 'text-gray-500 hover:text-white'
                            }`}
                        >
                            Regenerar
                        </button>
                    </div>

                    {/* Descripción del modo */}
                    <div className="px-3 py-2 bg-gray-800/50 border-b border-gray-800 flex-shrink-0">
                        <p className="text-xs text-gray-500 leading-snug">
                            {aiMode === 'update'
                                ? 'Modifica partes concretas sin tocar el resto de la web.'
                                : 'Genera una web completamente nueva desde cero.'
                            }
                        </p>
                    </div>

                    {/* Historial de chat */}
                    <div className="flex-1 overflow-y-auto p-3">
                        {messages.length === 0 && (
                            <div className="py-6 px-1">
                                {aiMode === 'update' ? (
                                    <>
                                        <div className="text-center mb-4">
                                            <div className="text-2xl mb-1">💬</div>
                                            <p className="text-sm text-gray-300 font-semibold">Dime qué quieres cambiar</p>
                                            <p className="text-xs text-gray-600 mt-1 leading-relaxed">Escríbelo con tus palabras o usa una sugerencia. Lo simple es al instante y gratis.</p>
                                        </div>
                                        <div className="flex flex-wrap gap-1.5 justify-center">
                                            {SUGGESTIONS.map(s => (
                                                <button
                                                    key={s.label}
                                                    onClick={() => applySuggestion(s)}
                                                    className="text-[11px] font-medium bg-gray-800 hover:bg-gray-700 text-gray-300 border border-gray-700 rounded-full px-2.5 py-1 transition-colors"
                                                >
                                                    {s.label}
                                                </button>
                                            ))}
                                        </div>
                                    </>
                                ) : (
                                    <p className="text-center text-gray-600 text-xs leading-relaxed">
                                        Describe la web que quieres crear. Por ejemplo: "web para una clínica dental en Málaga con sección de servicios y contacto".
                                    </p>
                                )}
                            </div>
                        )}
                        {messages.map((msg, i) => <ChatBubble key={i} msg={msg} />)}
                        {aiLoading && (
                            <div className="flex justify-start mb-3">
                                <div className="bg-gray-800 rounded-2xl rounded-bl-sm px-4 py-3 max-w-[85%]">
                                    <div className="flex items-center gap-2">
                                        <span className="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
                                        <span className="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
                                        <span className="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
                                    </div>
                                    {progress && <p className="text-xs text-gray-400 mt-2 leading-snug">{progress}</p>}
                                    <p className="text-[10px] text-gray-600 mt-1.5">Una web completa puede tardar 1-3 min. Puedes seguir editando mientras tanto.</p>
                                </div>
                            </div>
                        )}
                        <div ref={chatEndRef} />
                    </div>

                    {/* Input */}
                    <div className="p-3 border-t border-gray-800 flex-shrink-0">
                        {aiError && (
                            <div className="mb-2 p-2 bg-red-900/20 border border-red-800/50 rounded-lg">
                                <p className="text-xs text-red-400">{aiError}</p>
                            </div>
                        )}
                        {/* Sugerencias rápidas (cuando ya hay conversación) */}
                        {aiMode === 'update' && !aiLoading && messages.length > 0 && (
                            <div className="mb-2 flex flex-wrap gap-1.5">
                                {SUGGESTIONS.map(s => (
                                    <button
                                        key={s.label}
                                        onClick={() => applySuggestion(s)}
                                        className="text-[11px] font-medium bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white border border-gray-700 rounded-full px-2.5 py-1 transition-colors"
                                    >
                                        {s.label}
                                    </button>
                                ))}
                            </div>
                        )}
                        {/* Imágenes adjuntas */}
                        {images.length > 0 && (
                            <div className="mb-2 flex flex-wrap gap-2">
                                {images.map((f, i) => (
                                    <div key={i} className="relative group">
                                        <img src={URL.createObjectURL(f)} alt={f.name} className="w-14 h-14 object-cover rounded-lg border border-gray-700" />
                                        <button
                                            onClick={() => setImages(prev => prev.filter((_, j) => j !== i))}
                                            className="absolute -top-1.5 -right-1.5 bg-gray-900 border border-gray-600 text-gray-300 hover:text-white rounded-full w-5 h-5 text-xs leading-none flex items-center justify-center"
                                            title="Quitar"
                                        >×</button>
                                    </div>
                                ))}
                            </div>
                        )}

                        <div className="flex items-end gap-2">
                            <textarea
                                ref={promptInput}
                                value={prompt}
                                onChange={e => setPrompt(e.target.value)}
                                placeholder={
                                    aiMode === 'update'
                                        ? "¿Qué quieres cambiar? Ej: \"pon el menú en negro\" o adjunta una foto de tu carta"
                                        : "Describe tu web, o adjunta una foto (carta, logo, folleto)…"
                                }
                                rows={3}
                                className="flex-1 bg-gray-800 border border-gray-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500 resize-none placeholder-gray-600 leading-snug"
                                onKeyDown={e => { if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) sendToAI(); }}
                            />
                            <input
                                ref={imageInput}
                                type="file"
                                accept="image/*"
                                multiple
                                className="hidden"
                                onChange={e => { addImages(e.target.files); if (imageInput.current) imageInput.current.value = ''; }}
                            />
                            <button
                                type="button"
                                onClick={() => imageInput.current?.click()}
                                disabled={images.length >= 4}
                                title="Adjuntar foto (carta, logo, folleto…)"
                                className="flex-shrink-0 w-11 h-11 flex items-center justify-center bg-gray-800 border border-gray-700 rounded-xl text-gray-400 hover:text-white hover:border-gray-600 transition disabled:opacity-40"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            </button>
                        </div>
                        <button
                            onClick={sendToAI}
                            disabled={aiLoading || (!prompt.trim() && images.length === 0) || (aiMode === 'generate' && usedToday >= aiUsage.limit)}
                            className={`mt-2 w-full text-white text-sm font-bold py-2.5 rounded-xl transition-opacity disabled:opacity-40 ${
                                aiMode === 'update'
                                    ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:opacity-90'
                                    : 'bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:opacity-90'
                            }`}
                        >
                            {aiLoading ? 'Generando…' : aiMode === 'update' ? 'Aplicar cambio' : 'Generar web'}
                        </button>

                        {/* Contador de usos */}
                        <div className="mt-2 flex items-center justify-between">
                            <p className="text-xs text-gray-700">Ctrl+Enter para enviar</p>
                            <div className="relative">
                                <button
                                    onMouseEnter={() => setShowUsageTooltip(true)}
                                    onMouseLeave={() => setShowUsageTooltip(false)}
                                    className={`text-xs font-semibold px-2 py-0.5 rounded-full cursor-default ${usageColor}`}
                                >
                                    {usedToday}/{aiUsage.limit} hoy
                                </button>
                                {showUsageTooltip && (
                                    <div className="absolute bottom-full right-0 mb-1 bg-gray-800 border border-gray-700 rounded-lg px-2.5 py-2 text-xs text-gray-300 whitespace-nowrap shadow-xl">
                                        Se reinicia a medianoche cada día.
                                        {!aiUsage.isSubscribed && <><br/>Mejora tu plan para más llamadas.</>}
                                    </div>
                                )}
                            </div>
                        </div>

                        {usedToday >= aiUsage.limit && (
                            <div className="mt-2 p-2 bg-amber-900/20 border border-amber-800/40 rounded-lg text-xs text-amber-300/90 leading-relaxed">
                                Has agotado tus cambios con <strong>IA</strong> de hoy. Los cambios sencillos (colores, fuente, WhatsApp, ocultar secciones…) <strong>siguen siendo gratis</strong>.{' '}
                                {!aiUsage.isSubscribed && (
                                    <a href="/publicar" className="underline text-violet-300">Mejora tu plan</a>
                                )}
                            </div>
                        )}

                        {!aiUsage.isSubscribed && usedToday < aiUsage.limit && (
                            <a href="/publicar" className="mt-2 block text-center text-xs text-gray-600 hover:text-violet-400 transition-colors">
                                Activar plan para más cambios con IA
                            </a>
                        )}
                    </div>
                </div>

                {/* Preview */}
                <div className="flex-1 bg-gray-800 flex items-start justify-center overflow-auto p-4">
                    <div style={{ width: viewWidths[viewMode], transition: 'width 0.3s ease' }} className="h-full min-h-0 relative">
                        <iframe
                            ref={iframeRef}
                            sandbox="allow-scripts allow-same-origin"
                            className="w-full h-full bg-white rounded-lg shadow-2xl"
                            title="Vista previa"
                        />
                        {aiLoading && (
                            <div className="absolute inset-0 rounded-lg bg-gray-900/70 backdrop-blur-sm flex flex-col items-center justify-center gap-3 pointer-events-none">
                                <div className="flex items-center gap-2">
                                    <span className="w-2 h-2 bg-violet-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
                                    <span className="w-2 h-2 bg-violet-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
                                    <span className="w-2 h-2 bg-violet-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
                                </div>
                                <p className="text-sm text-gray-300 font-medium px-4 text-center">
                                    {progress || 'Generando tu web…'}
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Editor de código (solo en modo avanzado) */}
                {!simpleMode && (
                <div className="w-96 flex-shrink-0 border-l border-gray-800 bg-gray-900 flex flex-col">
                    <div className="flex border-b border-gray-800 flex-shrink-0">
                        {(['html', 'css', 'js'] as ActiveTab[]).map(tab => (
                            <button
                                key={tab}
                                onClick={() => setActiveTab(tab)}
                                className={`flex-1 py-3 text-xs font-bold uppercase tracking-wider transition-colors ${
                                    activeTab === tab
                                        ? 'bg-gray-800 text-white border-b-2 border-violet-500'
                                        : 'text-gray-500 hover:text-white'
                                }`}
                            >
                                {tab}
                            </button>
                        ))}
                    </div>
                    <div className="flex-1 overflow-hidden">
                        <Suspense fallback={<div className="flex items-center justify-center h-full text-xs text-gray-500">Cargando editor…</div>}>
                            <CodeMirrorEditor value={currentValue} onChange={handleChange} language={activeTab} />
                        </Suspense>
                    </div>
                </div>
                )}
            </div>
        </div>
    );
}
