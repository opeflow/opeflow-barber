<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import LayoutPainel from '../../Componentes/LayoutPainel.vue'

defineOptions({ layout: LayoutPainel })

const props = defineProps({ clientes: Object, busca: String })
const mostrarFormulario = ref(false)
const termoBusca = ref(props.busca)
const formulario = useForm({ nome: '', telefone: '', email: '', observacoes: '', aceita_lembretes: false })

function buscar() { router.get('/clientes', { busca: termoBusca.value }, { preserveState: true, replace: true }) }
function cadastrar() { formulario.post('/clientes', { onSuccess: () => { formulario.reset(); mostrarFormulario.value = false } }) }
</script>

<template>
  <Head title="Clientes" />
  <main class="mx-auto max-w-6xl px-4 py-6 pb-24 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><p class="text-sm font-medium text-blue-600">Relacionamento</p><h1 class="mt-1 text-2xl font-bold text-slate-950">Clientes</h1><p class="mt-1 text-sm text-slate-500">{{ clientes.total }} cliente(s) cadastrado(s)</p></div>
      <button class="min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white hover:bg-blue-700" @click="mostrarFormulario = !mostrarFormulario">{{ mostrarFormulario ? 'Fechar' : 'Novo cliente' }}</button>
    </header>

    <form v-if="mostrarFormulario" class="mt-6 rounded-xl border border-blue-100 bg-white p-5 shadow-sm" @submit.prevent="cadastrar">
      <h2 class="font-semibold text-slate-950">Cadastrar cliente</h2>
      <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <label class="text-sm font-medium text-slate-700">Nome<input v-model="formulario.nome" required maxlength="120" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><span v-if="formulario.errors.nome" class="mt-1 block text-red-600">{{ formulario.errors.nome }}</span></label>
        <label class="text-sm font-medium text-slate-700">Telefone<input v-model="formulario.telefone" type="tel" autocomplete="tel" required maxlength="20" placeholder="(99) 99999-9999" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><span v-if="formulario.errors.telefone" class="mt-1 block text-red-600">{{ formulario.errors.telefone }}</span></label>
        <label class="text-sm font-medium text-slate-700">E-mail (opcional)<input v-model="formulario.email" type="email" autocomplete="email" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><span v-if="formulario.errors.email" class="mt-1 block text-red-600">{{ formulario.errors.email }}</span></label>
        <label class="text-sm font-medium text-slate-700 sm:col-span-2 lg:col-span-3">Observações<input v-model="formulario.observacoes" maxlength="1000" placeholder="Preferências ou observações úteis" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><span v-if="formulario.errors.observacoes" class="mt-1 block text-red-600">{{ formulario.errors.observacoes }}</span></label>
      </div>
      <label class="mt-4 flex items-start gap-3 text-sm text-slate-700"><input v-model="formulario.aceita_lembretes" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-blue-600"><span>Cliente autorizou receber lembretes programados. Marque somente com consentimento do cliente.</span></label>
      <button :disabled="formulario.processing" class="mt-4 min-h-11 rounded-lg bg-blue-600 px-5 font-semibold text-white disabled:opacity-60">{{ formulario.processing ? 'Salvando…' : 'Salvar cliente' }}</button>
    </form>

    <form class="mt-6 flex gap-2" @submit.prevent="buscar"><input v-model="termoBusca" type="search" placeholder="Buscar por nome ou telefone" class="min-h-11 min-w-0 flex-1 rounded-lg border-slate-300"><button class="min-h-11 rounded-lg border border-slate-300 bg-white px-4 font-semibold text-slate-700">Buscar</button></form>

    <section v-if="clientes.data.length" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <article v-for="cliente in clientes.data" :key="cliente.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="font-semibold text-slate-950">{{ cliente.nome }}</h2>
        <a class="mt-1 block text-sm text-blue-700" :href="`tel:${cliente.telefone}`">{{ cliente.telefone }}</a>
        <p v-if="cliente.email" class="mt-1 truncate text-sm text-slate-500">{{ cliente.email }}</p>
        <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3"><span class="text-xs" :class="cliente.aceita_lembretes ? 'text-green-700' : 'text-slate-500'">{{ cliente.aceita_lembretes ? 'Aceita lembretes' : 'Sem consentimento para lembretes' }}</span><button v-if="cliente.aceita_lembretes" type="button" class="text-xs font-semibold text-red-700 underline" @click="router.post(`/clientes/${cliente.id}/retirar-consentimento`, {}, { preserveScroll: true })">Retirar consentimento</button></div>
        <div v-if="cliente.beneficios?.length" class="mt-3 rounded-lg bg-blue-50 p-3"><p class="text-xs font-semibold uppercase tracking-wide text-blue-800">Benefícios</p><div v-for="beneficio in cliente.beneficios.slice(0, 2)" :key="beneficio.id" class="mt-2 text-sm text-blue-900"><p>{{ beneficio.servico ?? 'Serviço grátis' }} · {{ beneficio.status === 'disponivel' ? 'Disponível' : beneficio.status === 'expirado' ? 'Expirado' : beneficio.status === 'agendado_para_disponibilidade' ? `Disponível em ${beneficio.disponivelEm}` : beneficio.status }} · até {{ beneficio.expiraEm }}</p><p v-if="beneficio.status === 'disponivel' || beneficio.status === 'agendado_para_disponibilidade'" class="mt-1 font-mono text-xs">Código: {{ beneficio.codigoDeResgate }}</p></div></div>
        <p v-if="cliente.observacoes" class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-600">{{ cliente.observacoes }}</p>
      </article>
    </section>
    <section v-else class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center"><h2 class="font-semibold text-slate-900">{{ busca ? 'Nenhum resultado encontrado' : 'Sua lista de clientes está vazia' }}</h2><p class="mt-1 text-sm text-slate-500">{{ busca ? 'Tente buscar por outro nome ou telefone.' : 'Cadastre clientes para usar nos agendamentos.' }}</p><button v-if="!busca" class="mt-4 min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white" @click="mostrarFormulario = true">Cadastrar primeiro cliente</button></section>

    <nav v-if="clientes.links.length > 3" class="mt-6 flex flex-wrap gap-2" aria-label="Paginação de clientes"><template v-for="link in clientes.links" :key="link.label"><button v-if="link.url" :disabled="link.active" class="min-h-10 rounded-lg border px-3 text-sm" :class="link.active ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-300 bg-white text-slate-700'" @click="router.visit(link.url, { preserveScroll: true })" v-html="link.label" /><span v-else class="px-3 py-2 text-sm text-slate-400" v-html="link.label" /></template></nav>
  </main>
</template>
