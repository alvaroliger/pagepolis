// Pagepolis — by Álvaro Liger (https://www.linkedin.com/in/alvaroliger/)
import '../css/app.css';
import './bootstrap';
import './i18n';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import toast, { Toaster } from 'react-hot-toast';

const appName = import.meta.env.VITE_APP_NAME || 'Pagepolis';

// Los controladores ya adjuntan mensajes flash (`->with('error', ...)`,
// `->with('message', ...)`) a la sesión y `HandleInertiaRequests` los comparte
// como prop `flash`, pero hasta ahora nada los mostraba: el cliente nunca veía
// avisos como "el plan gratuito permite hasta X proyectos". Un único listener
// global los convierte en el mismo toast que ya se usa en el resto de la app.
router.on('navigate', (event) => {
    const flash = (event.detail.page.props as { flash?: { message?: string | null; error?: string | null } }).flash;
    if (flash?.error) toast.error(flash.error);
    if (flash?.message) toast.success(flash.message);
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <ErrorBoundary>
                    <App {...props} />
                </ErrorBoundary>
                <Toaster
                    position="bottom-center"
                    toastOptions={{
                        style: { background: '#111827', color: '#fff', border: '1px solid #374151' },
                        success: { iconTheme: { primary: '#10b981', secondary: '#fff' } },
                        error: { iconTheme: { primary: '#ef4444', secondary: '#fff' } },
                    }}
                />
            </>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});
