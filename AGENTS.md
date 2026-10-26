# Instruções para agentes — Opeflow Barber

## Objetivo do produto

O Opeflow Barber é um sistema de gestão para barbearias. Ele deve reduzir o trabalho manual de agenda e atendimento, centralizando agendamentos online, fila presencial, clientes, financeiro, benefícios e comunicação pelo WhatsApp.

O produto deve ser **mobile-first**, simples para recepção e barbeiros, e preparado para operar como SaaS multiempresa.

## Stack definida

- Backend: PHP 8.4 e Laravel 12.
- Interface: Vue 3, Inertia.js e Tailwind CSS.
- Banco de dados: PostgreSQL.
- Cache, filas e agendamentos: Redis, Laravel Queue, Horizon e Scheduler.
- Ambiente local: Docker Compose, incluindo PHP/Laravel, Node/Vite, PostgreSQL e Redis em containers separados; não dependa de PHP, Composer ou Node instalados na máquina hospedeira.
- Arquivos: armazenamento compatível com S3.
- WhatsApp: API oficial WhatsApp Business Platform, via webhooks e templates aprovados.
- Distribuição: Progressive Web App (PWA) instalável pelo navegador em celulares e computadores.

Não introduza uma API REST independente nem um SPA separado no MVP sem necessidade comprovada. Laravel + Inertia é a arquitetura padrão inicial.

## Idioma e convenções de domínio

Todo o domínio de negócio deve ser nomeado em português, incluindo:

- Models, serviços de domínio, eventos, jobs, policies, requests, testes e métodos.
- Campos, enums e estados de negócio, quando não conflitar com convenções obrigatórias de ferramentas.
- Textos de interface, mensagens de validação e documentação.

Exemplos desejados: `Agendamento`, `Atendimento`, `Cliente`, `Profissional`, `Servico`, `FilaDeEspera`, `ItemDaFila`, `Beneficio`, `Lembrete` e `MovimentacaoFinanceira`.

Use métodos expressivos, como `confirmarAgendamento()`, `adicionarNaFila()`, `chamarProximoCliente()`, `iniciarAtendimento()` e `registrarPagamento()`.

## Direção de layout e experiência

O produto deve ter aparência moderna, limpa, prática e profissional. A experiência deve privilegiar tarefas rápidas de operação, sem perder capacidade analítica no desktop.

### Paleta visual

- Azul primário: `#2563EB` — ações principais, estados ativos e links.
- Azul escuro: `#0F172A` — navegação, títulos fortes e superfícies escuras.
- Preto: `#020617` — marca, contraste e destaques pontuais.
- Branco: `#FFFFFF` — superfícies de conteúdo e campos.
- Prata/cinza: `#CBD5E1` e `#64748B` — bordas, superfícies secundárias e texto auxiliar.
- Sucesso: `#16A34A`; alerta: `#D97706`; erro: `#DC2626`.

Use o azul como cor de ação, não como decoração excessiva. Mantenha fundos predominantemente brancos ou cinza muito claro, boa área de respiro, contraste AA e estados de foco visíveis. Não dependa somente de cor para comunicar status.

Cada barbearia pode personalizar logo, cor primária e cor da navegação sem afetar outra empresa. A logo deve ser validada como imagem, ter tamanho limitado e ser servida dentro do contexto autenticado da barbearia no painel interno. Cores devem ser validadas no formato hexadecimal de seis dígitos; calcule automaticamente texto escuro ou claro para preservar contraste. Mantenha fallback da marca Opeflow se não houver logo.

### Responsividade

1. Construa telas mobile-first, considerando 320 px como largura mínima suportada.
2. Ações primárias devem permanecer acessíveis no celular, preferencialmente em área fixa inferior ou cabeçalho contextual quando necessário.
3. Em telas pequenas, use navegação inferior para ações operacionais principais: início, agenda, fila, clientes e mais.
4. Em telas a partir de desktop, utilize barra lateral persistente e amplie áreas de trabalho para agenda, relatórios e tabelas.
5. Nunca esconda uma funcionalidade por estar no celular: reorganize-a como cartões, filtros recolhíveis, visão resumida, tabela com detalhes progressivos ou exportação.
6. Tabelas grandes devem ter colunas prioritárias visíveis, detalhes expansíveis e alternativas em cartão no mobile.

