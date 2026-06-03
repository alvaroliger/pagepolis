import { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-950 pt-6 sm:pt-0">
            <div className="mb-8">
                <Link href="/">
                    <span className="text-3xl font-black bg-gradient-to-r from-violet-500 to-cyan-400 bg-clip-text text-transparent">
                        Pagepolis
                    </span>
                </Link>
            </div>
            <div className="w-full sm:max-w-md px-6 py-8 bg-gray-900 border border-gray-800 rounded-2xl shadow-2xl">
                {children}
            </div>
        </div>
    );
}
