<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import LayoutPainel from '../../Componentes/LayoutPainel.vue'

defineOptions({ layout: LayoutPainel })
defineProps({ cliente: Object, proximoAgendamento: Object, agendamentos: Object, atendimentos: Object })

const estados = {
  aguardando_confirmacao: 'Aguardando confirmação', confirmado: 'Confirmado', em_atendimento: 'Em atendimento',
  atendido: 'Atendido', concluido: 'Concluído', cancelado: 'Cancelado', nao_compareceu: 'Não compareceu',
  reagendado: 'Reagendado', em_andamento: 'Em andamento',
}
</script>

<template>
  <Head :title="cliente.nome" />
  <main class="mx-auto max-w-5xl px-4 py-6 pb-24 sm:px-6 lg:px-8">
    <Link href="/clientes" class="text-sm font-semibold text-blue-700 underline">← Voltar para clientes</Link>
    <header class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <p class="text-sm font-medium text-blue-600">Ficha do cliente</p>
      <h1 class="mt-1 text-2xl font-bold text-slate-950">{{ cliente.nome }}</h1>
      <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-600">
        <a :href="`tel:${cliente.telefone}`" class="font-medium text-blue-700 underline">{{ cliente.telefone }}</a>
        <a v-if="cliente.email" :href="`mailto:${cliente.email}`" class="underline">{{ cliente.email }}</a>
        <span>{{ cliente.aceitaLembretes ? 'Consentiu com lembretes' : 'Sem consentimento para lembretes' }}</span>
      </div>
      <p v-if="cliente.observacoes" class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">{{ cliente.observacoes }}</p>
      <div class="mt-4 flex flex-wrap gap-3">
        <Link :href="`/clientes/${cliente.id}/beneficios`" class="inline-flex min-h-10 items-center rounded-lg border border-blue-200 px-3 text-sm font-semibold text-blue-700">Histórico de benefícios</Link>
        <Link href="/agenda" class="inline-flex min-h-10 items-center rounded-lg bg-blue-600 px-3 text-sm font-semibold text-white">Abrir agenda</Link>
      </div>
    </header>

    <section class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-5">
      <h2 class="font-semibold text-blue-950">Próximo agendamento</h2>
      <div v-if="proximoAgendamento" class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <div><p class="font-semibold text-slate-950">{{ proximoAgendamento.servico }}</p><p class="mt-1 text-sm text-slate-600">{{ proximoAgendamento.inicioEm }} · {{ proximoAgendamento.profissional }}</p></div>
        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-800">{{ estados[proximoAgendamento.status] || proximoAgendamento.status }}</span>
      </div>
      <p v-else class="mt-2 text-sm text-slate-600">Nenhum horário futuro confirmado.</p>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
      <section>
        <h2 class="text-lg font-bold text-slate-950">Histórico de agendamentos</h2>
        <div v-if="agendamentos.data.length" class="mt-3 space-y-3">
          <article v-for="item in agendamentos.data" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ item.servico }}</p><p class="mt-1 text-sm text-slate-500">{{ item.inicioEm }} · {{ item.profissional }}</p></div><span class="text-right text-xs text-slate-600">{{ estados[item.status] || item.status }}</span></div></article>
        </div>
        <p v-else class="mt-3 rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500">Ainda não há agendamentos no histórico.</p>
        <nav v-if="agendamentos.links.length > 3" class="mt-3 flex flex-wrap gap-2" aria-label="Paginação de agendamentos"><template v-for="link in agendamentos.links" :key="link.label"><button v-if="link.url" :disabled="link.active" class="min-h-9 rounded-lg border px-3 text-sm" :class="link.active ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-300 bg-white text-slate-700'" @click="router.visit(link.url, { preserveScroll: true })" v-html="link.label" /><span v-else class="px-3 py-2 text-sm text-slate-400" v-html="link.label" /></template></nav>
      </section>
      <section>
        <h2 class="text-lg font-bold text-slate-950">Atendimentos presenciais</h2>
        <div v-if="atendimentos.data.length" class="mt-3 space-y-3">
          <article v-for="item in atendimentos.data" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ item.servico }}</p><p class="mt-1 text-sm text-slate-500">{{ item.iniciadoEm }} · {{ item.profissional }}</p></div><span class="text-right text-xs text-slate-600">{{ estados[item.status] || item.status }}</span></div></article>
        </div>
        <p v-else class="mt-3 rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500">Ainda não há atendimentos presenciais registrados.</p>
        <nav v-if="atendimentos.links.length > 3" class="mt-3 flex flex-wrap gap-2" aria-label="Paginação de atendimentos"><template v-for="link in atendimentos.links" :key="link.label"><button v-if="link.url" :disabled="link.active" class="min-h-9 rounded-lg border px-3 text-sm" :class="link.active ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-300 bg-white text-slate-700'" @click="router.visit(link.url, { preserveScroll: true })" v-html="link.label" /><span v-else class="px-3 py-2 text-sm text-slate-400" v-html="link.label" /></template></nav>
      </section>
    </div>
  </main>
</template>
