<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'

const pagina = usePage()
const token = ref('')
const convite = computed(() => pagina.props.flash?.conviteAceite)
const valido = computed(() => convite.value?.valido === true)
const nome = computed(() => convite.value?.nome)
const email = computed(() => convite.value?.email)
const formulario = useForm({ senha: '', senha_confirmation: '' })
function aceitar() { formulario.transform((dados) => ({ ...dados, token: token.value })).post('/convites/equipe/ativar') }
onMounted(() => {
    token.value = window.location.hash.slice(1)
    window.history.replaceState({}, '', window.location.pathname)
    if (token.value) router.post('/convites/equipe/validar', { token: token.value }, { preserveState: true, preserveScroll: true })
})
</script>

<template>
  <Head title="Ativar acesso à equipe" />
  <main class="grid min-h-screen place-items-center bg-slate-50 px-4 py-8">
    <section class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-lg sm:p-8"><p class="text-sm font-semibold text-blue-600">Opeflow Barber</p><template v-if="valido"><h1 class="mt-2 text-2xl font-bold text-slate-950">Ative seu acesso</h1><p class="mt-2 text-sm leading-6 text-slate-600">Olá, {{ nome }}. Defina uma senha para acessar o painel da equipe com o e-mail <strong>{{ email }}</strong>.</p>
      <form class="mt-6 space-y-4" @submit.prevent="aceitar"><label class="block text-sm font-semibold text-slate-700">Senha (mínimo 12 caracteres)<input v-model="formulario.senha" type="password" required minlength="12" autocomplete="new-password" class="mt-1 min-h-12 w-full rounded-lg border-slate-300"></label><p v-if="formulario.errors.senha" class="text-sm text-red-700">{{ formulario.errors.senha }}</p><label class="block text-sm font-semibold text-slate-700">Confirme sua senha<input v-model="formulario.senha_confirmation" type="password" required minlength="12" autocomplete="new-password" class="mt-1 min-h-12 w-full rounded-lg border-slate-300"></label><p v-if="formulario.errors.convite" class="text-sm text-red-700">{{ formulario.errors.convite }}</p><button :disabled="formulario.processing || !token" class="min-h-12 w-full rounded-lg bg-blue-600 px-4 font-bold text-white disabled:opacity-60">{{ formulario.processing ? 'Ativando…' : 'Ativar acesso' }}</button></form>
      </template><template v-else><h1 class="mt-2 text-2xl font-bold text-slate-950">Convite indisponível</h1><p class="mt-2 text-sm leading-6 text-slate-600">Este link pode ter expirado ou já ter sido utilizado. Peça ao proprietário da barbearia para gerar um novo convite.</p></template>
    </section>
  </main>
</template>
