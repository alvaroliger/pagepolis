import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Login({ canResetPassword, status }: { canResetPassword: boolean; status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <GuestLayout>
            <Head title="Iniciar sesión" />
            <h2 className="text-2xl font-bold text-white mb-6">Iniciar sesión</h2>

            {status && <div className="mb-4 text-sm text-green-400">{status}</div>}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label htmlFor="email" className="block text-sm text-gray-400 mb-1">Email</label>
                    <input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={e => setData('email', e.target.value)}
                        autoComplete="email"
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500"
                        required
                    />
                    {errors.email && <p id="email-error" className="mt-1 text-sm text-red-400">{errors.email}</p>}
                </div>

                <div>
                    <label htmlFor="password" className="block text-sm text-gray-400 mb-1">Contraseña</label>
                    <input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={e => setData('password', e.target.value)}
                        autoComplete="current-password"
                        aria-invalid={!!errors.password}
                        aria-describedby={errors.password ? 'password-error' : undefined}
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500"
                        required
                    />
                    {errors.password && <p id="password-error" className="mt-1 text-sm text-red-400">{errors.password}</p>}
                </div>

                <div className="flex items-center justify-between">
                    <label className="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            onChange={e => setData('remember', e.target.checked)}
                            className="rounded border-gray-600"
                        />
                        Recordarme
                    </label>
                    {canResetPassword && (
                        <Link href="/forgot-password" className="text-sm text-violet-400 hover:text-violet-300">
                            ¿Olvidaste tu contraseña?
                        </Link>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full bg-gradient-to-r from-violet-600 to-violet-500 text-white py-3 rounded-lg font-semibold hover:opacity-90 disabled:opacity-50 transition-opacity"
                >
                    {processing ? 'Entrando...' : 'Iniciar sesión'}
                </button>
            </form>

            <p className="mt-6 text-center text-sm text-gray-500">
                ¿No tienes cuenta?{' '}
                <Link href="/register" className="text-violet-400 hover:text-violet-300 font-medium">
                    Regístrate gratis
                </Link>
            </p>
        </GuestLayout>
    );
}
