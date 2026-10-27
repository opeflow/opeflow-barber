<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'

const token = ref('')
const formulario = useForm({ token: '', senha: '', senha_confirmation: '' })

onMounted(() => {
  token.value = new URLSearchParams(window.location.hash.slice(1)).get('token') || ''
  window.history.replaceState({}, document.title, window.location.pathname)
})

function redefinir() {
  formulario.token = token.value
  formulario.post('/cliente/redefinir-senha', { onSuccess: () => formulario.reset() })
}
</script>

<template>
  <Head title="Definir nova senha" />
  <main class="grid min-h-screen place-items-center bg-slate-50 px-4 py-8">
    <form class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" @submit.prevent="redefinir">
      <a href="/cliente/entrar" class="text-sm font-semibold text-blue-700 underline">← Voltar ao acesso</a>
      <p class="mt-5 text-sm font-semibold text-blue-600">Opeflow Barber</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Defina uma nova senha</h1>
      <p class="mt-2 text-sm text-slate-600">O link é válido por 60 minutos e pode ser usado uma única vez.</p>
      <p v-if="!token" role="alert" class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Token não encontrado. Abra novamente o link enviado ao seu e-mail ou solicite outro.</p>
      <label class="mt-5 block text-sm font-medium text-slate-700">Nova senha (mínimo 12 caracteres)<input v-model="formulario.senha" type="password" autocomplete="new-password" minlength="12" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label>
      <label class="mt-4 block text-sm font-medium text-slate-700">Confirme a nova senha<input v-model="formulario.senha_confirmation" type="password" autocomplete="new-password" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label>
      <p v-if="formulario.errors.token || formulario.errors.senha" role="alert" class="mt-3 text-sm text-red-700">{{ formulario.errors.token || formulario.errors.senha }}</p>
      <button :disabled="formulario.processing || !token" class="mt-6 min-h-11 w-full rounded-lg bg-blue-600 px-4 font-semibold text-white disabled:opacity-50">{{ formulario.processing ? 'Salvando…' : 'Redefinir senha' }}</button>
    </form>
  </main>
</template>
