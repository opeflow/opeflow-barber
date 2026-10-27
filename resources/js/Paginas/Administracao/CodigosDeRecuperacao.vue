<script setup>
import { Head, router } from '@inertiajs/vue3'

defineProps({ codigos: Array })
async function copiar(codigos) {
  await navigator.clipboard.writeText(codigos.join('\n'))
}
</script>

<template>
  <Head title="Códigos de recuperação" />
  <main class="mx-auto min-h-screen max-w-2xl bg-slate-50 px-4 py-8 sm:px-6">
    <section class="rounded-2xl border border-amber-300 bg-white p-6 shadow-sm sm:p-8"><p class="text-sm font-semibold text-amber-700">Opeflow · Códigos de uso único</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Guarde estes códigos agora</h1><p class="mt-3 text-sm leading-relaxed text-slate-600">Cada código substitui uma verificação do autenticador uma única vez. Esta é a única exibição; guarde-os em um gerenciador seguro e não os compartilhe.</p>
      <ul class="mt-5 grid grid-cols-2 gap-2 rounded-xl bg-slate-950 p-4 font-mono text-sm text-white sm:grid-cols-3"><li v-for="codigo in codigos" :key="codigo" class="rounded bg-white/10 px-3 py-2 text-center">{{ codigo }}</li></ul>
      <button type="button" class="mt-4 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-4 font-semibold text-slate-800 hover:bg-slate-50" @click="copiar(codigos)">Copiar códigos</button>
      <button type="button" class="mt-3 min-h-11 w-full rounded-lg bg-blue-600 px-4 font-semibold text-white hover:bg-blue-700" @click="router.visit('/admin')">Ir ao painel</button>
    </section>
  </main>
</template>
