import { useState, useRef, useEffect, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';

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

interface Props {
    project: Project;
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
                    EditorView.theme({ '&': { height: '100%', fontSize: '12.5px' }, '.cm-scroller': { overflow: 'auto', fontFamily: '"Fira Code", "Cascadia Code", monospace' }, '.cm-content': { padding: '8px 0' } }),
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
                    <span className="ml-2 text-xs text-violet-400">⚡ regenerado</span>
                )}
            </div>
        </div>
    );
}

export default function EditorIndex({ project }: Props) {
    const [name, setName]         = useState(project.name);
    const [html, setHtml]         = useState(project.html);
    const [css,  setCss]          = useState(project.css);
    const [js,   setJs]           = useState(project.js);
    const [prompt, setPrompt]     = useState('');
    const [aiMode, setAiMode]     = useState<AiMode>('update');
    const [aiLoading, setAiLoading] = useState(false);
    const [saving, setSaving]     = useState(false);
    const [saved,  setSaved]      = useState(false);
    const [activeTab, setActiveTab] = useState<ActiveTab>('html');
    const [viewMode, setViewMode] = useState<ViewMode>('desktop');
    const [aiError, setAiError]     = useState('');
    const [messages, setMessages]   = useState<ChatMessage[]>(project.ai_history ?? []);
    const [seoMeta, setSeoMeta]     = useState(project.seo_meta);
    const [seoLoading, setSeoLoading] = useState(false);

    const iframeRef  = useRef<HTMLIFrameElement>(null);
    const chatEndRef = useRef<HTMLDivElement>(null);

    const buildPreviewHtml = useCallback(() =>
        `<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}</style></head><body>${html}<script>${js}<\/script></body></html>`,
    [html, css, js]);

    useEffect(() => {
        if (iframeRef.current) iframeRef.current.srcdoc = buildPreviewHtml();
    }, [buildPreviewHtml]);

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const generateSeo = async () => {
        setSeoLoading(true);
        try {
            const res = await axios.post('/ai/seo', { project_id: project.id });
            if (res.data.success) setSeoMeta(res.data.meta);
        } finally {
            setSeoLoading(false);
        }
    };

    const save = async () => {
        setSaving(true);
        try {
            await axios.post(`/editor/${project.id}/guardar`, { name, html, css, js });
            setSaved(true);
            setTimeout(() => setSaved(false), 2000);
        } finally {
            setSaving(false);
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
                    setHtml(res.data.html);
                    setCss(res.data.css);
                    setJs(res.data.js ?? '');
                    setMessages(prev => [...prev, {
                        role: 'assistant', content: res.data.description,
                        created_at: new Date().toISOString(),
                        type: 'update', changed: res.data.changed ?? [],
                    }]);
                }
            } else {
                const res = await axios.post('/ai/generar', { prompt: userMsg.content, project_id: project.id });
                if (res.data.success) {
                    setHtml(res.data.html);
                    setCss(res.data.css);
                    setJs(res.data.js ?? '');
                    setMessages(prev => [...prev, {
                        role: 'assistant', content: res.data.description ?? 'Web regenerada.',
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
    const handleChange = (v: string) => {
        if (activeTab === 'html') setHtml(v);
        else if (activeTab === 'css') setCss(v);
        else setJs(v);
    };

    const modeConfig = {
        update: {
            label:       '✏️ Cambio puntual',
            placeholder: "¿Qué quieres cambiar? Ej: 'pon el fondo azul', 'añade sección de precios', 'cambia el horario a 10-22h'",
            btnLabel:    '✏️ Aplicar cambio',
            btnClass:    'from-emerald-600 to-teal-600',
        },
        generate: {
            label:       '⚡ Regenerar todo',
            placeholder: 'Describe la web que quieres generar desde cero...',
            btnLabel:    '⚡ Regenerar web',
            btnClass:    'from-violet-600 to-fuchsia-600',
        },
    };

    const mc = modeConfig[aiMode];

    return (
        <div className="h-screen flex flex-col bg-gray-950 text-white overflow-hidden">
            <Head title={`Editor — ${name}`} />

            {/* Barra superior */}
            <div className="flex items-center justify-between px-4 py-3 border-b border-gray-800 bg-gray-900 flex-shrink-0">
                <div className="flex items-center gap-4">
                    <a href="/dashboard" className="text-gray-500 hover:text-white transition-colors text-sm">← Dashboard</a>
                    <input
                        value={name}
                        onChange={e => setName(e.target.value)}
                        className="bg-transparent text-white font-semibold text-lg focus:outline-none border-b border-transparent focus:border-gray-600 px-1"
                    />
                </div>
                <div className="flex items-center gap-3">
                    <button
                        onClick={generateSeo}
                        disabled={seoLoading || !html}
                        title="Generar SEO automático (título, descripción, schema.org)"
                        className={`text-xs font-semibold px-3 py-2 rounded-lg transition-colors flex items-center gap-1.5 ${
                            seoMeta
                                ? 'bg-green-900/40 text-green-400 border border-green-800/50'
                                : 'bg-gray-800 text-gray-400 hover:text-white border border-gray-700'
                        }`}
                    >
                        {seoLoading ? '⏳' : seoMeta ? '✓ SEO' : '🔍 SEO'}
                    </button>
                    <div className="flex gap-1 bg-gray-800 rounded-lg p-1">
                        {(['desktop', 'tablet', 'mobile'] as ViewMode[]).map(m => (
                            <button
                                key={m}
                                onClick={() => setViewMode(m)}
                                className={`px-3 py-1 rounded text-xs font-semibold transition-colors ${viewMode === m ? 'bg-gray-600 text-white' : 'text-gray-400 hover:text-white'}`}
                            >
                                {m === 'desktop' ? '🖥️' : m === 'tablet' ? '📱' : '📲'}
                            </button>
                        ))}
                    </div>
                    <button
                        onClick={save}
                        disabled={saving}
                        className="bg-gray-700 hover:bg-gray-600 disabled:opacity-50 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
                    >
                        {saving ? 'Guardando...' : saved ? '✓ Guardado' : 'Guardar'}
                    </button>
                    <a
                        href={`/publicar?project_id=${project.id}`}
                        className="bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold px-4 py-2 rounded-lg transition-colors"
                    >
                        Publicar →
                    </a>
                </div>
            </div>

            {/* Cuerpo */}
            <div className="flex flex-1 overflow-hidden">

                {/* Panel IA — chat */}
                <div className="w-72 flex-shrink-0 border-r border-gray-800 bg-gray-900 flex flex-col">

                    {/* Selector de modo */}
                    <div className="flex border-b border-gray-800 flex-shrink-0">
                        {(['update', 'generate'] as AiMode[]).map(m => (
                            <button
                                key={m}
                                onClick={() => setAiMode(m)}
                                className={`flex-1 py-2.5 text-xs font-semibold transition-colors ${
                                    aiMode === m
                                        ? 'bg-gray-800 text-white border-b-2 border-violet-500'
                                        : 'text-gray-500 hover:text-white'
                                }`}
                            >
                                {modeConfig[m].label}
                            </button>
                        ))}
                    </div>

                    {/* Historial de chat */}
                    <div className="flex-1 overflow-y-auto p-3">
                        {messages.length === 0 && (
                            <div className="text-center py-8 text-gray-600 text-xs px-3">
                                {aiMode === 'update'
                                    ? 'Dile a la IA qué quieres cambiar. Solo modificará esa parte.'
                                    : 'Describe la web y la IA la generará desde cero.'
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
                        {aiError && <p className="text-xs text-red-400 mb-2">{aiError}</p>}
                        <textarea
                            value={prompt}
                            onChange={e => setPrompt(e.target.value)}
                            placeholder={mc.placeholder}
                            rows={3}
                            className="w-full bg-gray-800 border border-gray-700 rounded-xl px-3 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500 resize-none placeholder-gray-600"
                            onKeyDown={e => { if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) sendToAI(); }}
                        />
                        <button
                            onClick={sendToAI}
                            disabled={aiLoading || !prompt.trim()}
                            className={`mt-2 w-full bg-gradient-to-r ${mc.btnClass} hover:opacity-90 disabled:opacity-40 text-white text-sm font-bold py-2.5 rounded-xl transition-opacity`}
                        >
                            {aiLoading ? 'Procesando…' : mc.btnLabel}
                        </button>
                        <p className="text-center text-xs text-gray-700 mt-1.5">Ctrl+Enter para enviar</p>
                    </div>
                </div>

                {/* Preview — centro */}
                <div className="flex-1 bg-gray-800 flex items-start justify-center overflow-auto p-4">
                    <div style={{ width: viewWidths[viewMode], transition: 'width 0.3s ease' }} className="h-full">
                        <iframe
                            ref={iframeRef}
                            sandbox="allow-scripts allow-same-origin"
                            className="w-full h-full bg-white rounded-lg shadow-2xl"
                            title="Preview"
                        />
                    </div>
                </div>

                {/* Editor de código — derecha */}
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
