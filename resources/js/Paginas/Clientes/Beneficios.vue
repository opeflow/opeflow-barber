<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import LayoutPainel from '../../Componentes/LayoutPainel.vue'

defineOptions({ layout: LayoutPainel })
defineProps({ cliente: Object, beneficios: Object })

const estados = {
  disponivel: 'Disponível',
  agendado_para_disponibilidade: 'Aguardando liberação',
  reservado: 'Reservado',
  utilizado: 'Utilizado',
  expirado: 'Expirado',
  cancelado: 'Cancelado',
}

const classes = {
  disponivel: 'bg-green-50 text-green-800',
  agendado_para_disponibilidade: 'bg-amber-50 text-amber-800',
  reservado: 'bg-blue-50 text-blue-800',
  utilizado: 'bg-slate-100 text-slate-700',
  expirado: 'bg-slate-100 text-slate-600',
  cancelado: 'bg-red-50 text-red-700',
}
</script>

<template>
  <Head :title="`Benefícios de ${cliente.nome}`" />
  <main class="mx-auto max-w-4xl px-4 py-6 pb-24 sm:px-6 lg:px-8">
    <Link href="/clientes" class="text-sm font-semibold text-blue-700 underline">← Voltar para clientes</Link>
    <header class="mt-4">
      <p class="text-sm font-medium text-blue-600">Relacionamento</p>
      <h1 class="mt-1 text-2xl font-bold text-slate-950">Histórico de benefícios</h1>
      <p class="mt-1 text-sm text-slate-500">{{ cliente.nome }} · {{ cliente.telefone }}</p>
      <p class="mt-2 text-sm text-slate-600">Consulta somente leitura. O código aparece apenas para créditos disponíveis no momento.</p>
    </header>

    <section v-if="beneficios.data.length" class="mt-6 space-y-3">
      <article v-for="beneficio in beneficios.data" :key="beneficio.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="font-semibold text-slate-950">{{ beneficio.servico || 'Serviço grátis' }}</h2>
            <p class="mt-1 text-sm text-slate-500">Emitido ao concluir {{ beneficio.servicoOrigem || 'atendimento' }}<template v-if="beneficio.concluidoEm"> em {{ beneficio.concluidoEm }}</template></p>
          </div>
          <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="classes[beneficio.status] || 'bg-slate-100 text-slate-700'">{{ estados[beneficio.status] || beneficio.status }}</span>
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
          <div><dt class="text-slate-500">Disponível a partir de</dt><dd class="mt-1 font-medium text-slate-800">{{ beneficio.disponivelEm }}</dd></div>
          <div><dt class="text-slate-500">Validade até</dt><dd class="mt-1 font-medium text-slate-800">{{ beneficio.expiraEm }}</dd></div>
          <div v-if="beneficio.utilizadoEm"><dt class="text-slate-500">Utilizado em</dt><dd class="mt-1 font-medium text-slate-800">{{ beneficio.utilizadoEm }}</dd></div>
        </dl>

        <div v-if="beneficio.agendamento" class="mt-4 rounded-lg bg-blue-50 p-3 text-sm text-blue-950">
          Reserva vinculada: {{ beneficio.agendamento.servico || beneficio.servico }} em {{ beneficio.agendamento.inicioEm }} · {{ estados[beneficio.agendamento.status] || beneficio.agendamento.status }}
        </div>
        <div v-if="beneficio.codigoDeResgate" class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-green-800">Código de resgate</p>
          <p class="mt-1 break-all font-mono text-sm text-green-950">{{ beneficio.codigoDeResgate }}</p>
        </div>
      </article>
    </section>
    <section v-else class="mt-6 rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
      <h2 class="font-semibold text-slate-900">Nenhum benefício registrado</h2>
      <p class="mt-1 text-sm text-slate-500">Quando um benefício for emitido, o histórico aparecerá aqui.</p>
    </section>

    <nav v-if="beneficios.links.length > 3" class="mt-6 flex flex-wrap gap-2" aria-label="Paginação do histórico de benefícios">
      <template v-for="link in beneficios.links" :key="link.label">
        <button v-if="link.url" :disabled="link.active" class="min-h-10 rounded-lg border px-3 text-sm" :class="link.active ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-300 bg-white text-slate-700'" @click="router.visit(link.url, { preserveScroll: true })" v-html="link.label" />
        <span v-else class="px-3 py-2 text-sm text-slate-400" v-html="link.label" />
      </template>
    </nav>
  </main>
</template>
