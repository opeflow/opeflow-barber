<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import LayoutPainel from '../../Componentes/LayoutPainel.vue'

defineOptions({ layout: LayoutPainel })

const props = defineProps({ data: String, agendamentos: Array, clientes: Array, profissionais: Array, servicos: Array, fusoHorario: String, podeGerenciarAgenda: Boolean })
const mostrarFormulario = ref(false)
const formulario = useForm({ cliente_id: '', profissional_id: '', servico_id: '', data: props.data, hora: '09:00', observacoes: '' })
const mensagemErro = computed(() => formulario.errors.profissional_id || formulario.errors.data || formulario.errors.hora || null)
const servicosDoProfissional = computed(() => {
    const selecionado = props.profissionais.find((item) => String(item.id) === String(formulario.profissional_id))
    return selecionado ? props.servicos.filter((item) => selecionado.servicoIds.includes(item.id)) : []
})

function trocarData(evento) { router.get('/agenda', { data: evento.target.value }, { preserveState: true, replace: true }) }
function criarAgendamento() { formulario.post('/agenda', { onSuccess: () => { mostrarFormulario.value = false; formulario.reset('observacoes') } }) }
function cancelarAgendamento(id) {
    if (window.confirm('Deseja cancelar este agendamento?')) router.delete(`/agenda/${id}`, { preserveScroll: true })
}
function marcarAtendido(id) { router.post(`/agenda/${id}/atendido`, {}, { preserveScroll: true }) }
function marcarFalta(id) { if (window.confirm('Marcar este horário como não comparecimento?')) router.post(`/agenda/${id}/nao-compareceu`, {}, { preserveScroll: true }) }
function formatarData(data) { return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'full', timeZone: props.fusoHorario }).format(new Date(`${data}T12:00:00`)) }
</script>

<template>
  <Head title="Agenda" />
  <main class="mx-auto max-w-6xl px-4 py-6 pb-24 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><p class="text-sm font-medium text-blue-600">Atendimentos</p><h1 class="mt-1 text-2xl font-bold text-slate-950">Agenda</h1><p class="mt-1 text-sm text-slate-500">{{ formatarData(data) }}</p></div>
      <div class="flex gap-3"><input :value="data" type="date" class="min-h-11 rounded-lg border-slate-300 px-3" @change="trocarData"><button v-if="podeGerenciarAgenda" class="min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white hover:bg-blue-700" @click="mostrarFormulario = !mostrarFormulario">{{ mostrarFormulario ? 'Fechar' : 'Novo agendamento' }}</button></div>
    </header>

    <section v-if="mostrarFormulario" class="mt-6 rounded-xl border border-blue-100 bg-white p-5 shadow-sm">
      <h2 class="font-semibold text-slate-950">Novo agendamento</h2>
      <p v-if="!clientes.length" class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Cadastre pelo menos um cliente antes de agendar. <Link href="/clientes" class="font-semibold underline">Abrir clientes</Link></p>
      <form class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="criarAgendamento">
        <label class="text-sm font-medium text-slate-700">Cliente<select v-model="formulario.cliente_id" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><option value="">Selecione</option><option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">{{ cliente.nome }} · {{ cliente.telefone }}</option></select><span v-if="formulario.errors.cliente_id" class="mt-1 block text-red-600">{{ formulario.errors.cliente_id }}</span></label>
        <label class="text-sm font-medium text-slate-700">Profissional<select v-model="formulario.profissional_id" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><option value="">Selecione</option><option v-for="item in profissionais" :key="item.id" :value="item.id">{{ item.nome }}</option></select><span v-if="formulario.errors.profissional_id" class="mt-1 block text-red-600">{{ formulario.errors.profissional_id }}</span></label>
        <label class="text-sm font-medium text-slate-700">Serviço<select v-model="formulario.servico_id" required :disabled="!formulario.profissional_id" class="mt-1 min-h-11 w-full rounded-lg border-slate-300 disabled:bg-slate-100"><option value="">{{ formulario.profissional_id ? 'Selecione' : 'Escolha um profissional primeiro' }}</option><option v-for="item in servicosDoProfissional" :key="item.id" :value="item.id">{{ item.nome }} · {{ item.duracao_em_minutos }} min</option></select><span v-if="formulario.errors.servico_id" class="mt-1 block text-red-600">{{ formulario.errors.servico_id }}</span></label>
        <label class="text-sm font-medium text-slate-700">Data<input v-model="formulario.data" type="date" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><span v-if="formulario.errors.data" class="mt-1 block text-red-600">{{ formulario.errors.data }}</span></label>
        <label class="text-sm font-medium text-slate-700">Horário<input v-model="formulario.hora" type="time" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><span v-if="formulario.errors.hora" class="mt-1 block text-red-600">{{ formulario.errors.hora }}</span></label>
        <label class="text-sm font-medium text-slate-700 sm:col-span-2 lg:col-span-1">Observações<input v-model="formulario.observacoes" maxlength="1000" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"></label>
        <p v-if="mensagemErro && !formulario.errors.profissional_id && !formulario.errors.data && !formulario.errors.hora" class="text-sm text-red-600 sm:col-span-2 lg:col-span-3">{{ mensagemErro }}</p>
        <div class="sm:col-span-2 lg:col-span-3"><button :disabled="formulario.processing || !clientes.length" class="min-h-11 rounded-lg bg-blue-600 px-5 font-semibold text-white disabled:opacity-50">{{ formulario.processing ? 'Salvando…' : 'Confirmar agendamento' }}</button></div>
      </form>
    </section>

    <section class="mt-6">
      <div v-if="!agendamentos.length" class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center"><h2 class="font-semibold text-slate-900">Nenhum agendamento neste dia</h2><p class="mt-1 text-sm text-slate-500">{{ podeGerenciarAgenda ? 'Crie um horário para começar a organizar os atendimentos.' : 'Você não tem atendimentos agendados para esta data.' }}</p><button v-if="podeGerenciarAgenda" class="mt-4 min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white" @click="mostrarFormulario = true">Adicionar agendamento</button></div>
      <ol v-else class="space-y-3">
        <li v-for="item in agendamentos" :key="item.id" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[90px_1fr_auto] sm:items-center">
          <div class="text-lg font-bold tabular-nums text-slate-950">{{ item.horaInicio }}<span class="block text-xs font-normal text-slate-500">até {{ item.horaFim }}</span></div>
          <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h2 class="font-semibold text-slate-950">{{ item.cliente }}</h2><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">{{ item.status.replaceAll('_', ' ') }}</span></div><p class="mt-1 text-sm text-slate-600">{{ item.servico }} · {{ item.profissional }}</p><p class="text-xs text-slate-500">{{ item.telefone }} · {{ new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(item.preco) }}</p></div>
          <div class="flex flex-wrap gap-2"><template v-if="item.podeEncerrar && item.status === 'confirmado'"><button class="min-h-10 rounded-lg bg-green-700 px-3 text-sm font-semibold text-white" @click="marcarAtendido(item.id)">Atendido</button><button class="min-h-10 rounded-lg px-3 text-sm font-semibold text-amber-800 hover:bg-amber-50" @click="marcarFalta(item.id)">Não compareceu</button></template><button v-if="podeGerenciarAgenda && ['confirmado', 'aguardando_confirmacao'].includes(item.status)" class="min-h-10 rounded-lg px-3 text-sm font-semibold text-red-600 hover:bg-red-50" @click="cancelarAgendamento(item.id)">Cancelar</button></div>
        </li>
      </ol>
    </section>
  </main>
</template>
