<script setup>
import { Head, router } from '@inertiajs/vue3'
import { BellRing, CalendarClock, Check, Clock3, MessageCircle, Scissors } from 'lucide-vue-next'
import LayoutPainel from '../../Componentes/LayoutPainel.vue'

defineOptions({ layout: LayoutPainel })
defineProps({ lembretes: Array, linkAgendamento: String })

function marcarEnviado(id) {
    if (confirm('Confirma que você enviou esta mensagem ao cliente?')) router.post(`/lembretes/${id}/marcar-enviado`, {}, { preserveScroll: true })
}
</script>

<template>
  <Head title="Lembretes" />
  <main class="mx-auto max-w-6xl px-4 py-6 pb-24 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div><p class="text-sm font-medium text-blue-600">Relacionamento com clientes</p><h1 class="mt-1 text-2xl font-bold text-slate-950">Lembretes</h1><p class="mt-1 max-w-2xl text-sm text-slate-600">Mensagens são preparadas para você revisar e enviar pelo WhatsApp. O sistema não envia mensagens automaticamente.</p></div>
      <a :href="linkAgendamento" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><Scissors class="h-4 w-4" /> Abrir link de agendamento</a>
    </header>

    <section class="mt-5 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-950"><p class="font-semibold">Como funciona</p><p class="mt-1">Os lembretes são programados para 24h e 2h antes do agendamento, além de avisos dos benefícios. Abra a conversa, confira a mensagem e envie. Só depois registre o envio aqui.</p></section>

    <section v-if="lembretes.length" class="mt-5 grid gap-3 lg:grid-cols-2">
      <article v-for="item in lembretes" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex items-start justify-between gap-3"><div class="flex min-w-0 items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700"><CalendarClock v-if="item.tipo.startsWith('agendamento')" class="h-5 w-5" /><BellRing v-else class="h-5 w-5" /></span><div class="min-w-0"><h2 class="truncate font-semibold text-slate-950">{{ item.cliente }}</h2><a :href="`tel:${item.telefone}`" class="text-sm text-blue-700">{{ item.telefone }}</a></div></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold" :class="item.status === 'pendente' ? 'bg-amber-50 text-amber-800' : item.status === 'enviado_manualmente' ? 'bg-green-50 text-green-800' : 'bg-slate-100 text-slate-600'">{{ item.status === 'pendente' ? 'Pendente' : item.status === 'enviado_manualmente' ? 'Enviado manualmente' : 'Cancelado' }}</span></div>
        <p class="mt-3 flex items-center gap-1.5 text-sm text-slate-600"><Clock3 class="h-4 w-4" />{{ item.status === 'enviado_manualmente' ? `Enviado em ${item.enviadoEm}` : `Programado para ${item.agendadoPara}` }}</p>
        <details class="mt-3 rounded-lg bg-slate-50 p-3"><summary class="cursor-pointer text-sm font-semibold text-slate-700">Prévia da mensagem</summary><p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ item.mensagem }}</p></details>
        <div v-if="item.status === 'pendente'" class="mt-4 flex flex-col gap-2 sm:flex-row"><a :href="item.linkWhatsApp" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold text-white" :class="item.podeEnviar ? 'bg-green-600 hover:bg-green-700' : 'pointer-events-none bg-slate-400'" :aria-disabled="!item.podeEnviar" :tabindex="item.podeEnviar ? 0 : -1"><MessageCircle class="h-4 w-4" /> {{ item.podeEnviar ? 'Abrir WhatsApp' : 'Disponível no horário' }}</a><button :disabled="!item.podeEnviar" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50" @click="marcarEnviado(item.id)"><Check class="h-4 w-4" /> Registrar envio</button></div>
        <p v-if="item.status === 'pendente'" class="mt-2 text-xs text-slate-500">{{ item.podeEnviar ? 'Abrir o WhatsApp não registra o envio. Use “Registrar envio” somente após enviar a mensagem.' : 'O envio fica disponível no horário programado. Abrir o WhatsApp não registra o envio.' }}</p>
      </article>
    </section>
    <section v-else class="mt-5 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center"><BellRing class="mx-auto h-8 w-8 text-slate-400" /><h2 class="mt-3 font-semibold text-slate-900">Nenhum lembrete programado</h2><p class="mt-1 text-sm text-slate-500">Ao marcar que aceita lembretes no cadastro do cliente, o sistema preparará avisos para os próximos agendamentos e benefícios.</p></section>
  </main>
</template>
