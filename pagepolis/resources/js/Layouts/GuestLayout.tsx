import { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';
import PagepolisLogo from '@/Components/PagepolisLogo';
import { FadeIn } from '@/Components/Motion';
import FlashMessages from '@/Components/FlashMessages';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-950 pt-6 sm:pt-0 px-4 relative overflow-hidden">
            {/* Glow de marca, sutil, coherente con la landing */}
            <div className="absolute inset-0 pointer-events-none" aria-hidden="true">
                <div className="absolute top-1/4 left-1/2 -translate-x-1/2 w-[560px] h-[360px] bg-violet-700/15 rounded-full blur-[120px]" />
                <div className="absolute bottom-1/4 left-1/3 w-[300px] h-[240px] bg-fuchsia-700/10 rounded-full blur-[100px]" />
            </div>

            <FadeIn className="relative w-full flex flex-col items-center">
                <div className="mb-8">
                    <Link href="/" className="flex items-center gap-2.5">
                        <PagepolisLogo size={36} />
                        <span className="text-3xl font-black tracking-tight bg-gradient-to-r from-violet-400 via-fuchsia-400 to-cyan-400 bg-clip-text text-transparent">
                            pagepolis
                        </span>
                    </Link>
                </div>
                <div className="w-full sm:max-w-md px-6 py-8 bg-gray-900/90 border border-gray-800 rounded-2xl shadow-2xl shadow-violet-950/20 backdrop-blur-sm">
                    {children}
                </div>
            </FadeIn>

            <FlashMessages />
        </div>
    );
}
