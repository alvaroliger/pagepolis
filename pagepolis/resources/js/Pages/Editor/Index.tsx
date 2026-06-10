import { useState, useRef, useEffect, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import toast from 'react-hot-toast';

import { EditorView, keymap, lineNumbers, highlightActiveLine, highlightActiveLineGutter, drawSelection } from '@codemirror/view';
import { EditorState, Compartment } from '@codemirror/state';
import { defaultKeymap, history, historyKeymap, indentWithTab } from '@codemirror/commands';
import { bracketMatching, indentOnInput, syntaxHighlighting, defaultHighlightStyle, foldGutter, foldKeymap } from '@codemirror/language';
import { autocompletion, completionKeymap, closeBrackets, closeBracketsKeymap } from '@codemirror/autocomplete';
import { oneDark } from '@codemirror/theme-one-dark';
import { html as htmlLang } from '@codemirror/lang-html';
import { css as cssLang } from '@codemirror/lang-css';
import { javascript as jsLang } from '@codemirror/lang-javascript';

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

type ActiveTab = 'html' | 'css' | 'js';
type ViewMode  = 'desktop' | 'tablet' | 'mobile';
type AiMode    = 'update' | 'generate';

const viewWidths: Record<ViewMode, string> = {
    desktop: '100%',
    tablet:  '768px',
    mobile:  '375px',
};

const langExtension: Record<ActiveTab, () => any> = {
    html: htmlLang,
    css:  cssLang,
    js:   () => jsLang({ jsx: false }),
};

function CodeMirrorEditor({ value, onChange, language }: { value: string; onChange: (v: string) => void; language: ActiveTab }) {
    const containerRef = useRef<HTMLDivElement>(null);
    const viewRef      = useRef<EditorView | null>(null);
    const langComp     = useRef(new Compartment());
    const onChangeRef  = useRef(onChange);
    onChangeRef.current = onChange;

    useEffect(() => {
        if (!containerRef.current) return;
        const view = new EditorView({
            state: EditorState.create({
                doc: value,
                extensions: [
                    lineNumbers(), highlightActiveLine(), highlightActiveLineGutter(), drawSelection(),
                    foldGutter(), history(), bracketMatching(), closeBrackets(), indentOnInput(), autocompletion(),
                    oneDark,
                    syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
                    langComp.current.of(langExtension[language]()),
                    keymap.of([...defaultKeymap, ...historyKeymap, ...completionKeymap, ...closeBracketsKeymap, ...foldKeymap, indentWithTab]),
                    EditorView.updateListener.of(u => { if (u.docChanged) onChangeRef.current(u.state.doc.toString()); }),
                    EditorView.theme({
                        '&': { height: '100%', fontSize: '12.5px' },
                        '.cm-scroller': { overflow: 'auto', fontFamily: '"Fira Code", "Cascadia Code", monospace' },
                        '.cm-content': { padding: '8px 0' },
                    }),
                ],
            }),
            parent: containerRef.current,
        });
        viewRef.current = view;
        return () => { view.destroy(); viewRef.current = null; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        if (!viewRef.current) return;
        viewRef.current.dispatch({ effects: langComp.current.reconfigure(langExtension[language]()) });
    }, [language]);

    useEffect(() => {
        const view = viewRef.current;
        if (!view) return;
        const current = view.state.doc.toString();
        if (current !== value) view.dispatch({ changes: { from: 0, to: current.length, insert: value } });
    }, [value]);

    return <div ref={containerRef} className="flex-1 overflow-hidden h-full" />;
}

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

    const iframeRef  = useRef<HTMLIFrameElement>(null);
    const chatEndRef = useRef<HTMLDivElement>(null);

    // Aviso al salir con cambios sin guardar
    useEffect(() => {
        const handler = (e: BeforeUnloadEvent) => {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        };
        window.addEventListener('beforeunload', handler);
        return () => window.removeEventListener('beforeunload', handler);
    }, [dirty]);

    const buildPreviewHtml = useCallback(() =>
        `<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}</style></head><body>${html}<script>${js}<\/script></body></html>`,
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
        if (!prompt.trim()) return;
        setAiLoading(true);
        setAiError('');

        const userMsg: ChatMessage = {
            role: 'user', content: prompt,
            created_at: new Date().toISOString(),
            type: aiMode,
        };
        setMessages(prev => [...prev, userMsg]);
        setPrompt('');

        try {
            if (aiMode === 'update') {
                const res = await axios.post('/ai/actualizar', { instruction: userMsg.content, project_id: project.id });
                if (res.data.success) {
                    setHtml(res.data.html); setCss(res.data.css); setJs(res.data.js ?? '');
                    setDirty(true);
                    setMessages(prev => [...prev, {
                        role: 'assistant', content: res.data.description,
                        created_at: new Date().toISOString(),
                        type: 'update', changed: res.data.changed ?? [],
                    }]);
                }
            } else {
                const res = await axios.post('/ai/generar', { prompt: userMsg.content, project_id: project.id });
                if (res.data.success) {
                    setHtml(res.data.html); setCss(res.data.css); setJs(res.data.js ?? '');
                    setDirty(true);
                    setMessages(prev => [...prev, {
                        role: 'assistant', content: res.data.description ?? 'Web generada.',
                        created_at: new Date().toISOString(),
                        type: 'generate',
                    }]);
                }
            }
        } catch (err: any) {
            const errorText = err.response?.data?.error ?? 'Error al procesar. Inténtalo de nuevo.';
            setAiError(errorText);
            setMessages(prev => prev.slice(0, -1));
        } finally {
            setAiLoading(false);
        }
    };

    const currentValue = activeTab === 'html' ? html : activeTab === 'css' ? css : js;
    const handleChange = activeTab === 'html' ? handleHtmlChange : activeTab === 'css' ? handleCssChange : handleJsChange;

    const usagePct = aiUsage.limit > 0 ? aiUsage.used / aiUsage.limit : 0;
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
                        onChange={e => setName(e.target.value)}
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
                            <div className="text-center py-8 text-gray-600 text-xs px-3 leading-relaxed">
                                {aiMode === 'update'
                                    ? 'Escribe lo que quieres cambiar. Por ejemplo: "pon el fondo azul" o "añade una sección de precios".'
                                    : 'Describe la web que quieres crear. Por ejemplo: "web para una clínica dental en Málaga".'
                                }
                            </div>
                        )}
                        {messages.map((msg, i) => <ChatBubble key={i} msg={msg} />)}
                        {aiLoading && (
                            <div className="flex justify-start mb-3">
                                <div className="bg-gray-800 rounded-2xl rounded-bl-sm px-4 py-3 flex items-center gap-2">
                                    <span className="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
                                    <span className="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
                                    <span className="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
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
                        <textarea
                            value={prompt}
                            onChange={e => setPrompt(e.target.value)}
                            placeholder={
                                aiMode === 'update'
                                    ? "¿Qué quieres cambiar? Ej: \"pon el menú en negro\" o \"cambia el horario a 9-20h\""
                                    : "Describe la web que quieres crear desde cero..."
                            }
                            rows={3}
                            className="w-full bg-gray-800 border border-gray-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500 resize-none placeholder-gray-600 leading-snug"
                            onKeyDown={e => { if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) sendToAI(); }}
                        />
                        <button
                            onClick={sendToAI}
                            disabled={aiLoading || !prompt.trim() || aiUsage.used >= aiUsage.limit}
                            className={`mt-2 w-full text-white text-sm font-bold py-2.5 rounded-xl transition-opacity disabled:opacity-40 ${
                                aiMode === 'update'
                                    ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:opacity-90'
                                    : 'bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:opacity-90'
                            }`}
                        >
                            {aiLoading ? 'Procesando…' : aiMode === 'update' ? 'Aplicar cambio' : 'Generar web'}
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
                                    {aiUsage.used}/{aiUsage.limit} hoy
                                </button>
                                {showUsageTooltip && (
                                    <div className="absolute bottom-full right-0 mb-1 bg-gray-800 border border-gray-700 rounded-lg px-2.5 py-2 text-xs text-gray-300 whitespace-nowrap shadow-xl">
                                        Se reinicia a medianoche cada día.
                                        {!aiUsage.isSubscribed && <><br/>Mejora tu plan para más llamadas.</>}
                                    </div>
                                )}
                            </div>
                        </div>

                        {aiUsage.used >= aiUsage.limit && (
                            <div className="mt-2 p-2 bg-red-900/20 border border-red-800/40 rounded-lg text-xs text-red-400">
                                Has usado todos los cambios de hoy.{' '}
                                {!aiUsage.isSubscribed && (
                                    <a href="/publicar" className="underline text-violet-400">Mejora tu plan</a>
                                )}
                            </div>
                        )}

                        {!aiUsage.isSubscribed && aiUsage.used < aiUsage.limit && (
                            <a href="/publicar" className="mt-2 block text-center text-xs text-gray-600 hover:text-violet-400 transition-colors">
                                Activar plan para mas cambios
                            </a>
                        )}
                    </div>
                </div>

                {/* Preview */}
                <div className="flex-1 bg-gray-800 flex items-start justify-center overflow-auto p-4">
                    <div style={{ width: viewWidths[viewMode], transition: 'width 0.3s ease' }} className="h-full min-h-0">
                        <iframe
                            ref={iframeRef}
                            sandbox="allow-scripts allow-same-origin"
                            className="w-full h-full bg-white rounded-lg shadow-2xl"
                            title="Vista previa"
                        />
                    </div>
                </div>

                {/* Editor de código */}
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
                        <CodeMirrorEditor value={currentValue} onChange={handleChange} language={activeTab} />
                    </div>
                </div>
            </div>
        </div>
    );
}
