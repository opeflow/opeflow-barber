<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import LayoutPainel from '../../Componentes/LayoutPainel.vue'

defineOptions({ layout: LayoutPainel })

const props = defineProps({ inicio: String, fim: String, fusoHorario: String, resumo: Object, porServico: Array, porProfissional: Array, urlExportacao: String })
const inicioSelecionado = ref(props.inicio)
const fimSelecionado = ref(props.fim)
const maiorServico = computed(() => Math.max(1, ...props.porServico.map((item) => Number(item.total))))
const maiorProfissional = computed(() => Math.max(1, ...props.porProfissional.map((item) => Number(item.total))))
function filtrar() { router.get('/relatorios', { inicio: inicioSelecionado.value, fim: fimSelecionado.value }, { preserveState: true, replace: true }) }
function moeda(valor) { return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }
</script>

<template>
  <Head title="Relatórios" />
  <main class="mx-auto max-w-7xl px-4 py-6 pb-24 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
      <div><p class="text-sm font-semibold text-blue-600">Desempenho da barbearia</p><h1 class="mt-1 text-2xl font-bold text-slate-950">Relatórios</h1><p class="mt-1 text-sm text-slate-500">Valores calculados no fuso horário {{ fusoHorario }}.</p></div>
      <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto_auto]"><label class="text-sm font-medium text-slate-700">De<input v-model="inicioSelecionado" type="date" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"></label><label class="text-sm font-medium text-slate-700">Até<input v-model="fimSelecionado" type="date" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"></label><button class="min-h-11 self-end rounded-lg bg-blue-600 px-4 font-semibold text-white" @click="filtrar">Aplicar</button><a :href="urlExportacao" class="flex min-h-11 items-center justify-center self-end rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700">Exportar CSV</a></div>
    </header>
    <section class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <article class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-500">Recebido</p><p class="mt-1 text-2xl font-bold text-slate-950">{{ moeda(resumo.totalRecebido) }}</p></article>
      <article class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-500">Despesas</p><p class="mt-1 text-2xl font-bold text-red-700">{{ moeda(resumo.totalDespesas) }}</p></article>
      <article class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-500">Saldo operacional</p><p class="mt-1 text-2xl font-bold text-slate-950">{{ moeda(resumo.saldo) }}</p></article>
      <article class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-500">Ticket médio por atendimento pago</p><p class="mt-1 text-2xl font-bold text-slate-950">{{ moeda(resumo.ticketMedio) }}</p><p class="mt-1 text-xs text-slate-500">{{ resumo.atendimentosComPagamento }} atendimento(s)</p></article>
    </section>
    <section class="mt-6 grid gap-4 sm:grid-cols-2">
      <article class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-semibold text-slate-950">Receita por serviço</h2><div v-if="porServico.length" class="mt-4 space-y-4"><div v-for="item in porServico" :key="item.nome"><div class="flex justify-between gap-3 text-sm"><span>{{ item.nome }}</span><span class="font-medium">{{ moeda(item.total) }}</span></div><div class="mt-1 h-2 rounded bg-slate-100"><div class="h-2 rounded bg-blue-600" :style="{ width: `${Number(item.total) / maiorServico * 100}%` }"></div></div></div></div><p v-else class="mt-3 text-sm text-slate-500">Sem recebimentos no período.</p></article>
      <article class="rounded-xl border border-slate-200 bg-white p-5"><h2 class="font-semibold text-slate-950">Receita por profissional</h2><div v-if="porProfissional.length" class="mt-4 space-y-4"><div v-for="item in porProfissional" :key="item.nome"><div class="flex justify-between gap-3 text-sm"><span>{{ item.nome }}</span><span class="font-medium">{{ moeda(item.total) }}</span></div><div class="mt-1 h-2 rounded bg-slate-100"><div class="h-2 rounded bg-slate-800" :style="{ width: `${Number(item.total) / maiorProfissional * 100}%` }"></div></div></div></div><p v-else class="mt-3 text-sm text-slate-500">Sem recebimentos no período.</p></article>
    </section>
    <section class="mt-4 grid gap-4 sm:grid-cols-2"><article class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Agendamentos cancelados</p><p class="mt-1 text-2xl font-bold text-slate-950">{{ resumo.cancelamentos }}</p></article><article class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Não comparecimentos</p><p class="mt-1 text-2xl font-bold text-slate-950">{{ resumo.faltas }}</p></article></section>
  </main>
</template>
