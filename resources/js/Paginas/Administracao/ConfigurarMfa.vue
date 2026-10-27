<script setup>
import { Head, useForm } from '@inertiajs/vue3'

defineProps({ segredo: String, emissor: String, conta: String })
const formulario = useForm({ codigo: '' })
function confirmar() { formulario.post('/admin/mfa/configurar') }
</script>

<template>
  <Head title="Configurar autenticação multifator" />
  <main class="grid min-h-screen place-items-center bg-slate-950 px-4 py-8">
    <form class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl sm:p-8" @submit.prevent="confirmar">
      <p class="text-sm font-semibold text-blue-600">Opeflow · Segurança administrativa</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Ative a verificação em duas etapas</h1>
      <p class="mt-3 text-sm leading-relaxed text-slate-600">Adicione esta conta ao seu aplicativo autenticador. Digite a chave manualmente se o aplicativo não puder ler um QR code.</p>
      <dl class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm"><dt class="text-slate-500">Conta</dt><dd class="mt-1 font-medium text-slate-900">{{ conta }}</dd><dt class="mt-4 text-slate-500">Emissor</dt><dd class="mt-1 font-medium text-slate-900">{{ emissor }}</dd><dt class="mt-4 text-slate-500">Chave de configuração · guarde em local seguro</dt><dd class="mt-2 select-all break-all rounded-lg bg-white p-3 font-mono text-base font-bold tracking-wider text-slate-950">{{ segredo }}</dd></dl>
      <label class="mt-5 block text-sm font-medium text-slate-700">Código de seis dígitos<input v-model="formulario.codigo" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label><p v-if="formulario.errors.codigo" role="alert" class="mt-1 text-sm text-red-700">{{ formulario.errors.codigo }}</p>
      <button :disabled="formulario.processing" class="mt-5 min-h-11 w-full rounded-lg bg-blue-600 px-4 font-semibold text-white hover:bg-blue-700 disabled:opacity-60">Confirmar e ativar MFA</button>
      <p class="mt-4 text-xs text-slate-500">Após confirmar, serão exibidos códigos de recuperação de uso único. Eles não poderão ser consultados novamente.</p>
    </form>
  </main>
</template>
