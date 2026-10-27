<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const formulario = useForm({ email: '' })
function solicitar() { formulario.post('/cliente/esqueci-senha', { onSuccess: () => formulario.reset() }) }
</script>

<template>
  <Head title="Recuperar senha" />
  <main class="grid min-h-screen place-items-center bg-slate-50 px-4 py-8">
    <form class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" @submit.prevent="solicitar">
      <a href="/cliente/entrar" class="text-sm font-semibold text-blue-700 underline">← Voltar ao acesso</a>
      <p class="mt-5 text-sm font-semibold text-blue-600">Opeflow Barber</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Recuperar senha</h1>
      <p class="mt-2 text-sm text-slate-600">Informe o e-mail da sua conta verificada. Se houver uma conta correspondente, enviaremos um link seguro.</p>
      <label class="mt-5 block text-sm font-medium text-slate-700">E-mail<input v-model="formulario.email" type="email" autocomplete="email" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label>
      <p v-if="formulario.errors.email" class="mt-2 text-sm text-red-700">{{ formulario.errors.email }}</p>
      <button :disabled="formulario.processing" class="mt-6 min-h-11 w-full rounded-lg bg-blue-600 px-4 font-semibold text-white disabled:opacity-60">{{ formulario.processing ? 'Enviando…' : 'Enviar instruções' }}</button>
    </form>
  </main>
</template>
