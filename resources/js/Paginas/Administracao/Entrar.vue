<script setup>
import { Head, useForm } from '@inertiajs/vue3'

defineProps({ mostrarCredenciaisDeDesenvolvimento: Boolean })
const formulario = useForm({ email: '', senha: '', lembrar: false })
function entrar() { formulario.post('/admin/entrar', { onFinish: () => formulario.reset('senha') }) }
</script>

<template>
  <Head title="Administração da plataforma" />
  <main class="grid min-h-screen place-items-center bg-slate-950 px-4 py-8">
    <form class="w-full max-w-md rounded-2xl border border-slate-700 bg-white p-6 shadow-xl sm:p-8" @submit.prevent="entrar">
      <p class="text-sm font-semibold text-blue-600">Opeflow · Plataforma</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Administração global</h1><p class="mt-2 text-sm text-slate-500">Acesso separado das contas de barbearia.</p>
      <aside v-if="mostrarCredenciaisDeDesenvolvimento" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-950"><p class="font-semibold">Conta de demonstração (somente desenvolvimento)</p><p class="mt-1">E-mail: admin@opeflow.local</p><p>Senha: OpeflowDev2026!</p></aside>
      <label class="mt-6 block text-sm font-medium text-slate-700">E-mail<input v-model="formulario.email" type="email" autocomplete="email" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label><p v-if="formulario.errors.email" class="mt-1 text-sm text-red-600">{{ formulario.errors.email }}</p>
      <label class="mt-4 block text-sm font-medium text-slate-700">Senha<input v-model="formulario.senha" type="password" autocomplete="current-password" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label><p v-if="formulario.errors.senha" class="mt-1 text-sm text-red-600">{{ formulario.errors.senha }}</p>
      <button :disabled="formulario.processing" class="mt-6 min-h-11 w-full rounded-lg bg-blue-600 px-4 font-semibold text-white hover:bg-blue-700 disabled:opacity-60">{{ formulario.processing ? 'Entrando…' : 'Entrar no painel global' }}</button>
      <a href="/entrar" class="mt-5 block text-center text-sm font-semibold text-slate-600 underline">Voltar para acesso da equipe</a>
    </form>
  </main>
</template>
