import { Component, ErrorInfo, PropsWithChildren } from 'react';
import PagepolisLogo from '@/Components/PagepolisLogo';

interface State {
    hasError: boolean;
}

/*
 * Red de seguridad para toda la app: si un componente de página revienta al
 * renderizar (bug, prop inesperada, respuesta rara del backend), React
 * desmontaría el árbol entero y el cliente vería una pantalla en blanco sin
 * ninguna pista. En vez de eso, mostramos una pantalla de error con marca y
 * una salida clara. Los enlaces son <a> normales (no Inertia Link) a
 * propósito: si lo que rompió es el propio árbol de React, seguimos
 * queriendo que la navegación funcione con una recarga completa.
 */
export default class ErrorBoundary extends Component<PropsWithChildren, State> {
    state: State = { hasError: false };

    static getDerivedStateFromError(): State {
        return { hasError: true };
    }

    componentDidCatch(error: Error, info: ErrorInfo) {
        console.error('Pagepolis: error no controlado al renderizar', error, info.componentStack);
    }

    render() {
        if (!this.state.hasError) {
            return this.props.children;
        }

        return (
            <div className="min-h-screen flex flex-col items-center justify-center bg-gray-950 px-4 relative overflow-hidden">
                <div className="absolute inset-0 pointer-events-none" aria-hidden="true">
                    <div className="absolute top-1/4 left-1/2 -translate-x-1/2 w-[560px] h-[360px] bg-violet-700/15 rounded-full blur-[120px]" />
                </div>
                <div className="relative w-full sm:max-w-md flex flex-col items-center text-center">
                    <a href="/" className="flex items-center gap-2.5 mb-8">
                        <PagepolisLogo size={36} />
                        <span className="text-3xl font-black tracking-tight bg-gradient-to-r from-violet-400 via-fuchsia-400 to-cyan-400 bg-clip-text text-transparent">
                            pagepolis
                        </span>
                    </a>
                    <div className="w-full px-6 py-8 bg-gray-900/90 border border-gray-800 rounded-2xl shadow-2xl shadow-violet-950/20">
                        <h1 className="text-xl font-bold text-white mb-2">Algo ha ido mal</h1>
                        <p className="text-sm text-gray-400 mb-6">
                            Ha ocurrido un error inesperado. Prueba a recargar la página; si el problema
                            persiste, vuelve a tu panel e inténtalo de nuevo.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-3">
                            <button
                                type="button"
                                onClick={() => window.location.reload()}
                                className="flex-1 bg-violet-600 hover:bg-violet-500 text-white px-5 py-3 rounded-xl font-bold transition-colors"
                            >
                                Recargar página
                            </button>
                            <a
                                href="/dashboard"
                                className="flex-1 bg-gray-800 hover:bg-gray-700 text-white px-5 py-3 rounded-xl font-bold transition-colors"
                            >
                                Ir al panel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}
