<script setup>
import { Head, router } from '@inertiajs/vue3'
import { LockKeyhole, LogOut, ShieldCheck } from 'lucide-vue-next'

defineProps({ nome: String, ambiente: String, escopo: String })
</script>

<template>
  <Head title="Painel administrativo global" />
  <main class="min-h-screen bg-slate-50 px-4 py-6 sm:px-8 sm:py-10">
    <div class="mx-auto max-w-5xl"><header class="flex items-center justify-between rounded-2xl bg-slate-950 p-5 text-white sm:p-7"><div><p class="text-sm text-slate-300">Opeflow · Administração global</p><h1 class="mt-1 text-2xl font-bold">Olá, {{ nome }}</h1><p class="mt-1 text-sm text-slate-300">Ambiente: {{ ambiente }}</p></div><button class="grid h-10 w-10 place-items-center rounded-lg hover:bg-white/10" aria-label="Sair" @click="router.post('/admin/sair')"><LogOut class="h-5 w-5" /></button></header>
      <section class="mt-5 rounded-xl border border-blue-100 bg-white p-5 shadow-sm sm:p-7"><div class="flex items-start gap-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700"><ShieldCheck class="h-6 w-6" /></span><div><h2 class="text-lg font-bold text-slate-950">Acesso global separado</h2><p class="mt-1 text-sm leading-relaxed text-slate-600">{{ escopo }}</p></div></div></section>
      <section class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950"><div class="flex items-start gap-3"><LockKeyhole class="mt-0.5 h-5 w-5 shrink-0" /><div><h2 class="font-semibold">Painel inicial</h2><p class="mt-1">Monitoramento de logs, integrações e gestão de segredos ainda não estão disponíveis. Este acesso demonstra a autenticação global e não expõe comandos, banco ou dados de barbearias.</p></div></div></section>
    </div>
  </main>
</template>
