import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    return (
        <GuestLayout>
            <Head title="Verificar email" />
            <h2 className="text-2xl font-bold text-white mb-4">Verifica tu email</h2>
            <p className="text-gray-400 text-sm mb-6">
                Hemos enviado un enlace de verificación a tu correo. Revisa tu bandeja de entrada.
            </p>

            {status === 'verification-link-sent' && (
                <div className="mb-4 p-3 bg-green-900/30 border border-green-700 text-green-400 rounded-lg text-sm">
                    Se ha enviado un nuevo enlace de verificación.
                </div>
            )}

            <div className="flex flex-col gap-3">
                <button
                    onClick={() => post('/email/verification-notification')}
                    disabled={processing}
                    className="w-full bg-violet-600 text-white py-3 rounded-lg font-semibold hover:bg-violet-500 disabled:opacity-50 transition-colors"
                >
                    {processing ? 'Enviando...' : 'Reenviar email'}
                </button>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    className="w-full text-center text-sm text-gray-500 hover:text-gray-400"
                >
                    Cerrar sesión
                </Link>
            </div>
        </GuestLayout>
    );
}
