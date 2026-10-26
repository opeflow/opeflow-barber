<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const formulario = useForm({ nome_da_barbearia: '', nome: '', email: '', senha: '', senha_confirmation: '' })

function criarConta() {
    formulario.post('/criar-conta', { onFinish: () => formulario.reset('senha', 'senha_confirmation') })
}
</script>

<template>
  <Head title="Criar conta" />
  <main class="grid min-h-screen place-items-center bg-slate-50 px-4 py-8">
    <form class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" @submit.prevent="criarConta">
      <p class="text-sm font-semibold text-blue-600">Opeflow Barber</p>
      <h1 class="mt-2 text-2xl font-bold text-slate-950">Crie sua conta</h1>
      <p class="mt-2 text-sm text-slate-500">Comece configurando a sua barbearia.</p>

      <label class="mt-6 block text-sm font-medium text-slate-700">Nome da barbearia
        <input v-model="formulario.nome_da_barbearia" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3 shadow-sm focus:border-blue-600 focus:ring-blue-600">
      </label>
      <p v-if="formulario.errors.nome_da_barbearia" class="mt-1 text-sm text-red-600">{{ formulario.errors.nome_da_barbearia }}</p>

      <label class="mt-4 block text-sm font-medium text-slate-700">Seu nome
        <input v-model="formulario.nome" autocomplete="name" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3 shadow-sm focus:border-blue-600 focus:ring-blue-600">
      </label>
      <p v-if="formulario.errors.nome" class="mt-1 text-sm text-red-600">{{ formulario.errors.nome }}</p>

      <label class="mt-4 block text-sm font-medium text-slate-700">E-mail
        <input v-model="formulario.email" type="email" autocomplete="email" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3 shadow-sm focus:border-blue-600 focus:ring-blue-600">
      </label>
      <p v-if="formulario.errors.email" class="mt-1 text-sm text-red-600">{{ formulario.errors.email }}</p>

      <label class="mt-4 block text-sm font-medium text-slate-700">Senha
        <input v-model="formulario.senha" type="password" autocomplete="new-password" required minlength="8" class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3 shadow-sm focus:border-blue-600 focus:ring-blue-600">
      </label>
      <label class="mt-4 block text-sm font-medium text-slate-700">Confirme a senha
        <input v-model="formulario.senha_confirmation" type="password" autocomplete="new-password" required minlength="8" class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3 shadow-sm focus:border-blue-600 focus:ring-blue-600">
      </label>
      <p v-if="formulario.errors.senha" class="mt-1 text-sm text-red-600">{{ formulario.errors.senha }}</p>

      <button :disabled="formulario.processing" class="mt-6 min-h-11 w-full rounded-lg bg-blue-600 px-4 font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">Criar e continuar</button>
      <p class="mt-5 text-center text-sm text-slate-600">Já tem conta? <Link href="/entrar" class="font-semibold text-blue-600">Entrar</Link></p>
    </form>
  </main>
</template>
