<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import LayoutPainel from '../../Componentes/LayoutPainel.vue'
import { corDeTexto } from '../../Componentes/cores.js'

defineOptions({ layout: LayoutPainel })

const props = defineProps({ profissionais: Array, servicos: Array, barbearia: Object, dataHoraLocal: String, fimHoraLocal: String, regraDeBeneficio: Object })
const pagina = usePage()
const profissional = useForm({ nome: '', telefone: '' })
const servico = useForm({ nome: '', descricao: '', duracao_em_minutos: 30, preco: '' })
const horario = useForm({ dia_da_semana: 1, inicio_as: '08:00', fim_as: '18:00' })
const pausa = useForm({ inicio_as: '12:00', fim_as: '13:00' })
const bloqueio = useForm({ inicio_em: props.dataHoraLocal, fim_em: props.fimHoraLocal, motivo: '' })
const regraBeneficio = useForm({ servico_originador_id: props.regraDeBeneficio?.servico_originador_id ?? '', servico_beneficiado_id: props.regraDeBeneficio?.servico_beneficiado_id ?? '', disponivel_apos_dias: props.regraDeBeneficio?.disponivel_apos_dias ?? 7, expira_apos_dias: props.regraDeBeneficio?.expira_apos_dias ?? 14, ativo: props.regraDeBeneficio?.ativo ?? true })
const vinculo = useForm({ servico_id: '' })
const identidade = useForm({ cor_primaria: props.barbearia.corPrimaria, cor_navegacao: props.barbearia.corNavegacao, logo: null, remover_logo: false })
const logoPreview = ref(props.barbearia.logoUrl)
const profissionalSelecionado = ref(null)
const dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado']
const linkCopiado = ref(false)

const servicosDisponiveis = computed(() => props.servicos.filter((item) => !profissionalSelecionado.value?.servicos.some((vinculado) => vinculado.id === item.id)))

function criarProfissional() { profissional.post('/configuracoes/profissionais', { onSuccess: () => profissional.reset() }) }
function criarServico() { servico.post('/configuracoes/servicos', { onSuccess: () => servico.reset('nome', 'descricao', 'preco') }) }
function selecionarProfissional(item) { profissionalSelecionado.value = item; vinculo.reset(); horario.reset('dia_da_semana', 'inicio_as', 'fim_as') }
function adicionarHorario() { horario.post(`/configuracoes/profissionais/${profissionalSelecionado.value.id}/horarios`, { onSuccess: () => horario.reset('dia_da_semana', 'inicio_as', 'fim_as') }) }
function criarPausa(item) { pausa.post(`/configuracoes/profissionais/${profissionalSelecionado.value.id}/horarios/${item.id}/pausas`, { preserveScroll: true }) }
function removerPausa(horarioItem, pausaItem) { if (window.confirm('Remover esta pausa recorrente?')) router.delete(`/configuracoes/profissionais/${profissionalSelecionado.value.id}/horarios/${horarioItem.id}/pausas/${pausaItem.id}`, { preserveScroll: true }) }
function criarBloqueio() { bloqueio.post(`/configuracoes/profissionais/${profissionalSelecionado.value.id}/bloqueios`, { preserveScroll: true, onSuccess: () => bloqueio.reset('motivo') }) }
function removerBloqueio(item) { if (window.confirm('Remover este bloqueio e liberar o período na agenda?')) router.delete(`/configuracoes/profissionais/${profissionalSelecionado.value.id}/bloqueios/${item.id}`, { preserveScroll: true }) }
function salvarRegraBeneficio() { regraBeneficio.post('/configuracoes/beneficios', { preserveScroll: true }) }
function vincularServico() { vinculo.post(`/configuracoes/profissionais/${profissionalSelecionado.value.id}/servicos`, { onSuccess: () => vinculo.reset() }) }
function selecionarLogo(evento) {
    const arquivo = evento.target.files?.[0] ?? null
    if (logoPreview.value?.startsWith('blob:')) URL.revokeObjectURL(logoPreview.value)
    identidade.logo = arquivo
    identidade.remover_logo = false
    logoPreview.value = arquivo ? URL.createObjectURL(arquivo) : props.barbearia.logoUrl
}
function removerLogo() {
    if (logoPreview.value?.startsWith('blob:')) URL.revokeObjectURL(logoPreview.value)
    identidade.logo = null
    identidade.remover_logo = true
    logoPreview.value = null
}
function salvarIdentidade() {
    identidade.post('/configuracoes/identidade-visual', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            if (logoPreview.value?.startsWith('blob:')) URL.revokeObjectURL(logoPreview.value)
            identidade.logo = null
            logoPreview.value = props.barbearia.logoUrl
        },
    })
}
async function copiarLinkAgendamento() {
    await navigator.clipboard.writeText(props.barbearia.linkAgendamento)
    linkCopiado.value = true
    window.setTimeout(() => { linkCopiado.value = false }, 2500)
}
const estiloDaPrevia = computed(() => ({
    '--cor-primaria': identidade.cor_primaria,
    '--cor-texto-primaria': corDeTexto(identidade.cor_primaria),
    '--cor-navegacao': identidade.cor_navegacao,
    '--cor-texto-navegacao': corDeTexto(identidade.cor_navegacao),
}))
</script>

