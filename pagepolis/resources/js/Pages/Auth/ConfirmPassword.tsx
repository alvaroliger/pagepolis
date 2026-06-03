import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors } = useForm({ password: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/confirm-password');
    };

    return (
        <GuestLayout>
            <Head title="Confirmar contraseña" />
            <h2 className="text-2xl font-bold text-white mb-2">Confirma tu contraseña</h2>
            <p className="text-gray-400 text-sm mb-6">
                Por seguridad, confirma tu contraseña para continuar.
            </p>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label className="block text-sm text-gray-400 mb-1">Contraseña</label>
                    <input
                        type="password"
                        value={data.password}
                        onChange={e => setData('password', e.target.value)}
                        className="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-violet-500"
                        required
                    />
                    {errors.password && <p className="mt-1 text-sm text-red-400">{errors.password}</p>}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full bg-violet-600 text-white py-3 rounded-lg font-semibold hover:bg-violet-500 disabled:opacity-50 transition-colors"
                >
                    {processing ? 'Confirmando...' : 'Confirmar'}
                </button>
            </form>
        </GuestLayout>
    );
}