### Aplicativo instalável (PWA)

O sistema deve ser entregue como PWA instalável pelos navegadores compatíveis, tanto em celular quanto em computador. A instalação não deve exigir loja de aplicativos.

- Forneça `manifest.webmanifest`, ícones em tamanhos adequados, tema visual e `service worker`.
- Exiba orientação de instalação apenas quando suportada e quando não estiver instalado, sem interromper tarefas críticas.
- O aplicativo instalado deve abrir em modo independente, com nome, ícone e cores da marca.
- Garanta que a interface principal continue útil sem conexão para consulta dos últimos dados armazenados; ações que alteram agenda, fila, financeiro ou mensagens devem exigir conexão e indicar claramente a indisponibilidade.
- Use sincronização e invalidação de cache cuidadosas: dados operacionais nunca podem ser apresentados como atuais se estiverem desatualizados.

### Padrões por área

- **Agenda:** no mobile, visão por dia e lista cronológica; no desktop, grade por profissional e semana. A criação deve estar disponível por botão flutuante/contextual.
- **Fila:** exibir posição, tempo estimado, serviço e estado em cartões de leitura rápida; ações de chamar, iniciar e desistência devem exigir poucos toques.
- **Cliente:** dados, próximo horário e ações recentes em primeiro plano; histórico em seções expansíveis.
- **Financeiro:** indicadores essenciais em cartões; lançamentos em lista adaptativa.
- **Relatórios e gráficos:** priorizar gráficos simples, filtros por período e exportação. No celular, mostrar um gráfico por vez e permitir detalhamento; no desktop, aproveitar grids, comparativos e tabelas completas.

Use componentes consistentes: cartões, badges de status com texto, diálogos de confirmação, skeletons de carregamento, estados vazios úteis e feedback imediato após cada ação.

Use notificações toast globais para sucesso, falhas de validação e avisos após navegação/ações, com texto objetivo, tempo de leitura adequado, possibilidade de fechar e anúncios acessíveis por `role="status"`/`role="alert"`. Não use toast como único local para explicar como corrigir um formulário: preserve mensagens específicas junto ao campo inválido. Evite exibir duas vezes a mesma confirmação no toast e no conteúdo da tela.

## Regras arquiteturais

1. Todas as tabelas de domínio devem conter `barbearia_id`, exceto as que forem globais por natureza.
2. Nunca confie no `barbearia_id` enviado pela interface: determine o contexto da barbearia pelo usuário autenticado, domínio público ou rota.
3. Datas devem ser armazenadas em UTC. Cada barbearia possui um fuso horário configurável, usado para exibição e regras de agenda.
4. Toda regra que envia mensagem, gera crédito, cancela reserva ou movimenta caixa deve ser idempotente.
5. Não permita conflitos de agenda para um mesmo profissional.
6. Bloqueios pontuais pertencem à barbearia e ao profissional, são armazenados em UTC e participam da mesma calculadora de disponibilidade usada no agendamento público e interno. A criação deve ser transacional e rejeitar conflitos com reservas e atendimentos ativos.
7. Pausas recorrentes são vinculadas a um turno semanal do profissional, devem ficar dentro dele, não podem se sobrepor entre si e são avaliadas na mesma calculadora de disponibilidade pública e interna.
8. Uma reserva não é automaticamente um pagamento; pagamentos devem ter entidade própria para permitir parcial, desconto e múltiplas formas de pagamento.
9. Use transações de banco nos fluxos que alterem vaga, posição de fila, benefício e atendimento simultaneamente.
10. O MVP não integra a API do WhatsApp: links `wa.me` podem abrir mensagem pré-preenchida para envio humano, e o sistema só registra confirmação manual do operador (nunca alegue entrega automática).
11. Lembretes de WhatsApp são criados somente após consentimento opcional e explícito do cliente, que deve poder ser retirado; retirar consentimento cancela lembretes pendentes. Abrir o aplicativo não altera estado. Registre usuário e horário quando o operador confirmar o envio.
12. Lançamentos de despesa são isolados por barbearia, idempotentes por chave e não devem ser apagados ou alterados sem fluxo explícito de estorno auditável.
13. Relatórios e exportações devem autorizar no servidor, limitar consultas ao contexto da barbearia e prevenir injeção de fórmulas em células CSV originadas de texto do usuário.
14. Benefícios devem ser emitidos uma única vez por atendimento originador e regra; a emissão ocorre na mesma transação de conclusão, usa janela de validade explícita e nunca pode ser resgatada fora da barbearia/cliente vinculados.
15. Benefícios disponíveis expirados devem ser marcados por tarefa agendada idempotente; estados utilizados, reservados ou cancelados nunca podem ser alterados pela rotina de expiração.
16. Resgate público de benefício exige código assinado válido, telefone do cliente vinculado, serviço beneficiado correto e reserva dentro da janela UTC; cancelar/faltar libera o crédito se ainda válido, e marcar como atendido consome-o transacionalmente.