<template>
  <Head title="Operação" />
  <main class="mx-auto max-w-6xl px-4 py-6 pb-24 sm:px-6 lg:px-8">
    <header><p class="text-sm font-semibold text-blue-600">Configurações da barbearia</p><h1 class="mt-1 text-2xl font-bold text-slate-950">Operação</h1><p class="mt-1 text-slate-500">Equipe, serviços, disponibilidade e identidade visual.</p></header>

    <section v-if="pagina.props.usuarioPainel?.podeGerenciarConfiguracoes" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <div class="mb-6 rounded-xl bg-slate-50 p-4"><h2 class="font-semibold text-slate-900">Link de agendamento online</h2><p class="mt-1 text-sm text-slate-500">Compartilhe no WhatsApp, Instagram ou no perfil da barbearia para seus clientes marcarem.</p><div class="mt-3 flex flex-col gap-2 sm:flex-row"><input :value="barbearia.linkAgendamento" readonly class="min-h-11 min-w-0 flex-1 rounded-lg border-slate-300 bg-white text-sm"><button type="button" class="min-h-11 rounded-lg bg-slate-900 px-4 font-semibold text-white" @click="copiarLinkAgendamento">{{ linkCopiado ? 'Link copiado!' : 'Copiar link' }}</button></div></div>
      <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
        <div><h2 class="text-lg font-semibold text-slate-950">Identidade visual</h2><p class="mt-1 max-w-xl text-sm leading-6 text-slate-500">Adicione a logo e escolha as cores que representam sua barbearia. As mudanças aparecem no menu e nas principais ações do painel.</p></div>
        <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-3" :style="estiloDaPrevia">
          <span v-if="logoPreview" class="grid h-11 w-11 place-items-center overflow-hidden rounded-lg bg-white"><img :src="logoPreview" :alt="`Prévia da logo de ${barbearia.nome}`" class="h-full w-full object-contain p-1"></span>
          <span v-else class="grid h-11 w-11 place-items-center rounded-lg" :style="{ backgroundColor: identidade.cor_primaria, color: corDeTexto(identidade.cor_primaria) }"><span class="text-lg font-bold">{{ barbearia.nome.slice(0, 1).toUpperCase() }}</span></span>
          <span class="rounded-lg px-3 py-2 text-sm font-semibold" :style="{ backgroundColor: identidade.cor_primaria, color: corDeTexto(identidade.cor_primaria) }">Prévia do menu</span>
          <span class="hidden rounded-lg px-3 py-2 text-xs font-semibold sm:inline-flex" :style="{ backgroundColor: identidade.cor_navegacao, color: corDeTexto(identidade.cor_navegacao) }">Navegação</span>
        </div>
      </div>

      <form class="mt-6 grid gap-5 lg:grid-cols-[1fr_1fr_1.2fr]" @submit.prevent="salvarIdentidade">
        <label class="text-sm font-semibold text-slate-700">Cor principal
          <span class="mt-2 flex min-h-12 items-center gap-3 rounded-lg border border-slate-300 p-2"><input v-model="identidade.cor_primaria" type="color" class="h-8 w-10 cursor-pointer rounded border-0 p-0" aria-label="Selecionar cor principal"><input v-model="identidade.cor_primaria" type="text" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" required class="min-h-9 min-w-0 flex-1 border-0 p-1 font-mono text-sm uppercase focus:ring-0"></span>
          <span v-if="identidade.errors.cor_primaria" class="mt-1 block text-sm font-normal text-red-700">{{ identidade.errors.cor_primaria }}</span>
        </label>
        <label class="text-sm font-semibold text-slate-700">Cor da navegação
          <span class="mt-2 flex min-h-12 items-center gap-3 rounded-lg border border-slate-300 p-2"><input v-model="identidade.cor_navegacao" type="color" class="h-8 w-10 cursor-pointer rounded border-0 p-0" aria-label="Selecionar cor da navegação"><input v-model="identidade.cor_navegacao" type="text" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$" required class="min-h-9 min-w-0 flex-1 border-0 p-1 font-mono text-sm uppercase focus:ring-0"></span>
          <span v-if="identidade.errors.cor_navegacao" class="mt-1 block text-sm font-normal text-red-700">{{ identidade.errors.cor_navegacao }}</span>
        </label>
        <div>
          <span class="text-sm font-semibold text-slate-700">Logo da barbearia</span>
          <label class="mt-2 flex min-h-12 cursor-pointer items-center justify-center rounded-lg border border-dashed border-slate-300 px-3 text-center text-sm font-medium text-slate-700 hover:border-blue-500 hover:bg-slate-50"><input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="selecionarLogo">{{ identidade.logo?.name ?? 'Escolher imagem · PNG, JPG ou WebP · até 2 MB' }}</label>
          <p v-if="identidade.errors.logo" class="mt-1 text-sm text-red-700">{{ identidade.errors.logo }}</p>
          <button v-if="logoPreview" type="button" class="mt-2 min-h-9 rounded-lg px-2 text-sm font-semibold text-red-700 hover:bg-red-50" @click="removerLogo">Remover logo</button>
        </div>
        <div class="flex flex-wrap items-center gap-3 lg:col-span-3"><button :disabled="identidade.processing" class="min-h-11 rounded-lg bg-blue-600 px-5 font-semibold text-white disabled:opacity-60">{{ identidade.processing ? 'Salvando…' : 'Salvar identidade visual' }}</button><span class="text-xs text-slate-500">O texto sobre as cores é ajustado automaticamente para manter contraste legível.</span></div>
      </form>
    </section>

    <section class="mt-6 grid gap-5 lg:grid-cols-2">
      <form class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="criarProfissional">
        <h2 class="font-semibold text-slate-950">Novo profissional</h2>
        <input v-model="profissional.nome" required placeholder="Nome" class="mt-4 min-h-11 w-full rounded-lg border-slate-300 px-3">
        <input v-model="profissional.telefone" placeholder="Telefone (opcional)" class="mt-3 min-h-11 w-full rounded-lg border-slate-300 px-3">
        <button :disabled="profissional.processing" class="mt-4 min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white">Adicionar profissional</button>
      </form>

      <form class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="criarServico">
        <h2 class="font-semibold text-slate-950">Novo serviço</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2"><input v-model="servico.nome" required placeholder="Nome do serviço" class="min-h-11 rounded-lg border-slate-300 px-3"><input v-model.number="servico.duracao_em_minutos" type="number" min="5" required placeholder="Duração (min.)" class="min-h-11 rounded-lg border-slate-300 px-3"></div>
        <div class="mt-3 grid gap-3 sm:grid-cols-2"><input v-model="servico.preco" type="number" min="0" step="0.01" required placeholder="Preço" class="min-h-11 rounded-lg border-slate-300 px-3"><input v-model="servico.descricao" placeholder="Descrição (opcional)" class="min-h-11 rounded-lg border-slate-300 px-3"></div>
        <button :disabled="servico.processing" class="mt-4 min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white">Adicionar serviço</button>
      </form>
    </section>

    <section v-if="pagina.props.usuarioPainel?.podeGerenciarConfiguracoes" class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="font-semibold text-slate-950">Benefício de retorno</h2>
      <p class="mt-1 text-sm text-slate-500">Ao concluir um atendimento do serviço originador, o cliente recebe um crédito para o serviço beneficiado dentro do prazo configurado.</p>
      <form class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="salvarRegraBeneficio">
        <select v-model="regraBeneficio.servico_originador_id" required class="min-h-11 rounded-lg border-slate-300 px-3"><option value="">Serviço que gera</option><option v-for="item in servicos" :key="item.id" :value="item.id">{{ item.nome }}</option></select>
        <select v-model="regraBeneficio.servico_beneficiado_id" required class="min-h-11 rounded-lg border-slate-300 px-3"><option value="">Serviço grátis</option><option v-for="item in servicos" :key="item.id" :value="item.id">{{ item.nome }}</option></select>
        <label class="text-xs font-medium text-slate-600">Disponível após (dias)<input v-model.number="regraBeneficio.disponivel_apos_dias" type="number" min="0" max="180" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label>
        <label class="text-xs font-medium text-slate-600">Expira após (dias)<input v-model.number="regraBeneficio.expira_apos_dias" type="number" min="1" max="365" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300 px-3"></label>
        <div class="flex items-center gap-3"><label class="flex items-center gap-2 text-sm text-slate-700"><input v-model="regraBeneficio.ativo" type="checkbox" class="rounded border-slate-300">Ativo</label><button :disabled="regraBeneficio.processing" class="min-h-11 flex-1 rounded-lg bg-blue-600 px-4 font-semibold text-white">Salvar</button></div>
      </form>
      <p v-if="regraBeneficio.errors.servico_beneficiado_id" class="mt-2 text-sm text-red-700">{{ regraBeneficio.errors.servico_beneficiado_id }}</p>
      <p class="mt-2 text-xs text-amber-700">A emissão e consulta do crédito estão disponíveis; o resgate pelo link público e o lembrete WhatsApp ainda não foram implementados.</p>
    </section>

    <section class="mt-6 grid gap-5 lg:grid-cols-[1fr_1.2fr]">
      <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold text-slate-950">Profissionais</h2><div class="mt-3 space-y-2"><button v-for="item in profissionais" :key="item.id" class="w-full rounded-lg border p-3 text-left hover:border-blue-600" :class="profissionalSelecionado?.id === item.id ? 'border-blue-600 bg-blue-50' : 'border-slate-200'" @click="selecionarProfissional(item)"><span class="font-medium">{{ item.nome }}</span><span class="mt-1 block text-sm text-slate-500">{{ item.servicos.length }} serviço(s) vinculado(s)</span></button><p v-if="!profissionais.length" class="text-sm text-slate-500">Nenhum profissional cadastrado.</p></div></article>

      <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><template v-if="profissionalSelecionado"><h2 class="font-semibold text-slate-950">{{ profissionalSelecionado.nome }}</h2><form class="mt-4 flex flex-col gap-3 sm:flex-row" @submit.prevent="vincularServico"><select v-model="vinculo.servico_id" required class="min-h-11 flex-1 rounded-lg border-slate-300 px-3"><option value="">Vincular serviço</option><option v-for="item in servicosDisponiveis" :key="item.id" :value="item.id">{{ item.nome }}</option></select><button class="min-h-11 rounded-lg border border-blue-600 px-4 font-semibold text-blue-600">Vincular</button></form><form class="mt-4 grid gap-3 sm:grid-cols-4" @submit.prevent="adicionarHorario"><select v-model.number="horario.dia_da_semana" class="min-h-11 rounded-lg border-slate-300 px-3"><option v-for="(dia, indice) in dias" :key="dia" :value="indice">{{ dia }}</option></select><input v-model="horario.inicio_as" type="time" class="min-h-11 rounded-lg border-slate-300 px-3"><input v-model="horario.fim_as" type="time" class="min-h-11 rounded-lg border-slate-300 px-3"><button class="min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white">Adicionar horário</button></form><div class="mt-5"><p class="text-sm font-medium text-slate-700">Horários cadastrados</p><ul class="mt-2 space-y-2"><li v-for="item in profissionalSelecionado.horarios_de_trabalho" :key="item.id" class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">{{ dias[item.dia_da_semana] }} · {{ item.inicio_as }}–{{ item.fim_as }}</li></ul></div><div class="mt-6 border-t border-slate-200 pt-5"><h3 class="font-semibold text-slate-900">Folga ou bloqueio pontual</h3><p class="mt-1 text-sm text-slate-500">O intervalo deixa de aparecer como disponível no agendamento online.</p><form class="mt-3 grid gap-3 sm:grid-cols-2" @submit.prevent="criarBloqueio"><label class="text-sm font-medium text-slate-700">Início<input v-model="bloqueio.inicio_em" type="datetime-local" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300"></label><label class="text-sm font-medium text-slate-700">Fim<input v-model="bloqueio.fim_em" type="datetime-local" required class="mt-1 min-h-11 w-full rounded-lg border-slate-300"></label><input v-model="bloqueio.motivo" maxlength="160" placeholder="Motivo (opcional)" class="min-h-11 rounded-lg border-slate-300 px-3"><button :disabled="bloqueio.processing" class="min-h-11 rounded-lg bg-blue-600 px-4 font-semibold text-white disabled:opacity-60">Adicionar bloqueio</button></form><p v-if="bloqueio.errors.inicio_em" class="mt-2 text-sm text-red-700">{{ bloqueio.errors.inicio_em }}</p><ul class="mt-3 space-y-2"><li v-for="item in profissionalSelecionado.bloqueios" :key="item.id" class="flex flex-col gap-2 rounded-lg bg-slate-50 p-3 text-sm sm:flex-row sm:items-center sm:justify-between"><span class="text-slate-700">{{ item.inicio_em }} – {{ item.fim_em }}<span v-if="item.motivo" class="text-slate-500"> · {{ item.motivo }}</span></span><button type="button" class="min-h-10 rounded-lg px-3 font-semibold text-red-700 hover:bg-red-50" @click="removerBloqueio(item)">Remover</button></li><li v-if="!profissionalSelecionado.bloqueios?.length" class="text-sm text-slate-500">Nenhum bloqueio futuro cadastrado.</li></ul></div></template><p v-else class="text-sm text-slate-500">Selecione um profissional para definir serviços e disponibilidade.</p></article>
    </section>

    <section v-if="profissionalSelecionado" class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="font-semibold text-slate-950">Pausas recorrentes · {{ profissionalSelecionado.nome }}</h2>
      <p class="mt-1 text-sm text-slate-500">Configure pausas dentro de cada horário semanal. O intervalo não será oferecido para reservas.</p>
      <div class="mt-4 grid gap-3 lg:grid-cols-2">
        <article v-for="item in profissionalSelecionado.horarios_de_trabalho" :key="item.id" class="rounded-lg bg-slate-50 p-3">
          <p class="text-sm font-semibold text-slate-800">{{ dias[item.dia_da_semana] }} · {{ item.inicio_as }}–{{ item.fim_as }}</p>
          <ul class="mt-2 space-y-1">
            <li v-for="pausaItem in item.pausas" :key="pausaItem.id" class="flex items-center justify-between gap-2 text-sm text-slate-600">
              <span>Pausa · {{ pausaItem.inicio_as.slice(0, 5) }}–{{ pausaItem.fim_as.slice(0, 5) }}</span>
              <button type="button" class="min-h-9 rounded px-2 font-semibold text-red-700 hover:bg-red-50" @click="removerPausa(item, pausaItem)">Remover</button>
            </li>
            <li v-if="!item.pausas?.length" class="text-sm text-slate-500">Sem pausas cadastradas.</li>
          </ul>
          <form class="mt-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]" @submit.prevent="criarPausa(item)">
            <input v-model="pausa.inicio_as" type="time" required aria-label="Início da pausa" class="min-h-10 rounded-lg border-slate-300 px-2">
            <input v-model="pausa.fim_as" type="time" required aria-label="Fim da pausa" class="min-h-10 rounded-lg border-slate-300 px-2">
            <button :disabled="pausa.processing" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-semibold text-slate-700">Adicionar pausa</button>
          </form>
          <p v-if="pausa.errors.inicio_as" class="mt-1 text-sm text-red-700">{{ pausa.errors.inicio_as }}</p>
        </article>
      </div>
    </section>
  </main>
</template>
