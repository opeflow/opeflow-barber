<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import LayoutPainel from '../Componentes/LayoutPainel.vue'

defineOptions({ layout: LayoutPainel })

const props = defineProps({ titulo: String, usuario: Object })
const modulos = computed(() => [
    { nome: 'Agenda', descricao: props.usuario.papel === 'profissional' ? 'Consulte seus atendimentos do dia.' : 'Crie e acompanhe os horários do dia.', href: '/agenda' },
    { nome: 'Fila presencial', descricao: 'Organize os atendimentos presenciais atribuídos.', href: '/fila' },
    ...(props.usuario.papel === 'profissional' ? [] : [{ nome: 'Clientes', descricao: 'Cadastre e encontre clientes rapidamente.', href: '/clientes' }]),
    ...(props.usuario.papel === 'profissional' ? [] : [{ nome: 'Financeiro', descricao: 'Confira recebimentos e pagamentos.', href: '/financeiro' }]),
])
</script>

<template>
  <main class="mx-auto min-h-screen max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <section class="rounded-2xl bg-slate-950 p-6 text-white shadow-sm sm:p-8">
      <p class="text-sm font-medium text-blue-300">{{ usuario.barbearia }}</p>
      <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ titulo }}</h1>
      <p class="mt-3 max-w-2xl text-slate-300">
        Olá, {{ usuario.nome }}. Acompanhe a operação e acesse as tarefas principais da sua equipe.
      </p>
    </section>

    <Link v-if="usuario.podeGerenciarConfiguracoes" href="/configuracoes/operacao" class="mt-6 inline-flex min-h-11 items-center rounded-lg bg-blue-600 px-4 font-semibold text-white">Configurar operação</Link>
    <Link href="/agenda" class="ml-2 mt-6 inline-flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 font-semibold text-slate-800">Abrir agenda</Link>
    <Link v-if="usuario.papel !== 'profissional'" href="/clientes" class="ml-2 mt-6 inline-flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 font-semibold text-slate-800">Clientes</Link>
    <Link href="/fila" class="ml-2 mt-6 inline-flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 font-semibold text-slate-800">Fila presencial</Link>
    <Link v-if="usuario.papel !== 'profissional'" href="/financeiro" class="ml-2 mt-6 inline-flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 font-semibold text-slate-800">Financeiro</Link>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <Link v-for="modulo in modulos" :key="modulo.nome" :href="modulo.href" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-700"><span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span></span>
        <h2 class="mt-4 font-semibold text-slate-950 group-hover:text-blue-700">{{ modulo.nome }}</h2>
        <p class="mt-1 text-sm leading-5 text-slate-500">{{ modulo.descricao }}</p>
      </Link>
    </section>
  </main>
</template>
