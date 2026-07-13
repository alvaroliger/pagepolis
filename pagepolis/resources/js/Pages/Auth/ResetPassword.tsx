import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/reset-password');
    };

    return (
        <GuestLayout>
            <Head title="Nueva contraseña" />
            <h2 className="text-2xl font-bold text-white mb-6">Nueva contraseña</h2>

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
                    />
                    {errors.email && <p id="email-error" className="mt-1 text-sm text-red-400">{errors.email}</p>}
                </div>

                <div>
                    <label htmlFor="password" className="block text-sm text-gray-400 mb-1">Nueva contraseña</label>
                    <input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={e => setData('password', e.target.value)}
                        autoComplete="new-password"
                        aria-invalid={!!errors.password}
                        aria-describedby={errors.password ? 'password-error' : undefined}
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500"
                    />
                    {errors.password && <p id="password-error" className="mt-1 text-sm text-red-400">{errors.password}</p>}
                </div>

                <div>
                    <label htmlFor="password_confirmation" className="block text-sm text-gray-400 mb-1">Confirmar contraseña</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={e => setData('password_confirmation', e.target.value)}
                        autoComplete="new-password"
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500"
                    />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full bg-violet-600 text-white py-3 rounded-lg font-semibold hover:bg-violet-500 disabled:opacity-50 transition-colors"
                >
                    {processing ? 'Guardando...' : 'Restablecer contraseña'}
                </button>
            </form>
        </GuestLayout>
    );
}
