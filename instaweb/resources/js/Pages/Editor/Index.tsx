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

interface Project {
    id: number;
    name: string;
    html: string;
    css: string;
    js: string;
    ai_history: Array<{ prompt: string; created_at: string }>;
    status: string;
}

interface Props {
    project: Project;
}

type ActiveTab = 'html' | 'css' | 'js';
type ViewMode = 'desktop' | 'tablet' | 'mobile';

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

function CodeMirrorEditor({
    value,
    onChange,
    language,
}: {
    value: string;
    onChange: (v: string) => void;
    language: ActiveTab;
}) {
    const containerRef = useRef<HTMLDivElement>(null);
    const viewRef      = useRef<EditorView | null>(null);
    const langComp     = useRef(new Compartment());
    const onChangeRef  = useRef(onChange);
    onChangeRef.current = onChange;

    // Mount editor once
    useEffect(() => {
        if (!containerRef.current) return;

        const view = new EditorView({
            state: EditorState.create({
                doc: value,
                extensions: [
                    lineNumbers(),
                    highlightActiveLine(),
                    highlightActiveLineGutter(),
                    drawSelection(),
                    foldGutter(),
                    history(),
                    bracketMatching(),
                    closeBrackets(),
                    indentOnInput(),
                    autocompletion(),
                    oneDark,
                    syntaxHighlightingFallback(),
                    langComp.current.of(langExtension[language]()),
                    keymap.of([
                        ...defaultKeymap,
                        ...historyKeymap,
                        ...completionKeymap,
                        ...closeBracketsKeymap,
                        ...foldKeymap,
                        indentWithTab,
                    ]),
                    EditorView.updateListener.of(update => {
                        if (update.docChanged) {
                            onChangeRef.current(update.state.doc.toString());
                        }
                    }),
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

    // Sync language when tab changes
    useEffect(() => {
        if (!viewRef.current) return;
        viewRef.current.dispatch({
            effects: langComp.current.reconfigure(langExtension[language]()),
        });
    }, [language]);

    // Sync content when AI overwrites it from outside
    useEffect(() => {
        const view = viewRef.current;
        if (!view) return;
        const current = view.state.doc.toString();
        if (current !== value) {
            view.dispatch({
                changes: { from: 0, to: current.length, insert: value },
            });
        }
    }, [value]);

    return <div ref={containerRef} className="flex-1 overflow-hidden h-full" />;
}

function syntaxHighlightingFallback() {
    return syntaxHighlighting(defaultHighlightStyle, { fallback: true });
}

export default function EditorIndex({ project }: Props) {
    const [name, setName]           = useState(project.name);
    const [html, setHtml]           = useState(project.html);
    const [css, setCss]             = useState(project.css);
    const [js, setJs]               = useState(project.js);
    const [prompt, setPrompt]       = useState('');
    const [aiLoading, setAiLoading] = useState(false);
    const [saving, setSaving]       = useState(false);
    const [saved, setSaved]         = useState(false);
    const [activeTab, setActiveTab] = useState<ActiveTab>('html');
    const [viewMode, setViewMode]   = useState<ViewMode>('desktop');
    const [aiError, setAiError]     = useState('');
    const iframeRef                 = useRef<HTMLIFrameElement>(null);

    const buildPreviewHtml = useCallback(() => {
        return `<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css}</style></head><body>${html}<script>${js}<\/script></body></html>`;
    }, [html, css, js]);

    useEffect(() => {
        if (iframeRef.current) {
            iframeRef.current.srcdoc = buildPreviewHtml();
        }
    }, [buildPreviewHtml]);

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

    const generateAI = async () => {
        if (!prompt.trim()) return;
        setAiLoading(true);
        setAiError('');
        try {
            const res = await axios.post('/ai/generar', {
                prompt,
                project_id: project.id,
            });
            if (res.data.success) {
                setHtml(res.data.html);
                setCss(res.data.css);
                setJs(res.data.js || '');
                setPrompt('');
            }
        } catch (err: any) {
            setAiError(err.response?.data?.error || 'Error al generar. Inténtalo de nuevo.');
        } finally {
            setAiLoading(false);
        }
    };

    const currentValue  = activeTab === 'html' ? html : activeTab === 'css' ? css : js;
    const handleChange  = (v: string) => {
        if (activeTab === 'html') setHtml(v);
        else if (activeTab === 'css') setCss(v);
        else setJs(v);
    };

    return (
        <div className="h-screen flex flex-col bg-gray-950 text-white overflow-hidden">
            <Head title={`Editor — ${name}`} />

            {/* Barra superior */}
            <div className="flex items-center justify-between px-4 py-3 border-b border-gray-800 bg-gray-900 flex-shrink-0">
                <div className="flex items-center gap-4">
                    <a href="/dashboard" className="text-gray-500 hover:text-white transition-colors text-sm">
                        ← Dashboard
                    </a>
                    <input
                        value={name}
                        onChange={e => setName(e.target.value)}
                        className="bg-transparent text-white font-semibold text-lg focus:outline-none border-b border-transparent focus:border-gray-600 px-1"
                    />
                </div>
                <div className="flex items-center gap-3">
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

            {/* Cuerpo principal */}
            <div className="flex flex-1 overflow-hidden">
                {/* Panel IA — izquierda */}
                <div className="w-72 flex-shrink-0 border-r border-gray-800 bg-gray-900 flex flex-col">
                    <div className="p-4 border-b border-gray-800">
                        <h2 className="text-sm font-bold text-white mb-3">✨ Generar con IA</h2>
                        <textarea
                            value={prompt}
                            onChange={e => setPrompt(e.target.value)}
                            placeholder="Describe los cambios que quieres... ej: 'Cambia el color principal a azul, añade una sección de precios con 3 planes'"
                            rows={6}
                            className="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-3 text-white text-sm focus:outline-none focus:border-violet-500 resize-none placeholder-gray-500"
                            onKeyDown={e => { if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) generateAI(); }}
                        />
                        {aiError && <p className="mt-2 text-xs text-red-400">{aiError}</p>}
                        <button
                            onClick={generateAI}
                            disabled={aiLoading || !prompt.trim()}
                            className="mt-3 w-full bg-gradient-to-r from-violet-600 to-violet-500 hover:opacity-90 disabled:opacity-40 text-white text-sm font-bold py-3 rounded-lg transition-opacity flex items-center justify-center gap-2"
                        >
                            {aiLoading ? (
                                <>
                                    <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                    Generando...
                                </>
                            ) : '⚡ Generar (Ctrl+Enter)'}
                        </button>
                    </div>

                    {/* Historial IA */}
                    {project.ai_history.length > 0 && (
                        <div className="flex-1 overflow-y-auto p-4">
                            <h3 className="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-3">Historial IA</h3>
                            <div className="space-y-2">
                                {[...project.ai_history].reverse().map((entry, i) => (
                                    <div key={i} className="bg-gray-800 rounded-lg p-3">
                                        <p className="text-xs text-gray-300 line-clamp-2">{entry.prompt}</p>
                                        <p className="text-xs text-gray-600 mt-1">
                                            {new Date(entry.created_at).toLocaleDateString('es-ES')}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {/* Preview — centro */}
                <div className="flex-1 bg-gray-800 flex items-start justify-center overflow-auto p-4">
                    <div
                        style={{ width: viewWidths[viewMode], transition: 'width 0.3s ease' }}
                        className="h-full"
                    >
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
                        <CodeMirrorEditor
                            value={currentValue}
                            onChange={handleChange}
                            language={activeTab}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}
