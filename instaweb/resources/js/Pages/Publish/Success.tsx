import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function PublishSuccess() {
    return (
        <AuthenticatedLayout>
            <Head title="¡Pago exitoso!" />
            <div className="max-w-2xl mx-auto px-4 py-24 text-center">
                <div className="text-7xl mb-6">🎉</div>
                <h1 className="text-4xl font-black text-white mb-4">¡Todo listo!</h1>
                <p className="text-gray-400 text-lg mb-8">
                    Tu pago se ha procesado correctamente. Estamos comprando tu dominio y desplegando tu sitio. Esto puede tardar unos minutos.
                </p>
                <Link href="/dashboard" className="inline-block bg-violet-600 hover:bg-violet-500 text-white px-8 py-4 rounded-xl font-bold transition-colors">
                    Ver mis proyectos →
                </Link>
            </div>
        </AuthenticatedLayout>
    );
}
