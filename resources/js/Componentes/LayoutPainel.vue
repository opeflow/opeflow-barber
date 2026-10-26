<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { BellRing, CalendarDays, ChartBar, CircleDollarSign, House, ListChecks, LogOut, Menu, MoreHorizontal, Scissors, Settings2, UsersRound, X } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { corDeTexto, corLegivelEmFundoBranco } from './cores.js'

const pagina = usePage()
const mostrarMais = ref(false)
const usuario = computed(() => pagina.props.usuarioPainel)
const primario = computed(() => usuario.value?.corPrimaria || '#2563EB')
const navegacao = computed(() => usuario.value?.corNavegacao || '#0F172A')
const estiloDeMarca = computed(() => ({
    '--cor-primaria': primario.value,
    '--cor-texto-primaria': corDeTexto(primario.value),
    '--cor-primaria-link': corLegivelEmFundoBranco(primario.value),
    '--cor-navegacao': navegacao.value,
    '--cor-texto-navegacao': corDeTexto(navegacao.value),
}))

const itensPrincipais = computed(() => [
    { nome: 'Início', href: '/', icone: House },
    { nome: 'Agenda', href: '/agenda', icone: CalendarDays },
    { nome: 'Fila', href: '/fila', icone: ListChecks },
    ...(usuario.value?.papel === 'profissional' ? [] : [{ nome: 'Clientes', href: '/clientes', icone: UsersRound }]),
])

const itensExtras = computed(() => [
    ...(usuario.value?.papel !== 'profissional' ? [{ nome: 'Lembretes', href: '/lembretes', icone: BellRing }] : []),
    ...(usuario.value?.papel !== 'profissional' ? [{ nome: 'Financeiro', href: '/financeiro', icone: CircleDollarSign }] : []),
    ...(usuario.value?.papel !== 'profissional' ? [{ nome: 'Relatórios', href: '/relatorios', icone: ChartBar }] : []),
    ...(usuario.value?.podeGerenciarConfiguracoes ? [{ nome: 'Operação', href: '/configuracoes/operacao', icone: Settings2 }] : []),
    ...(usuario.value?.podeGerenciarEquipe ? [{ nome: 'Equipe', href: '/configuracoes/equipe', icone: UsersRound }] : []),
])

function estaAtivo(href) {
    const caminho = pagina.url.split('?')[0]
    return href === '/' ? caminho === '/' : caminho === href || caminho.startsWith(`${href}/`)
}

</script>

