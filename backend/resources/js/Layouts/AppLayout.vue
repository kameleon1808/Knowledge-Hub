<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <header class="border-b border-slate-800/70">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-400 text-slate-950 font-semibold">
                        KH
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Knowledge Hub</p>
                        <p class="text-lg font-semibold">za Timove</p>
                    </div>
                </div>
                <span class="text-sm text-slate-400">Phase A - Foundation</span>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-12">
            <slot />
        </main>

        <footer class="border-t border-slate-800/70">
            <div class="mx-auto max-w-6xl px-6 py-6 text-sm text-slate-500">
                Built with Laravel, Inertia, Vue 3, and Tailwind.
            </div>
        </footer>
    </div>
</template>
