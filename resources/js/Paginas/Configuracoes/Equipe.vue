<script setup>
import { computed, ref } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import LayoutPainel from '../../Componentes/LayoutPainel.vue'

defineOptions({ layout: LayoutPainel })
const props = defineProps({ usuarios: Array, profissionais: Array })
const pagina = usePage()
const convite = computed(() => pagina.props.flash?.conviteEquipe)
const copiado = ref(false)
const formulario = useForm({ nome: '', email: '', papel: 'recepcao', profissional_id: '' })

function criarConvite() { formulario.post('/configuracoes/equipe/convites', { preserveScroll: true, onSuccess: () => formulario.reset() }) }
async function copiarConvite() {
    await navigator.clipboard.writeText(convite.value.url)
    copiado.value = true
    window.setTimeout(() => { copiado.value = false }, 2500)
}
function alterarAcesso(usuario) {
    const acao = usuario.ativo ? 'desativar' : 'reativar'
    if (!window.confirm(`Deseja ${acao} o acesso de ${usuario.nome}?`)) return
    pagina.router.patch(`/configuracoes/equipe/${usuario.id}/acesso`, {}, { preserveScroll: true })
}
function renovarConvite(usuario) {
    pagina.router.post(`/configuracoes/equipe/${usuario.id}/convite`, {}, { preserveScroll: true })
}
const papeis = { gerente: 'Gerente', recepcao: 'Recepção', profissional: 'Profissional' }
</script>

<template>
  <Head title="Equipe" />
  <main class="mx-auto max-w-6xl px-4 py-6 pb-24 sm:px-6 lg:px-8">
    <header><p class="text-sm font-semibold text-blue-600">Configurações da barbearia</p><h1 class="mt-1 text-2xl font-bold text-slate-950">Equipe e acessos</h1><p class="mt-1 text-slate-500">Convide cada pessoa para usar sua própria conta. O link é válido por 48 horas e pode ser usado uma única vez.</p></header>

    <section v-if="convite" class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5" role="status"><h2 class="font-bold text-blue-950">Convite criado para {{ convite.nome }}</h2><p class="mt-1 text-sm text-blue-900">Copie e envie este link diretamente à pessoa. Ele expira em {{ convite.expiraEm }} e não será exibido novamente.</p><div class="mt-3 flex flex-col gap-2 sm:flex-row"><input :value="convite.url" readonly class="min-h-11 min-w-0 flex-1 rounded-lg border-blue-200 bg-white text-sm"><button class="min-h-11 rounded-lg bg-blue-700 px-4 font-semibold text-white" @click="copiarConvite">{{ copiado ? 'Link copiado!' : 'Copiar convite' }}</button></div></section>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="text-lg font-bold text-slate-950">Convidar pessoa</h2><p class="mt-1 text-sm text-slate-500">A pessoa define a própria senha ao abrir o link de convite.</p>
      <form class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="criarConvite">
        <label class="text-sm font-medium text-slate-700">Nome<input v-model="formulario.nome" required maxlength="120" autocomplete="name" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"></label>
        <label class="text-sm font-medium text-slate-700">E-mail<input v-model="formulario.email" required type="email" maxlength="255" autocomplete="email" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"></label>
        <label class="text-sm font-medium text-slate-700">Acesso<select v-model="formulario.papel" class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><option value="recepcao">Recepção</option><option value="profissional">Profissional</option><option value="gerente">Gerente</option></select></label>
        <label v-if="formulario.papel === 'profissional'" class="text-sm font-medium text-slate-700">Vincular profissional<select v-model="formulario.profissional_id" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300"><option value="">Selecione</option><option v-for="profissional in profissionais" :key="profissional.id" :value="profissional.id">{{ profissional.nome }}</option></select></label>
        <div class="flex items-end"><button :disabled="formulario.processing" class="min-h-11 w-full rounded-lg bg-blue-600 px-4 font-semibold text-white disabled:opacity-60">{{ formulario.processing ? 'Criando…' : 'Criar convite' }}</button></div>
      </form>
      <p v-for="(erro, campo) in formulario.errors" :key="campo" class="mt-2 text-sm text-red-700">{{ erro }}</p>
    </section>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="text-lg font-bold text-slate-950">Pessoas com acesso</h2><ul class="mt-4 divide-y divide-slate-100">
      <li v-for="usuario in usuarios" :key="usuario.id" class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center"><div class="min-w-0 flex-1"><p class="truncate font-semibold text-slate-900">{{ usuario.nome }}</p><p class="truncate text-sm text-slate-500">{{ usuario.email }}<span v-if="usuario.profissional"> · {{ usuario.profissional }}</span></p></div><span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ papeis[usuario.papel] || usuario.papel }}{{ usuario.convitePendente ? ' · convite pendente' : '' }}</span><span class="text-sm" :class="usuario.ativo ? 'text-green-700' : 'text-slate-500'">{{ usuario.ativo ? 'Ativo' : 'Inativo' }}</span><button v-if="!usuario.ativo && !usuario.conviteAceito" class="min-h-10 rounded-lg border border-blue-200 px-3 text-sm font-semibold text-blue-700 hover:bg-blue-50" @click="renovarConvite(usuario)">{{ usuario.convitePendente ? 'Gerar novo link' : 'Reenviar convite' }}</button><button v-else class="min-h-10 rounded-lg border px-3 text-sm font-semibold" :class="usuario.ativo ? 'border-red-200 text-red-700 hover:bg-red-50' : 'border-green-200 text-green-700 hover:bg-green-50'" @click="alterarAcesso(usuario)">{{ usuario.ativo ? 'Desativar acesso' : 'Reativar acesso' }}</button></li>
      <li v-if="!usuarios.length" class="py-8 text-center text-sm text-slate-500">Ainda não há outras pessoas cadastradas na equipe.</li>
    </ul></section>
    <section class="mt-5 rounded-xl bg-slate-100 p-4 text-sm leading-6 text-slate-600"><strong class="text-slate-800">Permissões:</strong> proprietários e gerentes gerenciam equipe e configurações. Recepção pode acessar o financeiro. Acesso de profissional não inclui financeiro nem administração da equipe. Convites são secretos: compartilhe em canal privado e peça um novo convite se o link for exposto ou expirar.</section>
  </main>
</template>