<template>
  <div class="min-h-screen bg-slate-50" :style="estiloDeMarca">
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col px-4 py-5 text-white shadow-xl lg:flex" :style="{ backgroundColor: navegacao, color: corDeTexto(navegacao) }">
      <Link href="/" class="flex min-h-14 items-center gap-3 rounded-xl px-2" aria-label="Ir ao início">
        <img v-if="usuario?.logoUrl" :src="usuario.logoUrl" :alt="`Logo da ${usuario.barbearia}`" class="h-11 w-11 rounded-xl bg-white object-contain p-1">
        <span v-else class="grid h-11 w-11 place-items-center rounded-xl bg-white/10"><Scissors class="h-6 w-6" aria-hidden="true" /></span>
        <span class="min-w-0"><span class="block text-sm font-bold">Opeflow</span><span class="block truncate text-xs opacity-70">{{ usuario?.barbearia }}</span></span>
      </Link>

      <p class="mb-2 mt-9 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] opacity-60">Menu principal</p>
      <nav aria-label="Navegação principal" class="space-y-1">
        <Link v-for="item in itensPrincipais" :key="item.href" :href="item.href" class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors" :class="estaAtivo(item.href) ? 'shadow-sm' : 'opacity-75 hover:bg-white/10 hover:opacity-100'" :style="estaAtivo(item.href) ? { backgroundColor: primario, color: corDeTexto(primario) } : {}"><component :is="item.icone" class="h-5 w-5" aria-hidden="true" />{{ item.nome }}</Link>
      </nav>
      <p class="mb-2 mt-8 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] opacity-60">Gestão</p>
      <nav aria-label="Gestão da barbearia" class="space-y-1">
        <Link v-for="item in itensExtras" :key="item.href" :href="item.href" class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors" :class="estaAtivo(item.href) ? 'shadow-sm' : 'opacity-75 hover:bg-white/10 hover:opacity-100'" :style="estaAtivo(item.href) ? { backgroundColor: primario, color: corDeTexto(primario) } : {}"><component :is="item.icone" class="h-5 w-5" aria-hidden="true" />{{ item.nome }}</Link>
      </nav>

      <div class="mt-auto border-t border-white/15 pt-4">
        <div class="flex items-center gap-3 px-2"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white/15 text-sm font-bold">{{ usuario?.nome?.slice(0, 1)?.toUpperCase() }}</span><span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold">{{ usuario?.nome }}</span><span class="block truncate text-xs opacity-65">{{ usuario?.papel }}</span></span></div>
        <Link href="/sair" method="post" as="button" class="mt-3 flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm opacity-75 hover:bg-white/10 hover:opacity-100"><LogOut class="h-4 w-4" aria-hidden="true" /> Sair</Link>
      </div>
    </aside>

    <div class="lg:pl-64">
      <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:h-[4.5rem] lg:px-8">
        <Link href="/" class="flex min-w-0 items-center gap-3 lg:hidden">
          <img v-if="usuario?.logoUrl" :src="usuario.logoUrl" :alt="`Logo da ${usuario.barbearia}`" class="h-9 w-9 rounded-lg border border-slate-200 object-contain p-0.5">
          <span v-else class="grid h-9 w-9 place-items-center rounded-lg text-white" :style="{ backgroundColor: primario }"><Scissors class="h-5 w-5" aria-hidden="true" /></span>
          <span class="min-w-0"><span class="block text-sm font-bold text-slate-950">{{ usuario?.barbearia }}</span><span class="block text-xs text-slate-500">Opeflow Barber</span></span>
        </Link>
        <div class="hidden lg:block"><p class="text-sm font-semibold text-slate-900">{{ usuario?.barbearia }}</p><p class="text-xs text-slate-500">Painel de operação</p></div>
        <button class="grid h-10 w-10 place-items-center rounded-lg text-slate-600 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-blue-600 lg:hidden" :aria-expanded="mostrarMais" aria-label="Abrir mais opções" @click="mostrarMais = !mostrarMais"><Menu v-if="!mostrarMais" class="h-5 w-5" /><X v-else class="h-5 w-5" /></button>
        <div class="hidden items-center gap-3 lg:flex"><span class="text-sm text-slate-600">Olá, {{ usuario?.nome?.split(' ')[0] }}</span><span class="grid h-9 w-9 place-items-center rounded-full bg-slate-100 text-sm font-bold text-slate-700">{{ usuario?.nome?.slice(0, 1)?.toUpperCase() }}</span></div>
      </header>

      <div class="min-h-[calc(100vh-4rem)] pb-20 lg:min-h-[calc(100vh-4.5rem)] lg:pb-8"><slot /></div>
    </div>

    <div v-if="mostrarMais" class="fixed inset-0 z-30 bg-slate-950/20 lg:hidden" @click="mostrarMais = false"></div>
    <nav v-if="mostrarMais" aria-label="Mais opções" class="fixed bottom-[4.5rem] right-3 z-40 w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl lg:hidden">
      <Link v-for="item in itensExtras" :key="item.href" :href="item.href" class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="mostrarMais = false"><component :is="item.icone" class="h-5 w-5 text-blue-600" aria-hidden="true" />{{ item.nome }}</Link>
      <Link href="/sair" method="post" as="button" class="flex min-h-12 w-full items-center gap-3 rounded-xl px-3 text-left text-sm font-semibold text-red-700 hover:bg-red-50"><LogOut class="h-5 w-5" aria-hidden="true" /> Sair da conta</Link>
    </nav>

    <nav aria-label="Navegação móvel" class="fixed inset-x-0 bottom-0 z-20 grid border-t border-slate-200 bg-white/95 px-1 pb-[env(safe-area-inset-bottom)] pt-1 shadow-[0_-6px_20px_rgba(15,23,42,0.06)] backdrop-blur lg:hidden" :class="itensPrincipais.length === 3 ? 'grid-cols-4' : 'grid-cols-5'">
      <Link v-for="item in itensPrincipais" :key="item.href" :href="item.href" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-lg text-[10px] font-semibold" :class="estaAtivo(item.href) ? 'text-blue-700' : 'text-slate-500'" :style="estaAtivo(item.href) ? { color: corLegivelEmFundoBranco(primario) } : {}"><component :is="item.icone" class="h-5 w-5" aria-hidden="true" /><span>{{ item.nome }}</span></Link>
      <button class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-lg text-[10px] font-semibold" :class="mostrarMais ? 'text-blue-700' : 'text-slate-500'" :style="mostrarMais ? { color: corLegivelEmFundoBranco(primario) } : {}" :aria-expanded="mostrarMais" @click="mostrarMais = !mostrarMais"><MoreHorizontal class="h-5 w-5" aria-hidden="true" /><span>Mais</span></button>
    </nav>
  </div>
</template>
