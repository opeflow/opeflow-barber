<script setup>
import { Head, router } from '@inertiajs/vue3'
import { CalendarDays, LogOut, Scissors } from 'lucide-vue-next'

defineProps({ nome: String, agendamentos: Array, linkAgendamento: String })
</script>

<template>
  <Head title="Minha área" />
  <main class="mx-auto min-h-screen max-w-4xl bg-slate-50 px-4 py-6 sm:px-6 lg:py-10">
    <header class="flex items-center justify-between rounded-2xl bg-slate-950 p-5 text-white sm:p-7"><div><p class="text-sm text-slate-300">Opeflow Barber</p><h1 class="mt-1 text-2xl font-bold">Olá, {{ nome }}</h1><p class="mt-1 text-sm text-slate-300">Sua área de agendamentos</p></div><button class="grid h-10 w-10 place-items-center rounded-lg hover:bg-white/10" aria-label="Sair" @click="router.post('/cliente/sair')"><LogOut class="h-5 w-5" /></button></header>
    <div class="mt-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><h2 class="text-xl font-bold text-slate-950">Seus agendamentos</h2><p class="mt-1 text-sm text-slate-500">Consulte os horários vinculados à sua conta.</p></div><a :href="linkAgendamento" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700"><Scissors class="h-4 w-4" /> Agendar novo horário</a></div>
    <section v-if="agendamentos.length" class="mt-4 grid gap-3 sm:grid-cols-2"><article v-for="item in agendamentos" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><div class="flex items-start justify-between gap-2"><h3 class="font-semibold text-slate-950">{{ item.servico }}</h3><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800">{{ item.status.replaceAll('_', ' ') }}</span></div><p class="mt-3 flex items-center gap-2 text-sm text-slate-700"><CalendarDays class="h-4 w-4 text-blue-600" />{{ item.dataHora }}</p><p class="mt-2 text-sm text-slate-500">{{ item.barbearia }} · com {{ item.profissional }}</p></article></section>
    <section v-else class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center"><CalendarDays class="mx-auto h-8 w-8 text-slate-400" /><h2 class="mt-3 font-semibold text-slate-900">Nenhum agendamento encontrado</h2><p class="mt-1 text-sm text-slate-500">Use o botão acima para escolher um horário.</p></section>
    <p class="mt-5 text-xs text-slate-500">Esta área permite consulta. Cancelamento e reagendamento online ainda não estão disponíveis.</p>
  </main>
</template>
