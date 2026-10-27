<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'

const formulario = useForm({ codigo: '' })
function verificar() { formulario.post('/admin/mfa/verificar') }
</script>

<template>
  <Head title="Verificação administrativa" />
  <main class="grid min-h-screen place-items-center bg-slate-950 px-4 py-8">
    <form class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl sm:p-8" @submit.prevent="verificar">
      <p class="text-sm font-semibold text-blue-600">Opeflow · Segurança administrativa</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Confirme sua identidade</h1><p class="mt-2 text-sm text-slate-600">Informe o código do autenticador ou um código de recuperação ainda não utilizado.</p>
      <label class="mt-5 block text-sm font-medium text-slate-700">Código<input v-model="formulario.codigo" inputmode="text" autocomplete="one-time-code" required maxlength="30" class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label><p v-if="formulario.errors.codigo" role="alert" class="mt-1 text-sm text-red-700">{{ formulario.errors.codigo }}</p>
      <button :disabled="formulario.processing" class="mt-5 min-h-11 w-full rounded-lg bg-blue-600 px-4 font-semibold text-white hover:bg-blue-700 disabled:opacity-60">Verificar e continuar</button>
      <button type="button" class="mt-3 min-h-10 w-full rounded-lg px-4 text-sm font-semibold text-slate-600 underline" @click="router.post('/admin/sair')">Cancelar e sair</button>
    </form>
  </main>
</template>
