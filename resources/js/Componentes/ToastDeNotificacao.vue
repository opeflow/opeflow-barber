<script setup>
import { usePage } from '@inertiajs/vue3'
import { CircleCheck, CircleAlert, Info, X } from 'lucide-vue-next'
import { onBeforeUnmount, ref, watch } from 'vue'

const pagina = usePage()
const notificacoes = ref([])
const temporizadores = new Map()
let proximoId = 0

function remover(id) {
    clearTimeout(temporizadores.get(id))
    temporizadores.delete(id)
    notificacoes.value = notificacoes.value.filter((item) => item.id !== id)
}

function adicionar(mensagem, tipo) {
    if (!mensagem) return
    const id = ++proximoId
    notificacoes.value.push({ id, mensagem, tipo })
    temporizadores.set(id, setTimeout(() => remover(id), tipo === 'erro' ? 7000 : 5000))
    if (notificacoes.value.length > 4) remover(notificacoes.value[0].id)
}

watch(() => [
    pagina.props.flash?.sucesso,
    pagina.props.flash?.erro,
    pagina.props.flash?.aviso,
    JSON.stringify(pagina.props.errors ?? {}),
], ([sucesso, erro, aviso, erros]) => {
    if (sucesso) adicionar(sucesso, 'sucesso')
    else if (erro) adicionar(erro, 'erro')
    else if (aviso) adicionar(aviso, 'aviso')
    else if (erros !== '{}') {
        const mensagens = JSON.parse(erros)
        const erroDaFila = mensagens.fila
        adicionar(Array.isArray(erroDaFila) ? erroDaFila[0] : erroDaFila || 'Confira os campos destacados e tente novamente.', 'erro')
    }
}, { immediate: true })

onBeforeUnmount(() => temporizadores.forEach((temporizador) => clearTimeout(temporizador)))
</script>

<template>
  <div class="pointer-events-none fixed inset-x-3 top-3 z-[100] flex flex-col items-end gap-2 sm:inset-x-auto sm:right-5 sm:top-5 sm:w-full sm:max-w-sm" aria-live="polite" aria-atomic="false">
    <TransitionGroup name="notificacao">
      <section v-for="notificacao in notificacoes" :key="notificacao.id" class="pointer-events-auto flex w-full items-start gap-3 rounded-xl border bg-white p-4 shadow-lg shadow-slate-950/10" :class="notificacao.tipo === 'erro' ? 'border-red-200' : notificacao.tipo === 'aviso' ? 'border-amber-200' : 'border-green-200'" :role="notificacao.tipo === 'erro' ? 'alert' : 'status'">
        <component :is="notificacao.tipo === 'erro' ? CircleAlert : notificacao.tipo === 'aviso' ? Info : CircleCheck" :class="notificacao.tipo === 'erro' ? 'text-red-600' : notificacao.tipo === 'aviso' ? 'text-amber-600' : 'text-green-700'" class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
        <p class="min-w-0 flex-1 text-sm font-medium text-slate-800">{{ notificacao.mensagem }}</p>
        <button class="-mr-1 -mt-1 grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-blue-600" aria-label="Fechar notificação" @click="remover(notificacao.id)"><X class="h-4 w-4" /></button>
      </section>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.notificacao-enter-active, .notificacao-leave-active { transition: opacity 180ms ease, transform 180ms ease; }
.notificacao-enter-from, .notificacao-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