### Acessos de equipe

- Somente proprietário e gerente podem convidar ou administrar usuários da equipe; nunca exponha gerenciamento global por `barbearia_id` ou papel informado pelo cliente.
- Convites são tokens bearer aleatórios, armazenados somente como hash, de uso único e validade curta. Não ative usuários antes do aceite e da definição da senha pela própria pessoa; links são secretos e devem ser compartilhados em canal privado.
- Prefira enviar tokens bearer em fragmentos de URL (`#token`) e removê-los da barra de endereço após a leitura; transmita o segredo em corpo HTTPS, nunca em caminho/query string, logs ou referenciadores.
- Não permita que um papel local se atribua a proprietário. Atribua profissionais a no máximo uma conta individual e valide o vínculo na mesma barbearia.
- Aplique as permissões também no servidor. Ocultar item de navegação não substitui autorização em rotas e ações.
- Conta profissional deve estar vinculada a um profissional ativo: consulte somente seus agendamentos e itens atribuídos na fila; não entregue lista completa de clientes nem relatórios financeiros. Ações no atendimento e pagamentos devem validar no servidor o vínculo com esse profissional.
- Contas de cliente e de administrador global não podem ser representadas como `Usuario` de barbearia: use provedores/guardas separados. Conta de cliente só acessa dados do `Cliente` e da barbearia vinculados; administrador global não possui `barbearia_id`.
- Seeds com credenciais padrão são exclusivos de `local`/`testing`, idempotentes e nunca devem rodar em produção. Documente credenciais de demonstração, mantenha senhas com hash e não trate o painel demo como acesso administrativo de produção.

## Estados principais

### Agendamento

`aguardando_confirmacao`, `confirmado`, `em_atendimento`, `atendido`, `cancelado`, `nao_compareceu` e `reagendado`.

### Item da fila

`aguardando`, `chamado`, `em_atendimento`, `atendido`, `desistiu` e `cancelado`.

### Benefício

`disponivel`, `reservado`, `utilizado`, `expirado` e `cancelado`.

## Agenda e fila presencial

Clientes sem reserva devem poder entrar em uma fila presencial. O item da fila deve registrar cliente, serviço, horário de chegada, profissional preferido (opcional), prioridade, previsão de atendimento e status.

Ao liberar um profissional, a aplicação deve escolher o próximo cliente elegível. A ordem padrão é chegada, depois prioridade; uma preferência de profissional só deve ser respeitada se não bloquear indevidamente a operação. Ao iniciar, o item deve gerar ou vincular um `Atendimento`, preservando o histórico da fila.

Reservas futuras e clientes em fila devem usar a mesma fonte de disponibilidade. Nunca venda uma vaga já comprometida.

