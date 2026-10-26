<script setup>
import { computed } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({ barbearia: Object, servicos: Array, profissionais: Array, servicoSelecionado: Number, profissionalSelecionado: Number, dataSelecionada: String, dataMinima: String, dataMaxima: String, horarios: Array, reserva: Object })
const pagina = usePage()
const formulario = useForm({ servico_id: props.servicoSelecionado ?? '', profissional_id: props.profissionalSelecionado ?? '', data: props.dataSelecionada, hora: '', nome: '', telefone: '', email: '', codigo_beneficio: '', aceita_lembretes: false, chave_idempotencia: globalThis.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random()}` })
const servico = computed(() => props.servicos.find((item) => item.id === Number(formulario.servico_id)))

function consultarHorarios() {
    formulario.hora = ''
    formulario.clearErrors()
    pagina.router.get(`/agendar/${props.barbearia.slug}`, { servico_id: formulario.servico_id, profissional_id: formulario.profissional_id, data: formulario.data }, { preserveState: true, preserveScroll: true, replace: true })
}
function reservar() { formulario.post(`/agendar/${props.barbearia.slug}`, { preserveScroll: true }) }
</script>

<template>
  <Head :title="`Agendar | ${barbearia.nome}`"><meta name="theme-color" :content="barbearia.corPrimaria"></Head>
  <div class="min-h-screen bg-slate-50 px-4 py-8 sm:px-6" :style="{ '--marca': barbearia.corPrimaria }">
    <main class="mx-auto max-w-2xl">
      <header class="mb-6 rounded-2xl bg-slate-950 p-6 text-white shadow-lg sm:p-8">
        <div class="flex items-center gap-4"><img v-if="barbearia.logoUrl" :src="barbearia.logoUrl" :alt="`Logo ${barbearia.nome}`" class="h-14 w-14 rounded-xl bg-white object-contain p-1"><span v-else class="grid h-14 w-14 place-items-center rounded-xl bg-white/10 text-2xl font-bold">{{ barbearia.nome.slice(0,1) }}</span><div><p class="text-sm text-slate-300">Agendamento online</p><h1 class="text-2xl font-bold">{{ barbearia.nome }}</h1></div></div>
        <p class="mt-4 text-sm text-slate-300">Escolha o serviço, horário e deixe seus dados. Sua reserva será confirmada ao finalizar.</p>
      </header>
      <section v-if="reserva" class="mb-5 rounded-2xl border border-green-200 bg-green-50 p-5" role="status"><h2 class="text-lg font-bold text-green-900">Horário reservado!</h2><p class="mt-1 text-sm text-green-800">{{ reserva.servico }} com {{ reserva.profissional }}, em {{ reserva.data }} às {{ reserva.hora }}.</p><p v-if="reserva.beneficioAplicado" class="mt-2 text-sm font-semibold text-green-900">Seu benefício foi reservado para este horário.</p></section>
      <form v-else class="space-y-5" @submit.prevent="reservar">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><p class="text-xs font-bold uppercase tracking-wider text-blue-700">Passo 1 de 3</p><h2 class="mt-1 text-lg font-bold text-slate-950">Escolha o serviço e horário</h2>
          <label class="mt-4 block text-sm font-semibold text-slate-700">Serviço<select v-model="formulario.servico_id" required class="mt-1 min-h-12 w-full rounded-lg border-slate-300" @change="consultarHorarios"><option value="">Selecione um serviço</option><option v-for="item in servicos" :key="item.id" :value="item.id">{{ item.nome }} · {{ item.duracao_em_minutos }} min · R$ {{ Number(item.preco).toFixed(2) }}</option></select></label>
          <p v-if="!servicos.length" class="mt-3 text-sm text-amber-800">Esta barbearia ainda não possui serviços disponíveis para agendamento online.</p>
          <template v-if="servico"><label class="mt-4 block text-sm font-semibold text-slate-700">Profissional (opcional)<select v-model="formulario.profissional_id" class="mt-1 min-h-12 w-full rounded-lg border-slate-300" @change="consultarHorarios"><option value="">Qualquer profissional</option><option v-for="item in profissionais" :key="item.id" :value="item.id">{{ item.nome }}</option></select></label>
            <label class="mt-4 block text-sm font-semibold text-slate-700">Data<input v-model="formulario.data" type="date" :min="dataMinima" :max="dataMaxima" required class="mt-1 min-h-12 w-full rounded-lg border-slate-300" @change="consultarHorarios"></label>
            <p class="mt-4 text-sm font-semibold text-slate-700">Horários disponíveis</p><div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4"> <button v-for="(item, indice) in horarios" :key="`${item.profissionalId}-${item.hora}`" type="button" class="min-h-11 rounded-lg border px-3 font-semibold" :class="formulario.hora === item.hora && Number(formulario.profissional_id) === item.profissionalId ? 'border-blue-700 bg-blue-700 text-white' : 'border-slate-300 text-slate-700 hover:border-blue-500'" :aria-pressed="formulario.hora === item.hora && Number(formulario.profissional_id) === item.profissionalId" @click="formulario.hora = item.hora; formulario.profissional_id = item.profissionalId">{{ item.hora }}<span v-if="!formulario.profissional_id" class="sr-only"> com {{ item.profissional }}</span></button></div>
            <p v-if="!horarios.length" class="mt-2 text-sm text-slate-500">Não há horários disponíveis nessa data. Tente outro dia.</p><p v-if="formulario.errors.hora || formulario.errors.profissional_id" class="mt-2 text-sm text-red-700">{{ formulario.errors.hora || formulario.errors.profissional_id }}</p>
          </template>
        </section>
        <section v-if="formulario.hora" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><p class="text-xs font-bold uppercase tracking-wider text-blue-700">Passo 2 de 3</p><h2 class="mt-1 text-lg font-bold text-slate-950">Seus dados de contato</h2><p class="mt-1 text-sm text-slate-500">{{ servico?.nome }} · {{ formulario.data }} às {{ formulario.hora }}</p>
          <label class="mt-4 block text-sm font-semibold text-slate-700">Nome<input v-model="formulario.nome" required maxlength="120" autocomplete="name" class="mt-1 min-h-12 w-full rounded-lg border-slate-300"></label><p v-if="formulario.errors.nome" class="mt-1 text-sm text-red-700">{{ formulario.errors.nome }}</p>
          <label class="mt-3 block text-sm font-semibold text-slate-700">WhatsApp / telefone<input v-model="formulario.telefone" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="(00) 00000-0000" class="mt-1 min-h-12 w-full rounded-lg border-slate-300"></label><p v-if="formulario.errors.telefone" class="mt-1 text-sm text-red-700">{{ formulario.errors.telefone }}</p>
          <label class="mt-3 block text-sm font-semibold text-slate-700">E-mail (opcional)<input v-model="formulario.email" type="email" maxlength="255" autocomplete="email" class="mt-1 min-h-12 w-full rounded-lg border-slate-300"></label>
          <label class="mt-4 flex items-start gap-3 text-sm text-slate-700"><input v-model="formulario.aceita_lembretes" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-blue-600"><span>Aceito receber lembretes de horário e benefícios pelo WhatsApp. A barbearia preparará a mensagem para envio manual.</span></label>
          <label class="mt-3 block text-sm font-semibold text-slate-700">Código de benefício (opcional)<input v-model.trim="formulario.codigo_beneficio" maxlength="20" autocapitalize="characters" autocomplete="off" placeholder="Código recebido da barbearia" class="mt-1 min-h-12 w-full rounded-lg border-slate-300"></label><p v-if="formulario.errors.codigo_beneficio" class="mt-1 text-sm text-red-700">{{ formulario.errors.codigo_beneficio }}</p>
          <button :disabled="formulario.processing" class="mt-5 min-h-12 w-full rounded-lg px-5 font-bold text-white disabled:opacity-60" :style="{ backgroundColor: barbearia.corPrimaria }">{{ formulario.processing ? 'Confirmando…' : 'Confirmar agendamento' }}</button><p class="mt-3 text-center text-xs text-slate-500">Passo 3 de 3 · Ao confirmar, você concorda que a barbearia use esses dados para gerenciar seu horário.</p>
          <p v-if="formulario.errors.chave_idempotencia" class="mt-2 text-sm text-red-700">Atualize a página e tente novamente.</p>
        </section>
      </form>
      <footer class="mt-6 text-center text-xs text-slate-500">Agendamento online · Opeflow</footer>
    </main>
  </div>
</template>
