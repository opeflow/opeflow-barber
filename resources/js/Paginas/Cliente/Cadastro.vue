<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const formulario = useForm({ barbearia: '', email: '', senha: '', senha_confirmation: '' })
function cadastrar() { formulario.post('/cliente/cadastro', { onSuccess: () => formulario.reset() }) }
</script>

<template>
  <Head title="Criar acesso do cliente" />
  <main class="grid min-h-screen place-items-center bg-slate-50 px-4 py-8">
    <form class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" @submit.prevent="cadastrar">
      <a href="/cliente/entrar" class="text-sm font-semibold text-blue-700 underline">← Voltar ao acesso do cliente</a>
      <p class="mt-5 text-sm font-semibold text-blue-600">Opeflow Barber</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Criar acesso</h1>
      <p class="mt-2 text-sm text-slate-600">Use a barbearia e o mesmo e-mail já informado no seu cadastro. Enviaremos um link de confirmação antes de liberar seus agendamentos.</p>
      <label class="mt-5 block text-sm font-medium text-slate-700">Código da barbearia<input v-model="formulario.barbearia" autocomplete="organization" required maxlength="120" placeholder="Ex.: barbearia-central" class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label>
      <label class="mt-4 block text-sm font-medium text-slate-700">E-mail cadastrado<input v-model="formulario.email" type="email" autocomplete="email" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label>
      <label class="mt-4 block text-sm font-medium text-slate-700">Senha (mínimo 12 caracteres)<input v-model="formulario.senha" type="password" autocomplete="new-password" required minlength="12" class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label>
      <label class="mt-4 block text-sm font-medium text-slate-700">Confirme a senha<input v-model="formulario.senha_confirmation" type="password" autocomplete="new-password" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label>
      <p v-if="formulario.errors.email || formulario.errors.senha || formulario.errors.barbearia" class="mt-3 text-sm text-red-700">{{ formulario.errors.email || formulario.errors.senha || formulario.errors.barbearia }}</p>
      <button :disabled="formulario.processing" class="mt-6 min-h-11 w-full rounded-lg bg-blue-600 px-4 font-semibold text-white disabled:opacity-60">{{ formulario.processing ? 'Enviando…' : 'Enviar confirmação por e-mail' }}</button>
    </form>
  </main>
</template>
