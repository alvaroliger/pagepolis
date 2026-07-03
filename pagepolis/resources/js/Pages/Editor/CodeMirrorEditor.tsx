import { useRef, useEffect } from 'react';

import { EditorView, keymap, lineNumbers, highlightActiveLine, highlightActiveLineGutter, drawSelection } from '@codemirror/view';
import { EditorState, Compartment } from '@codemirror/state';
import { defaultKeymap, history, historyKeymap, indentWithTab } from '@codemirror/commands';
import { bracketMatching, indentOnInput, syntaxHighlighting, defaultHighlightStyle, foldGutter, foldKeymap } from '@codemirror/language';
import { autocompletion, completionKeymap, closeBrackets, closeBracketsKeymap } from '@codemirror/autocomplete';
import { oneDark } from '@codemirror/theme-one-dark';
import { html as htmlLang } from '@codemirror/lang-html';
import { css as cssLang } from '@codemirror/lang-css';
import { javascript as jsLang } from '@codemirror/lang-javascript';

export type ActiveTab = 'html' | 'css' | 'js';

const langExtension: Record<ActiveTab, () => any> = {
    html: htmlLang,
    css:  cssLang,
    js:   () => jsLang({ jsx: false }),
};

export default function CodeMirrorEditor({ value, onChange, language }: { value: string; onChange: (v: string) => void; language: ActiveTab }) {
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
