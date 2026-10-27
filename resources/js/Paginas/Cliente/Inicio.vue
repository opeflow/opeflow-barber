<script setup>
import { Head, router } from '@inertiajs/vue3'
import { CalendarDays, LogOut, Scissors } from 'lucide-vue-next'
import { onMounted, ref } from 'vue'

const props = defineProps({ nome: String, agendamentos: Array, linkAgendamento: String })
const online = ref(typeof navigator === 'undefined' || navigator.onLine)
const reagendamentoAberto = ref(null)
const novaData = ref('')
const novaHora = ref('')

onMounted(() => {
  const salvar = () => { online.value = navigator.onLine }
  window.addEventListener('online', salvar)
  window.addEventListener('offline', salvar)
  try {
    localStorage.setItem('opeflow:portal-cliente:cache', JSON.stringify({ nome: props.nome, agendamentos: props.agendamentos.map(({ barbearia, dataHora, servico, profissional, status }) => ({ barbearia, dataHora, servico, profissional, status })), salvoEm: new Date().toISOString() }))
  } catch { /* Cache opcional: falta de espaço não deve bloquear o portal. */ }
})

function cancelar(id) {
  if (!online.value) return
  if (window.confirm('Deseja cancelar este horário? Se houver benefício reservado, ele será liberado novamente quando ainda estiver válido.')) {
    router.delete(`/cliente/agendamentos/${id}`)
  }
}

function abrirReagendamento(item) {
  reagendamentoAberto.value = item.id
  novaData.value = item.dataISO
  novaHora.value = item.horaISO
}

function reagendar(id) {
  if (!online.value) return
  if (window.confirm('Confirmar o novo horário? O anterior ficará no histórico e, se houver benefício, ele continuará reservado.')) {
    router.post(`/cliente/agendamentos/${id}/reagendar`, { data: novaData.value, hora: novaHora.value }, {
      onSuccess: () => { reagendamentoAberto.value = null },
    })
  }
}

function sair() {
  try { localStorage.removeItem('opeflow:portal-cliente:cache') } catch { /* O encerramento da sessão não depende do armazenamento local. */ }
  router.post('/cliente/sair')
}
</script>

<template>
  <Head title="Minha área" />
  <main class="mx-auto min-h-screen max-w-4xl bg-slate-50 px-4 py-6 sm:px-6 lg:py-10">
    <header class="flex items-center justify-between rounded-2xl bg-slate-950 p-5 text-white sm:p-7"><div><p class="text-sm text-slate-300">Opeflow Barber</p><h1 class="mt-1 text-2xl font-bold">Olá, {{ nome }}</h1><p class="mt-1 text-sm text-slate-300">Sua área de agendamentos</p></div><button class="grid h-10 w-10 place-items-center rounded-lg hover:bg-white/10" aria-label="Sair e limpar dados locais" @click="sair"><LogOut class="h-5 w-5" /></button></header>
    <p v-if="!online" role="status" class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-950">Sem conexão. Estes dados podem estar desatualizados; agendar ou cancelar exige internet.</p>
    <div class="mt-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><h2 class="text-xl font-bold text-slate-950">Seus agendamentos</h2><p class="mt-1 text-sm text-slate-500">Consulte os horários vinculados à sua conta.</p></div><a :href="linkAgendamento" :aria-disabled="!online" :class="online ? 'bg-blue-600 hover:bg-blue-700' : 'pointer-events-none bg-slate-400'" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold text-white"><Scissors class="h-4 w-4" /> Agendar novo horário</a></div>
    <section v-if="agendamentos.length" class="mt-4 grid gap-3 sm:grid-cols-2"><article v-for="item in agendamentos" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><div class="flex items-start justify-between gap-2"><h3 class="font-semibold text-slate-950">{{ item.servico }}</h3><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800">{{ item.status.replaceAll('_', ' ') }}</span></div><p class="mt-3 flex items-center gap-2 text-sm text-slate-700"><CalendarDays class="h-4 w-4 text-blue-600" />{{ item.dataHora }}</p><p class="mt-2 text-sm text-slate-500">{{ item.barbearia }} · com {{ item.profissional }}</p><div v-if="item.podeReagendar" class="mt-4 flex flex-wrap gap-2"><button type="button" :disabled="!online" class="min-h-10 rounded-lg bg-blue-600 px-3 text-sm font-semibold text-white enabled:hover:bg-blue-700 disabled:opacity-50" @click="abrirReagendamento(item)">Reagendar</button><button type="button" :disabled="!online" class="min-h-10 rounded-lg border border-red-200 px-3 text-sm font-semibold text-red-700 enabled:hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50" @click="cancelar(item.id)">Cancelar</button></div><form v-if="reagendamentoAberto === item.id" class="mt-4 grid gap-3 rounded-lg bg-slate-50 p-3" @submit.prevent="reagendar(item.id)"><label class="grid gap-1 text-sm font-medium text-slate-700">Nova data<input v-model="novaData" type="date" required class="min-h-11 rounded-lg border border-slate-300 bg-white px-3" /></label><label class="grid gap-1 text-sm font-medium text-slate-700">Novo horário<input v-model="novaHora" type="time" required class="min-h-11 rounded-lg border border-slate-300 bg-white px-3" /></label><p class="text-xs text-slate-500">Mantemos serviço e barbeiro. O sistema confirma a disponibilidade antes de salvar.</p><div class="flex gap-2"><button type="submit" :disabled="!online" class="min-h-10 rounded-lg bg-blue-600 px-3 text-sm font-semibold text-white disabled:opacity-50">Confirmar novo horário</button><button type="button" class="min-h-10 rounded-lg px-3 text-sm text-slate-600 hover:bg-white" @click="reagendamentoAberto = null">Voltar</button></div></form></article></section>
    <section v-else class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center"><CalendarDays class="mx-auto h-8 w-8 text-slate-400" /><h2 class="mt-3 font-semibold text-slate-900">Nenhum agendamento encontrado</h2><p class="mt-1 text-sm text-slate-500">Use o botão acima para escolher um horário.</p></section>
    <p class="mt-5 text-xs text-slate-500">Você pode cancelar ou reagendar horários futuros ainda não iniciados. Reagendamentos mantêm o horário anterior no histórico e não estão disponíveis sem conexão.</p>
  </main>
</template>
