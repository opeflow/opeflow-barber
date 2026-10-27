<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { reactive } from 'vue'

const props = defineProps({ integracoes: Array })
const provedores = [
  { valor: 'whatsapp_business', nome: 'WhatsApp Business Platform' },
  { valor: 'armazenamento_s3', nome: 'Armazenamento compatível com S3' },
  { valor: 'pagamentos', nome: 'Provedor de pagamentos' },
]
const novo = useForm({ provedor: 'armazenamento_s3', rotulo: '', credencial: '' })
const edicoes = reactive({})
for (const item of props.integracoes) edicoes[item.id] = useForm({ provedor: item.provedor, rotulo: item.rotulo, credencial: '' })
function cadastrar() { novo.post('/admin/integracoes', { onSuccess: () => novo.reset('rotulo', 'credencial') }) }
function atualizar(item) { edicoes[item.id].put(`/admin/integracoes/${item.id}`, { onSuccess: () => edicoes[item.id].reset('credencial') }) }
function revogar(item) { if (window.confirm(`Desativar a integração “${item.rotulo}”?`)) router.post(`/admin/integracoes/${item.id}/revogar`) }
</script>

<template>
  <Head title="Integrações globais" />
  <main class="min-h-screen bg-slate-50 px-4 py-6 sm:px-8 sm:py-10"><div class="mx-auto max-w-5xl">
    <header><a href="/admin" class="text-sm font-semibold text-blue-700">← Painel global</a><h1 class="mt-2 text-2xl font-bold text-slate-950">Integrações e credenciais</h1><p class="mt-1 text-sm text-slate-600">Os segredos são cifrados em repouso e nunca voltam ao navegador depois de salvos.</p></header>
    <aside class="mt-5 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950"><strong>Armazenamento seguro, sem conexão ativa:</strong> este cadastro guarda e mascara credenciais, mas ainda não conecta provedores nem testa chamadas externas. WhatsApp continua fora da integração do MVP.</aside>
    <form class="mt-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2" @submit.prevent="cadastrar"><h2 class="text-lg font-bold text-slate-950 sm:col-span-2">Cadastrar credencial</h2><label class="grid gap-1 text-sm font-medium text-slate-700">Provedor<select v-model="novo.provedor" class="min-h-11 rounded-lg border-slate-300"><option v-for="p in provedores" :key="p.valor" :value="p.valor">{{ p.nome }}</option></select></label><label class="grid gap-1 text-sm font-medium text-slate-700">Rótulo interno<input v-model="novo.rotulo" maxlength="120" required class="min-h-11 rounded-lg border-slate-300 px-3"></label><label class="grid gap-1 text-sm font-medium text-slate-700 sm:col-span-2">Segredo ou chave<input v-model="novo.credencial" type="password" autocomplete="new-password" minlength="12" maxlength="2000" required class="min-h-11 rounded-lg border-slate-300 px-3"><span class="text-xs text-slate-500">Nunca inclua a chave em rótulos, mensagens ou solicitações de suporte.</span></label><p v-if="novo.errors.credencial" role="alert" class="text-sm text-red-700 sm:col-span-2">{{ novo.errors.credencial }}</p><button :disabled="novo.processing" class="min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white sm:col-span-2">{{ novo.processing ? 'Salvando…' : 'Salvar credencial cifrada' }}</button></form>
    <section class="mt-5 grid gap-3"><article v-for="item in integracoes" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-bold text-slate-950">{{ item.rotulo }}</h2><p class="mt-1 text-sm text-slate-500">{{ provedores.find(p => p.valor === item.provedor)?.nome ?? item.provedor }} · {{ item.credencialMascarada ?? 'Sem credencial' }} · atualizada {{ item.atualizadaEm ?? '—' }}</p></div><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="item.ativa ? 'bg-green-50 text-green-800' : 'bg-slate-100 text-slate-600'">{{ item.ativa ? 'Ativa' : 'Revogada' }}</span></div><form v-if="item.ativa" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2" @submit.prevent="atualizar(item)"><label class="grid gap-1 text-sm font-medium text-slate-700">Provedor<select v-model="edicoes[item.id].provedor" class="min-h-10 rounded-lg border-slate-300"><option v-for="p in provedores" :key="p.valor" :value="p.valor">{{ p.nome }}</option></select></label><label class="grid gap-1 text-sm font-medium text-slate-700">Rótulo<input v-model="edicoes[item.id].rotulo" maxlength="120" required class="min-h-10 rounded-lg border-slate-300 px-2"></label><label class="grid gap-1 text-sm font-medium text-slate-700 sm:col-span-2">Nova chave (deixe vazia para manter a atual)<input v-model="edicoes[item.id].credencial" type="password" autocomplete="new-password" minlength="12" maxlength="2000" class="min-h-10 rounded-lg border-slate-300 px-2"></label><button class="min-h-10 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700">Salvar ou rotacionar chave</button><button type="button" class="min-h-10 rounded-lg border border-red-200 px-4 text-sm font-semibold text-red-700 hover:bg-red-50" @click="revogar(item)">Revogar integração</button></form></article><p v-if="!integracoes.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Nenhuma credencial cadastrada.</p></section>
  </div></main>
</template>