## Link público e lembretes via WhatsApp (sem integração)

- O link público de agendamento é identificado pelo slug da barbearia. Nunca aceite `barbearia_id` como autoridade da requisição; valide serviço e profissional dentro da empresa vinculada ao slug.
- Limite requisições de consulta/reserva e use chave de idempotência única por barbearia para impedir reservas duplicadas. Revalide a disponibilidade dentro de transação antes de gravar.
- Agendamento público não implica portal autenticado do cliente: não afirme que consulta, cancelamento ou reagendamento autônomos existem até serem implementados.

- A equipe pode copiar/compartilhar o link público da barbearia por qualquer canal. O link público identifica a barbearia por slug.
- Mensagens pré-preenchidas abrem no WhatsApp para revisão e envio por uma pessoa. Não há API, webhook, envio automático, confirmação de entrega nem campanha no MVP.
- Só programe lembretes se houver consentimento salvo no cadastro do cliente; não infira consentimento pelo telefone ou uso anterior do sistema.
- O status `enviado_manualmente` registra a declaração do operador, com usuário e horário; não representa comprovante de entrega do WhatsApp.
- Se integração oficial for aprovada em escopo futuro, validar assinaturas e idempotência de webhooks, usar templates aprovados, filas e auditoria com redação de dados sensíveis.

## Qualidade e validação

- Escreva testes de feature para os fluxos de agendamento, fila, benefício e financeiro.
- Escreva testes unitários para cálculo de disponibilidade, seleção de fila e validade de benefícios.
- Use Form Requests para validação de entrada e Policies para autorização.
- Não exponha credenciais, tokens, telefones reais ou dados pessoais em logs e testes.
- Antes de encerrar uma alteração, execute os testes relevantes e o formatter do projeto.

## Ordem de implementação

Priorize nesta ordem: fundação multiempresa, usuários e permissões, serviços/profissionais/horários, agenda, agendamento público, fila presencial, clientes, atendimento e pagamentos, lembretes, WhatsApp, benefícios, relatórios, estoque e equipamentos.

Não implemente chatbot com IA, aplicativo nativo, marketplace ou integrações secundárias antes de o MVP estar estável.

## Manutenção da documentação

Sempre que surgir, durante o desenvolvimento, uma nova funcionalidade, alteração de escopo, decisão de arquitetura, regra de domínio, mudança de stack, requisito visual ou ajuste relevante de fluxo, atualize o [PLAN.md](./PLAN.md) e este `AGENTS.md` quando aplicável.

- Atualize o `PLAN.md` para refletir o que será construído, o roadmap, os critérios de aceite e os impactos técnicos.
- Atualize o `AGENTS.md` quando a mudança alterar regras permanentes de implementação, convenções, arquitetura, qualidade ou experiência de uso.
- Registre a documentação na mesma alteração de código ou antes dela; não deixe decisões relevantes somente na conversa ou implícitas no código.
- Mantenha guias de uso separados por público e identifique claramente o que já está disponível e o que permanece no roadmap; não descreva telas ou acessos planejados como se estivessem implementados.

## Administração global e gestão de segredos

- O painel administrativo global é distinto da operação de cada barbearia. Nunca permita acesso global com base em `barbearia_id`, parâmetro de URL ou papel local.
- Proteja rotas administrativas com autorização global dedicada, autenticação reforçada, limitação de requisições e auditoria.
- Segredos de integração devem ser cifrados em repouso, mascarados após a gravação e nunca enviados de volta à interface. Em produção, prefira um cofre de segredos gerenciado.
- Nunca grave tokens, chaves, conteúdo privado de mensagens ou dados pessoais desnecessários em logs. Redija campos sensíveis antes de persistir ou exibir eventos.
- O painel de observabilidade deve oferecer consultas filtradas e paginadas; não implemente console de comandos, execução de SQL ou leitura arbitrária de arquivos.
- Teste isolamento de autorização, redação de logs e impossibilidade de recuperar credenciais em texto puro.
