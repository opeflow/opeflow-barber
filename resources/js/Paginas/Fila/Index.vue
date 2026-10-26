<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import LayoutPainel from '../../Componentes/LayoutPainel.vue'

defineOptions({ layout: LayoutPainel })

const props = defineProps({ itens: Array, clientes: Array, servicos: Array, profissionais: Array, dataOperacional: String, fusoHorario: String, modoProfissional: Boolean, podeAdministrarFila: Boolean, podeRegistrarPagamento: Boolean })
const mostrarFormulario = ref(false)
const formulario = useForm({ cliente_id: '', servico_id: '', profissional_preferido_id: '', prioridade: 0, observacoes: '' })
const formulariosPagamento = ref({})
const aguardando = computed(() => props.itens.filter((item) => item.status === 'aguardando').length)

function adicionar() { formulario.post('/fila', { onSuccess: () => { formulario.reset(); mostrarFormulario.value = false } }) }
function chamarProximo() { router.post('/fila/chamar-proximo', {}, { preserveScroll: true }) }
function iniciar(id) { router.post(`/fila/${id}/iniciar`, {}, { preserveScroll: true }) }
function concluir(id) { router.post(`/fila/${id}/concluir`, {}, { preserveScroll: true }) }
function desistir(id) {
    if (window.confirm('Remover este cliente da fila?')) router.post(`/fila/${id}/desistir`, {}, { preserveScroll: true })
}
function abrirPagamento(item) {
    formulariosPagamento.value[item.id] = useForm({ valor: item.saldo, forma_pagamento: 'pix', chave_idempotencia: crypto.randomUUID() })
}
function registrarPagamento(item) {
    const form = formulariosPagamento.value[item.id]
    form.post(`/atendimentos/${item.atendimentoId}/pagamentos`, { preserveScroll: true, onSuccess: () => { delete formulariosPagamento.value[item.id] } })
}
function hora(data) { return new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit', timeZone: props.fusoHorario }).format(new Date(data)) }
function nomeDoStatus(status) { return ({ aguardando: 'Aguardando', chamado: 'Chamado', em_atendimento: 'Em atendimento' })[status] ?? status }
</script>

<template>
  <Head title="Fila presencial" />
  <main class="mx-auto max-w-6xl px-4 py-6 pb-24 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><p class="text-sm font-medium text-blue-600">Atendimento no local</p><h1 class="mt-1 text-2xl font-bold text-slate-950">Fila presencial</h1><p class="mt-1 text-sm text-slate-500">{{ dataOperacional }} · {{ itens.length }} aguardando ou em atendimento</p></div>
      <div v-if="podeAdministrarFila" class="flex flex-wrap gap-2"><button :disabled="aguardando === 0" class="min-h-11 rounded-lg border border-blue-600 bg-white px-4 font-semibold text-blue-700 disabled:opacity-50" @click="chamarProximo">Chamar próximo</button><button class="min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white hover:bg-blue-700" @click="mostrarFormulario = !mostrarFormulario">{{ mostrarFormulario ? 'Fechar' : 'Adicionar cliente' }}</button></div>
    </header>

    <form v-if="podeAdministrarFila && mostrarFormulario" class="mt-6 rounded-xl border border-blue-100 bg-white p-5 shadow-sm" @submit.prevent="adicionar">
      <h2 class="font-semibold text-slate-950">Adicionar à fila</h2>
      <p v-if="!clientes.length" class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Cadastre um cliente antes de adicioná-lo. <Link href="/clientes" class="font-semibold underline">Abrir clientes</Link></p>
      <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <label class="text-sm font-medium text-slate-700">Cliente<select v-model="formulario.cliente_id" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><option value="">Selecione</option><option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">{{ cliente.nome }} · {{ cliente.telefone }}</option></select></label>
        <label class="text-sm font-medium text-slate-700">Serviço<select v-model="formulario.servico_id" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><option value="">Selecione</option><option v-for="servico in servicos" :key="servico.id" :value="servico.id">{{ servico.nome }} · {{ servico.duracao_em_minutos }} min</option></select></label>
        <label class="text-sm font-medium text-slate-700">Preferência de profissional<select v-model="formulario.profissional_preferido_id" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><option value="">Qualquer disponível</option><option v-for="profissional in profissionais" :key="profissional.id" :value="profissional.id">{{ profissional.nome }}</option></select></label>
        <label class="text-sm font-medium text-slate-700">Prioridade<select v-model.number="formulario.prioridade" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><option :value="0">Normal</option><option :value="1">Prioritária</option></select></label>
        <label class="text-sm font-medium text-slate-700 sm:col-span-2 lg:col-span-4">Observações<input v-model="formulario.observacoes" maxlength="1000" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"></label>
      </div>
      <p v-if="formulario.errors.cliente_id || formulario.errors.servico_id || formulario.errors.profissional_preferido_id" class="mt-2 text-sm text-red-600">{{ formulario.errors.cliente_id || formulario.errors.servico_id || formulario.errors.profissional_preferido_id }}</p>
      <button :disabled="formulario.processing || !clientes.length" class="mt-4 min-h-11 rounded-lg bg-blue-600 px-5 font-semibold text-white disabled:opacity-50">{{ formulario.processing ? 'Adicionando…' : 'Entrar na fila' }}</button>
    </form>

    <section v-if="itens.length" class="mt-6 space-y-3">
      <article v-for="item in itens" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex min-w-0 gap-4">
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-slate-100 text-lg font-bold text-slate-700">{{ item.posicao }}</div>
            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h2 class="font-semibold text-slate-950">{{ item.cliente }}</h2><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="item.status === 'em_atendimento' ? 'bg-green-100 text-green-800' : item.status === 'chamado' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700'">{{ nomeDoStatus(item.status) }}</span><span v-if="item.prioridade" class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Prioridade</span></div><p class="mt-1 text-sm text-slate-600">{{ item.servico }} · {{ item.duracao }} min · chegou às {{ hora(item.chegouEm) }}</p><p class="text-xs text-slate-500">{{ item.telefone }}<template v-if="item.profissional"> · Com {{ item.profissional }}</template><template v-else-if="item.profissionalPreferido"> · Prefere {{ item.profissionalPreferido }}</template></p><p v-if="item.previsao && item.status === 'aguardando'" class="mt-1 text-sm font-medium text-blue-700">Previsão aproximada: {{ hora(item.previsao) }}</p><p v-else-if="item.status === 'aguardando'" class="mt-1 text-sm text-slate-500">Previsão indisponível até vincular um profissional ao serviço.</p></div>
          </div>
          <div class="flex flex-wrap gap-2 sm:justify-end">
            <button v-if="item.status === 'chamado'" class="min-h-10 rounded-lg bg-blue-600 px-3 text-sm font-semibold text-white" @click="iniciar(item.id)">Iniciar atendimento</button>
            <button v-if="podeRegistrarPagamento && item.status === 'em_atendimento' && !formulariosPagamento[item.id]" class="min-h-10 rounded-lg border border-blue-600 px-3 text-sm font-semibold text-blue-700" @click="abrirPagamento(item)">Registrar pagamento</button>
            <button v-if="item.status === 'em_atendimento'" class="min-h-10 rounded-lg bg-green-700 px-3 text-sm font-semibold text-white" @click="concluir(item.id)">Concluir</button>
            <button v-if="['aguardando', 'chamado'].includes(item.status)" class="min-h-10 rounded-lg px-3 text-sm font-semibold text-red-600 hover:bg-red-50" @click="desistir(item.id)">Desistiu</button>
          </div>
        </div>
        <form v-if="formulariosPagamento[item.id]" class="mt-4 grid gap-3 rounded-lg bg-slate-50 p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end" @submit.prevent="registrarPagamento(item)">
          <label class="text-sm font-medium text-slate-700">Valor recebido (saldo {{ Number(item.saldo).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }})<input v-model="formulariosPagamento[item.id].valor" required type="number" min="0.01" step="0.01" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"></label>
          <label class="text-sm font-medium text-slate-700">Forma<select v-model="formulariosPagamento[item.id].forma_pagamento" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><option value="pix">Pix</option><option value="dinheiro">Dinheiro</option><option value="credito">Cartão de crédito</option><option value="debito">Cartão de débito</option><option value="outro">Outro</option></select></label>
          <div class="flex gap-2"><button :disabled="formulariosPagamento[item.id].processing" class="min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white disabled:opacity-50">{{ formulariosPagamento[item.id].processing ? 'Salvando…' : 'Salvar pagamento' }}</button><button type="button" class="min-h-11 rounded-lg px-3 font-semibold text-slate-600" @click="delete formulariosPagamento[item.id]">Fechar</button></div>
          <p v-if="formulariosPagamento[item.id].errors.valor || formulariosPagamento[item.id].errors.pagamento" class="text-sm text-red-700 sm:col-span-3">{{ formulariosPagamento[item.id].errors.valor || formulariosPagamento[item.id].errors.pagamento }}</p>
        </form>
      </article>
    </section>
    <section v-else class="mt-6 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center"><h2 class="font-semibold text-slate-900">{{ modoProfissional ? 'Nenhum atendimento atribuído' : 'A fila está vazia' }}</h2><p class="mt-1 text-sm text-slate-500">{{ modoProfissional ? 'Quando a recepção atribuir um cliente a você, ele aparecerá aqui.' : 'Adicione quem chegou sem agendamento para organizar a espera.' }}</p><button v-if="podeAdministrarFila" class="mt-4 min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white" @click="mostrarFormulario = true">Adicionar primeiro cliente</button></section>
  </main>
</template>
