import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <GuestLayout>
            <Head title="Recuperar contraseña" />
            <h2 className="text-2xl font-bold text-white mb-2">Recuperar contraseña</h2>
            <p className="text-gray-400 text-sm mb-6">
                Te enviaremos un enlace para restablecer tu contraseña.
            </p>

            {status && <div className="mb-4 p-3 bg-green-900/30 border border-green-700 text-green-400 rounded-lg text-sm">{status}</div>}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label className="block text-sm text-gray-400 mb-1">Email</label>
                    <input
                        type="email"
                        value={data.email}
                        onChange={e => setData('email', e.target.value)}
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500"
                        required
                    />
                    {errors.email && <p className="mt-1 text-sm text-red-400">{errors.email}</p>}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full bg-violet-600 text-white py-3 rounded-lg font-semibold hover:bg-violet-500 disabled:opacity-50 transition-colors"
                >
                    {processing ? 'Enviando...' : 'Enviar enlace'}
                </button>
            </form>

            <p className="mt-6 text-center text-sm text-gray-500">
                <Link href="/login" className="text-violet-400 hover:text-violet-300">
                    Volver al login
                </Link>
            </p>
        </GuestLayout>
    );
}
