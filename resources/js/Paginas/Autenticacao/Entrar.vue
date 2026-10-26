<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const formulario = useForm({ email: '', senha: '', lembrar: false })

function entrar() {
    formulario.post('/entrar', { onFinish: () => formulario.reset('senha') })
}
</script>

<template>
  <Head title="Entrar" />
  <main class="grid min-h-screen place-items-center bg-slate-50 px-4 py-8">
    <form class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" @submit.prevent="entrar">
      <p class="text-sm font-semibold text-blue-600">Opeflow Barber</p>
      <h1 class="mt-2 text-2xl font-bold text-slate-950">Acesse sua barbearia</h1>
      <p class="mt-2 text-sm text-slate-500">Use seus dados para administrar agenda, fila e clientes.</p>

      <label class="mt-6 block text-sm font-medium text-slate-700">
        E-mail
        <input v-model="formulario.email" type="email" autocomplete="email" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3 shadow-sm focus:border-blue-600 focus:ring-blue-600">
      </label>
      <p v-if="formulario.errors.email" class="mt-1 text-sm text-red-600">{{ formulario.errors.email }}</p>

      <label class="mt-4 block text-sm font-medium text-slate-700">
        Senha
        <input v-model="formulario.senha" type="password" autocomplete="current-password" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3 shadow-sm focus:border-blue-600 focus:ring-blue-600">
      </label>
      <p v-if="formulario.errors.senha" class="mt-1 text-sm text-red-600">{{ formulario.errors.senha }}</p>

      <label class="mt-4 flex items-center gap-2 text-sm text-slate-600"><input v-model="formulario.lembrar" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-600"> Manter conectado</label>

      <button :disabled="formulario.processing" class="mt-6 min-h-11 w-full rounded-lg bg-blue-600 px-4 font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">Entrar</button>
      <p class="mt-5 text-center text-sm text-slate-600">Ainda não usa o Opeflow? <Link href="/criar-conta" class="font-semibold text-blue-600">Criar conta</Link></p>
    </form>
  </main>
</template>
