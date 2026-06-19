<template>
    <div class="min-h-screen bg-gray-50 text-gray-900">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
                <NuxtLink to="/invoices" class="text-lg font-semibold tracking-tight">
                    Invoices
                </NuxtLink>
                <span class="text-sm text-gray-500">Accounting</span>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            <NuxtPage />
        </main>
    </div>
</template>
