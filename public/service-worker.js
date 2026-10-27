const CACHE_DA_APLICACAO = 'opeflow-estatico-v2'
const RECURSOS_ESTATICOS = ['/offline.html', '/manifest.webmanifest', '/icones/opeflow.svg']

self.addEventListener('install', (evento) => {
  evento.waitUntil(caches.open(CACHE_DA_APLICACAO).then((cache) => cache.addAll(RECURSOS_ESTATICOS)))
  self.skipWaiting()
})

self.addEventListener('activate', (evento) => {
  evento.waitUntil(
    caches.keys().then((chaves) => Promise.all(
      chaves.filter((chave) => chave !== CACHE_DA_APLICACAO).map((chave) => caches.delete(chave)),
    )),
  )
  self.clients.claim()
})

self.addEventListener('fetch', (evento) => {
  if (evento.request.method !== 'GET') {
    return
  }

  if (evento.request.mode === 'navigate') {
    evento.respondWith(fetch(evento.request).catch(() => caches.match('/offline.html')))
    return
  }

  evento.respondWith(
    fetch(evento.request).then((resposta) => {
      const requisicaoEstatica = evento.request.url.startsWith(self.location.origin)
        && ['script', 'style', 'image', 'font'].includes(evento.request.destination)
        && resposta.ok

      if (requisicaoEstatica) {
        const copia = resposta.clone()
        caches.open(CACHE_DA_APLICACAO).then((cache) => cache.put(evento.request, copia))
      }

      return resposta
    }).catch(() => caches.match(evento.request).then((respostaEmCache) => respostaEmCache || caches.match('/offline.html'))),
  )
})
