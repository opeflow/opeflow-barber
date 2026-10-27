<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { onMounted } from 'vue'

defineProps({ mostrarCredenciaisDeDesenvolvimento: Boolean })
const formulario = useForm({ email: '', senha: '', lembrar: false })
function entrar() { formulario.post('/cliente/entrar', { onFinish: () => formulario.reset('senha') }) }
onMounted(() => { try { localStorage.removeItem('opeflow:portal-cliente:cache') } catch { /* A tela de autenticação não depende do cache local. */ } })
</script>

<template>
  <Head title="Acesso do cliente" />
  <main class="grid min-h-screen place-items-center bg-slate-50 px-4 py-8">
    <form class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" @submit.prevent="entrar">
      <p class="text-sm font-semibold text-blue-600">Opeflow Barber</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Área do cliente</h1><p class="mt-2 text-sm text-slate-500">Entre para consultar seus agendamentos.</p>
      <aside v-if="mostrarCredenciaisDeDesenvolvimento" class="mt-4 rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs text-blue-950"><p class="font-semibold">Conta de demonstração (somente desenvolvimento)</p><p class="mt-1">E-mail: cliente@opeflow.local</p><p>Senha: OpeflowDev2026!</p></aside>
      <label class="mt-6 block text-sm font-medium text-slate-700">E-mail<input v-model="formulario.email" type="email" autocomplete="email" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label><p v-if="formulario.errors.email" class="mt-1 text-sm text-red-600">{{ formulario.errors.email }}</p>
      <label class="mt-4 block text-sm font-medium text-slate-700">Senha<input v-model="formulario.senha" type="password" autocomplete="current-password" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label><p v-if="formulario.errors.senha" class="mt-1 text-sm text-red-600">{{ formulario.errors.senha }}</p>
      <a href="/cliente/esqueci-senha" class="mt-2 block text-right text-sm font-semibold text-blue-700 underline">Esqueci minha senha</a>
      <label class="mt-4 flex items-center gap-2 text-sm text-slate-600"><input v-model="formulario.lembrar" type="checkbox" class="rounded border-slate-300 text-blue-600"> Manter conectado</label>
      <button :disabled="formulario.processing" class="mt-6 min-h-11 w-full rounded-lg bg-blue-600 px-4 font-semibold text-white hover:bg-blue-700 disabled:opacity-60">{{ formulario.processing ? 'Entrando…' : 'Entrar' }}</button>
      <p class="mt-5 text-center text-sm text-slate-600">Já tem cadastro na barbearia? <a href="/cliente/cadastro" class="font-semibold text-blue-700 underline">Crie seu acesso</a> confirmando o e-mail cadastrado.</p><a href="/entrar" class="mt-3 block text-center text-sm font-semibold text-blue-700 underline">Acesso da equipe</a>
    </form>
  </main>
</template>
