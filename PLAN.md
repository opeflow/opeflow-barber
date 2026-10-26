# Plano do produto — Opeflow Barber

## 1. Visão

O Opeflow Barber será um SaaS de gestão para barbearias. O foco inicial é organizar agenda, atender clientes que chegam sem marcar horário, automatizar a comunicação pelo WhatsApp e oferecer controle financeiro prático.

O primeiro lançamento deve resolver a rotina da recepção e do barbeiro em poucos cliques. Funcionalidades de estoque, equipamentos, campanhas e inteligência operacional entram progressivamente, sem atrasar o MVP.

## 2. Objetivos do MVP

- Permitir que o cliente agende pelo celular a partir de um link enviado no WhatsApp.
- Permitir que a equipe crie, confirme, cancele e conclua agendamentos.
- Permitir a entrada e gestão de clientes presenciais em fila.
- Centralizar cadastro e histórico dos clientes.
- Registrar atendimentos e recebimentos básicos.
- Programar lembretes e preparar mensagens para envio manual pelo WhatsApp, sem integração externa no MVP.
- Suportar uma ou mais barbearias sem misturar seus dados.

## 3. Perfis de acesso

| Perfil | Responsabilidades |
| --- | --- |
| Proprietário | Configura a barbearia, equipe, serviços, regras, financeiro e relatórios. |
| Gerente/recepção | Gerencia agenda, fila, clientes, atendimentos e pagamentos. |
| Profissional | Consulta a própria agenda, chama cliente, inicia e conclui atendimento. |
| Cliente | Reserva pelo link público; consulta/cancelamento/reagendamento autenticados permanecem no roadmap. |

## 4. Módulos e funcionalidades

### 4.1 Barbearia, usuários e permissões

- Cadastro de barbearia com nome, logo, contato, endereço, fuso horário e políticas.
- Personalização de logo, cor primária e cor da navegação no painel, isolada por barbearia e com contraste de texto calculado automaticamente.
- Usuários associados à barbearia com perfis e permissões.
- Convites de equipe com link aleatório de uso único, expiração em 48 horas, hash do token em repouso e senha criada pelo próprio convidado; o segredo vai no fragmento da URL e é enviado ao servidor somente no corpo HTTPS. Acesso pode ser desativado/reativado pelo proprietário ou gerente.
- Vinculação de conta individual ao profissional correspondente. Recepção, gerente e profissional possuem papéis distintos; rotas de equipe/configuração e financeiro conferem autorização no servidor.
- Estrutura multiempresa baseada em `barbearia_id`.
- Configuração do link público de agendamento.

### 4.2 Profissionais, serviços e disponibilidade

- Cadastro de profissionais ativos/inativos.
- Serviços com nome, descrição, duração, preço base, cor e intervalo de preparação.
- Vínculo entre profissional e serviços executados.
- Jornada de trabalho recorrente, pausas vinculadas a cada turno e bloqueios pontuais de agenda por profissional; calendário de feriados permanece no roadmap.
- Intervalos de agenda configuráveis, sugerido inicialmente: 15 minutos.

### 4.3 Agenda

- Visões diária, semanal e por profissional.
- Criação manual e pública de agendamento.
- Estados: aguardando confirmação, confirmado, em atendimento, atendido, cancelado, não compareceu e reagendado.
- Prevenção de sobreposição de horários por profissional.
- Reagendamento com preservação de histórico.
- Bloqueio de horários pela recepção.

### 4.4 Agendamento público

Fluxo sugerido:

```text
Link público → serviço → profissional (opcional) → data/hora → identificação → confirmação
```

- Página pública responsiva com URL única por barbearia.
- Escolha de serviço e, se aplicável, profissional.
- Exibição somente de horários realmente disponíveis.
- Identificação por nome e telefone; localizar ou criar cliente.
- Confirmação de reserva e instruções de atendimento.
- Entrega inicial: link `/agendar/{slug}`, seleção de serviço/profissional/data/horário, cadastro ou reutilização de cliente por telefone e confirmação sem conta.
- O link pode ser copiado em **Configurações → Operação**; a página pública respeita a identidade visual da barbearia.
- Consultas e reservas têm limitação de requisições, validação no servidor e chave idempotente; disponibilidade considera agendamentos e atendimentos presenciais ativos.
- Futuramente: sinal via Pix e confirmação de presença.

### 4.5 Fila presencial

Clientes que chegarem sem reserva entram em uma fila da barbearia, vinculada ao dia e à unidade.

Cada item deve conter:

- Cliente, serviço desejado, horário de chegada e posição.
- Profissional preferido, opcional.
- Prioridade normal ou manual.
- Previsão calculada de atendimento.
- Estado: aguardando, chamado, em atendimento, atendido, desistiu ou cancelado.
- Observações de recepção.

Regras:

1. A ordenação padrão é prioridade e horário de chegada.
2. O sistema só chama cliente para profissional apto ao serviço.
3. Preferência de profissional é considerada sem deixar o atendimento ocioso indevidamente.
4. Um profissional com agendamento iminente não deve receber cliente cujo serviço ultrapasse a próxima reserva.
5. Ao começar, o item da fila é vinculado a um `Atendimento`.
6. A desistência mantém o histórico e recalcula as previsões restantes.
7. A fila e a agenda compartilham a disponibilidade dos profissionais.

### 4.6 Clientes

- Cadastro único por barbearia, com deduplicação por telefone.
- Nome, telefone, e-mail opcional, data de nascimento e observações.
- Preferências de corte e atendimento.
- Etiquetas: frequente, aniversariante, faz pezinho, inativo e outras configuráveis.
- Histórico de agendamentos, fila, atendimentos, pagamentos, faltas e benefícios.
- Consentimento e preferências de comunicação.

### 4.7 Atendimento e financeiro

- Conversão de agendamento ou item da fila em atendimento.
- Serviços realizados, valores, descontos, acréscimos e observações.
- Registro de pagamentos em dinheiro, Pix, crédito, débito e outros.
- Suporte a pagamento parcial e múltiplas formas de pagamento.
- Despesas manuais categorizadas com chave de idempotência e trilha do usuário que lançou.
- Resumo operacional diário de receitas, despesas e saldo líquido por fuso da barbearia (implementado inicialmente); fechamento de caixa e estornos auditáveis permanecem no roadmap.
- Relatórios iniciais: receita por período, por serviço e por profissional; ticket médio; cancelamentos; faltas; exportação CSV.

### 4.8 Lembretes e WhatsApp

- Não haverá integração com a API do WhatsApp no escopo atual. A barbearia compartilha o link público de agendamento em mensagens próprias.
- A equipe acessa `/lembretes`, revisa uma mensagem pré-preenchida e abre o WhatsApp via link `wa.me`; o sistema não envia nem afirma entrega automática.
- Programação inicial: 24 horas e 2 horas antes do horário; benefícios geram lembrete de disponibilidade e aviso próximo da expiração.
- Lembretes só são criados quando o cliente consentiu receber esses avisos. Consentimento é opcional no agendamento público e explícito no cadastro pela equipe.
- Registrar envio exige confirmação manual após o envio; abrir o WhatsApp não altera o estado. A trilha guarda data/hora e usuário, sem alegar confirmação do provedor.
- Cancelamento do agendamento cancela lembretes ainda pendentes. Criação é idempotente por lembrete e isolada por barbearia.
- Integração oficial, recebimento de webhooks, campanhas e confirmações automáticas permanecem fora do escopo até autorização futura.

### 4.9 Benefício de pezinho grátis

Após um corte elegível ser concluído, o sistema pode emitir um benefício para o cliente.

Configuração por benefício:

- Serviço originador, como `Corte masculino`.
- Serviço beneficiado, como `Pezinho`.
- Início e fim da janela de utilização, por exemplo entre 7 e 14 dias após o corte.
- Quantidade máxima de usos, normalmente um.
- Duração reservada para atendimento gratuito.
- Profissionais elegíveis.
- Regra de preço: gratuito ou desconto fixo/percentual.

Fluxo:

```text
Atendimento concluído → benefício criado → lembrete programado → cliente agenda → benefício reservado → atendimento → benefício utilizado ou expirado
```

## 5. Modelo de domínio

| Entidade | Responsabilidade |
| --- | --- |
| `Barbearia` | Empresa, configurações, fuso e regras de operação. |
| `Usuario` | Pessoa autenticada e suas permissões. |
| `Profissional` | Barbeiro/prestador com agenda e serviços habilitados. |
| `Servico` | Serviço oferecido, duração e preço base. |
| `HorarioDeTrabalho` | Disponibilidade recorrente do profissional ou unidade. |
| `BloqueioDeAgenda` | Indisponibilidade temporária. |
| `Cliente` | Dados, preferências e histórico do consumidor. |
| `Agendamento` | Reserva programada para serviço e profissional. |
| `FilaDeEspera` | Agrupamento diário da fila presencial. |
| `ItemDaFila` | Presença de um cliente em espera. |
| `Atendimento` | Execução real dos serviços. |
| `Pagamento` | Parte ou totalidade do recebimento de um atendimento. |
| `Despesa` | Saída financeira da barbearia. |
| `Lembrete` | Comunicação programada e seu resultado. |
| `MensagemWhatsApp` | Auditoria de mensagens; integração com provedor permanece futura. |
| `Lembrete` | Aviso programado e confirmação manual de envio por operador. |
| `RegraDeBeneficio` | Política de emissão de benefício. |
| `BeneficioDoCliente` | Crédito emitido, reservado, utilizado ou expirado. |
| `ProdutoEstoque` | Item de consumo ou revenda, fase posterior. |
| `Equipamento` | Máquina/ferramenta e seu ciclo de manutenção, fase posterior. |

## 6. Métodos de domínio relevantes

```php
Agendamento::confirmarAgendamento()
Agendamento::cancelarAgendamento()
Agendamento::reagendarPara()
Agendamento::iniciarAtendimento()

FilaDeEspera::adicionarCliente()
FilaDeEspera::chamarProximoCliente()
FilaDeEspera::recalcularPrevisoes()
ItemDaFila::chamarCliente()
ItemDaFila::registrarDesistencia()

Atendimento::adicionarServico()
Atendimento::concluirAtendimento()
Atendimento::registrarPagamento()

BeneficioDoCliente::reservar()
BeneficioDoCliente::utilizar()
BeneficioDoCliente::expirar()

Lembrete::agendarEnvio()
Lembrete::marcarComoEnviado()
```

## 7. Arquitetura técnica

| Camada | Tecnologia | Papel |
| --- | --- | --- |
| Aplicação | Laravel 12 / PHP 8.4 | Regras de negócio, autenticação, filas, jobs e webhooks. |
| Interface | Vue 3 + Inertia.js + Tailwind CSS | Painel interno e páginas públicas responsivas. |
| Banco | PostgreSQL | Dados transacionais e consultas de agenda. |
| Assíncrono | Redis + Horizon | Filas de WhatsApp, lembretes, cálculos e tarefas pesadas. |
| Agendador | Laravel Scheduler | Disparo periódico de lembretes, expirações e relatórios. |
| Arquivos | S3 compatível | Logotipos, comprovantes e anexos futuros. |
| Infraestrutura | Docker Compose | Desenvolvimento local reproduzível, com containers de PHP/Laravel, Node/Vite, PostgreSQL e Redis. |
| Instalação | PWA | Aplicativo instalável no navegador, em celular e computador. |

## 8. Requisitos não funcionais

- Dados isolados por barbearia.
- Armazenamento de datas em UTC e apresentação no fuso configurado.
- Operações de reserva e chamada de fila protegidas por transação.
- Idempotência nos webhooks e lembretes.
- LGPD: mínimo de dados, trilha de auditoria e preferência de comunicação.
- Interface otimizada para celular e uso rápido pela recepção.
- Backups automáticos e monitoramento de filas antes da produção.

## 9. Layout e experiência de uso

### 9.1 Princípios

- Interface moderna, limpa e focada em eficiência operacional.
- Mobile-first para barbeiros, recepção e agendamento público.
- Desktop ampliado para gestão, relatórios, configurações e análises detalhadas.
- Menos cliques para ações frequentes: criar agendamento, adicionar à fila, chamar cliente, iniciar atendimento e registrar pagamento.
- Conteúdo e ações mais importantes sempre acima da dobra ou facilmente alcançáveis.

### 9.2 Identidade visual

| Uso | Cor | Código |
| --- | --- | --- |
| Ação principal | Azul | `#2563EB` |
| Navegação e títulos | Azul escuro | `#0F172A` |
| Destaque de marca | Preto | `#020617` |
| Superfície principal | Branco | `#FFFFFF` |
| Bordas e fundos secundários | Prata/cinza | `#CBD5E1` |
| Texto auxiliar | Cinza escuro | `#64748B` |
| Sucesso | Verde | `#16A34A` |
| Alerta | Âmbar | `#D97706` |
| Erro | Vermelho | `#DC2626` |

Os fundos devem ser predominantemente branco e cinza muito claro; o azul identifica ações, seleção e navegação ativa. A interface não deve usar efeitos pesados, excesso de sombras ou azul em áreas decorativas. Contraste, foco de teclado e texto de status são obrigatórios, inclusive onde houver cores.

O proprietário pode substituir a cor primária, a cor de navegação e a logo nas configurações operacionais. A personalização é isolada por `barbearia_id`, tem defaults da marca e seleciona automaticamente texto claro/escuro com contraste legível sobre as cores escolhidas.

### 9.3 Estrutura de navegação

**No celular:** cabeçalho compacto com contexto da barbearia e navegação inferior para `Início`, `Agenda`, `Fila`, `Clientes` e `Mais`. Ações recorrentes usam botão contextual, como `Novo agendamento` ou `Adicionar à fila`.

**No desktop:** barra lateral fixa com os mesmos módulos e uma área principal ampla. Agenda semanal, tabelas financeiras e relatórios devem explorar a largura disponível sem comprometer legibilidade.

### 9.4 Comportamento das telas principais

| Tela | Mobile | Desktop |
| --- | --- | --- |
| Início | resumo do dia, próximos atendimentos e fila atual em cartões | resumo, indicadores e atalhos com mais contexto simultâneo |
| Agenda | agenda diária em lista cronológica; filtros recolhíveis | grade diária/semanal por profissional e painel de detalhes |
| Fila | cartões com posição, previsão, serviço e ações diretas | painel de fila, previsão e disponibilidade por profissional |
| Clientes | busca e cartões; histórico expansível | lista com filtros, painel lateral e histórico detalhado |
| Financeiro | indicadores essenciais e lançamentos em lista | filtros, tabelas, comparativos e detalhamento por profissional/serviço |
| Relatórios | um gráfico por vez, filtros compactos e exportação | grade de gráficos, filtros persistentes, tabelas e comparativos |

### 9.5 Componentes necessários

- `CartaoDeIndicador`: valor, comparação com período anterior e atalho opcional.
- `BadgeDeStatus`: estado com texto, ícone e cor semântica.
- `CartaoDeFila`: cliente, serviço, posição, previsão e ações rápidas.
- `CartaoDeAgendamento`: horário, cliente, serviço, profissional e estado.
- `SeletorDePeriodo`: hoje, semana, mês, período personalizado e comparação opcional.
- `TabelaResponsiva`: colunas prioritárias, detalhes expansíveis e modo cartão em telas pequenas.
- `GraficoResponsivo`: título, legenda acessível, filtros e visão de dados em tabela como alternativa.
- `EstadoVazio`: explica o contexto e oferece a ação apropriada.
- `DialogoDeConfirmacao`: usado em cancelamentos, desistências, exclusões e ações financeiras sensíveis.

### 9.6 Critérios de responsividade e acessibilidade

1. Suportar largura mínima de 320 px sem rolagem horizontal da página.
2. Áreas de toque com pelo menos 44 × 44 px.
3. Foco visível para teclado e semântica adequada para leitores de tela.
4. Gráficos devem apresentar alternativa tabular ou resumo textual dos dados.
5. Filtros avançados devem ser recolhíveis no celular, nunca eliminados.
6. Não usar tooltips como única fonte de informação relevante.
7. Formularios devem validar em linha, preservar dados e orientar a correção em linguagem clara.

### 9.7 Aplicativo instalável (PWA)

O painel interno e a experiência pública devem poder ser instalados diretamente pelo navegador em dispositivos compatíveis. O PWA reduz atrito para a equipe, criando um atalho com aparência de aplicativo no Android, iOS compatível e computadores com Chrome, Edge ou navegadores equivalentes.

Entregáveis técnicos:

- `manifest.webmanifest` com nome, nome curto, descrição, ícones, cor de tema, cor de fundo e modo `standalone`.
- Ícones da marca em formatos e tamanhos adequados para celular e desktop.
- `service worker` para cache dos arquivos estáticos e experiência de carregamento rápida.
- Tela/indicador de atualização quando uma nova versão estiver disponível.
- Detecção de estado online/offline, com mensagem clara quando uma ação exigir internet.
- Ação contextual de instalação, discreta e disponível somente para navegadores compatíveis e quando o sistema ainda não estiver instalado.

Limites do primeiro lançamento offline:

- Páginas e recursos estáticos podem abrir sem internet após acesso prévio.
- Os últimos dados consultados podem ser exibidos identificados como dados armazenados/offline.
- Criar ou alterar agendamento, fila, atendimento, pagamento, benefício e mensagens exige conexão no MVP, para evitar conflitos de disponibilidade e caixa.
- Uma estratégia de fila de sincronização offline para mutações só deve ser criada após regras explícitas de resolução de conflitos.

## 10. Roadmap de entrega

### Fase 0 — Fundação

- **Em andamento:** estrutura Laravel/Inertia, Docker, dependências iniciais, entidades e migrações base de barbearia, usuários, profissionais, serviços, clientes, agendamentos e horários de trabalho; onboarding transacional de barbearia/proprietário, autenticação e perfis iniciais; cadastro operacional de serviços, profissionais e disponibilidade; primeiro cálculo de disponibilidade; shell visual; manifesto PWA, ícone e service worker de recursos estáticos.
- **Agenda implementada inicialmente:** calendário diário, criação/cancelamento, validação transacional de disponibilidade, serviços filtrados por profissional e isolamento pela barbearia autenticada.
- **Bloqueios pontuais de agenda:** proprietário/gerente pode cadastrar e remover períodos de folga/indisponibilidade por profissional; o cálculo compartilhado impede a oferta no agendamento público e conflitos com reservas existentes são rejeitados.
- **Pausas recorrentes:** proprietário/gerente pode configurar intervalos dentro de cada turno semanal; a pausa é aplicada em todas as semanas e exclui o intervalo do cálculo público e interno. Pausas sobrepostas ou fora do turno são rejeitadas; calendário de feriados segue pendente.
- **Clientes implementados inicialmente:** cadastro, busca e listagem paginada com isolamento por barbearia, necessário para iniciar agendamentos.
- **Fila presencial implementada inicialmente:** entrada de clientes sem horário, posição, previsão aproximada, chamada de profissional compatível e abertura/conclusão de atendimento; fluxo validado em teste integrado usando banco de testes isolado.
- **Atendimento e financeiro implementados inicialmente:** pagamentos em entidade própria, recebimentos parciais e em formas combinadas (dinheiro, Pix, crédito, débito e outras), chave de idempotência por operação, limite ao saldo do atendimento e resumo diário por forma de pagamento com paginação; o formulário é acessível na fila durante o atendimento.
- **Despesas e saldo diário:** registro categorizado de despesas com chave idempotente, usuário responsável e data/hora local convertida para UTC; resumo calcula recebimentos menos saídas e isola consulta por barbearia. Não há ainda edição, exclusão, estorno nem fechamento formal de caixa.
- **Relatórios iniciais:** resumo por intervalo de até um ano, no fuso da barbearia, com receitas, despesas, saldo operacional, ticket médio por atendimento pago, cancelamentos, faltas e receitas agrupadas por serviço/profissional; exportação CSV com proteção contra fórmulas em descrições. Comparações com períodos anteriores e gráficos avançados seguem pendentes.
- **Benefício de retorno:** regra por barbearia, emissão idempotente ao concluir serviço originador, expiração horária, código de resgate assinado derivado sem armazenar segredo, validação de telefone/serviço/janela no agendamento público, reserva do crédito, liberação ao cancelar/faltar e consumo ao marcar atendido. Lembrete automático e extrato completo do histórico ainda pendentes.
- **Identidade visual por barbearia implementada inicialmente:** upload/remover logo, cor primária e cor da navegação; menu e ações usam as cores da empresa autenticada, com texto de contraste automático, fallback da marca e separação por empresa.
- **Experiência de uso refinada:** layout interno responsivo compartilhado com navegação lateral no desktop e inferior no celular, além de notificações toast acessíveis para sucesso, falhas e avisos.
- **Equipe individual:** proprietários/gerentes podem gerar convites de uso único para gerente, recepção ou profissional; profissional pode ser vinculado a uma única conta. Convidado define senha própria e ativa o acesso. Acesso pode ser revogado/reativado; convite pendente expirado pode ser renovado. E-mail automático ainda depende da integração de envio.
- **Permissões iniciais no servidor:** gerenciamento de equipe/configurações restrito a proprietário/gerente; resumo financeiro restrito a proprietário/gerente/recepção; profissional vê somente agenda e atendimentos da fila vinculados ao próprio cadastro, não consulta a lista de clientes nem gerencia agendamentos/fila. Pode iniciar/concluir seu atendimento e registrar pagamento apenas nele enquanto estiver em andamento. Matriz completa por tela/ação ainda precisa ser refinada.
- **Guias de uso criados:** instruções para proprietário, equipe, clientes e logins de desenvolvimento em `docs/`, identificando o que já está disponível e o que ainda não foi lançado. A autenticação do portal de demonstração do cliente permite consulta somente leitura dos próprios agendamentos; cadastro, recuperação de senha, cancelamento e reagendamento continuam pendentes.
- **Acessos de desenvolvimento:** `DesenvolvimentoSeeder` cria, de forma idempotente e somente em `local`/`testing`, logins de proprietário e profissional pela guarda de equipe, conta de cliente vinculada e administrador global isolado. O painel global é somente uma base de autenticação; logs, integrações e gestão de segredos continuam pendentes.
- A previsão inicial da fila é indicativa e distribuída pela duração dos itens ativos e pelo número de profissionais habilitados para o serviço; deve ser refinada com disponibilidade individual e reservas futuras.
- Docker, Laravel, Vue/Inertia, PostgreSQL e Redis.
- Ambiente integralmente conteinerizado, inclusive instalação de dependências PHP/Node, execução do Vite, worker Horizon e agendador Laravel. PostgreSQL e Redis operam somente na rede interna dos containers.
- Autenticação, barbearia, usuários e perfis.
- Layout base, permissões e auditoria essencial.
- Manifesto PWA, ícones, instalação pelo navegador e cache de recursos estáticos.

### Fase 1 — MVP operacional

- Profissionais, serviços, horários e bloqueios.
- Agenda interna e prevenção de conflito.
- Agendamento público por link (fluxo inicial implementado; ampliar regras de negócio e concluir a cobertura dos critérios de aceite).
- Clientes e histórico básico.
- Fila presencial e previsão de atendimento.
- Atendimento, pagamentos e caixa simples.
- Identidade visual personalizável por barbearia, shell de navegação responsivo e feedback toast acessível.
- Guias operacionais separados por público e atualizados junto a alterações de rotas, permissões e fluxos.

**Ainda pendente nesta fase:** matriz completa de permissões por função, recuperação de senha, cadastro público de conta do cliente, cancelamento e reagendamento pelo portal. A agenda individual básica do profissional foi implementada como consulta dos próprios horários; conta demo do cliente oferece apenas consulta dos próprios agendamentos.

**Escopo já entregue nesta fase:** registro de múltiplos pagamentos por atendimento, despesas categorizadas, resumo diário e relatórios iniciais por período/profissional/serviço. Ainda faltam fechamento de caixa, estornos auditáveis e comparações analíticas avançadas.

### Fase 2 — Automação e fidelização

- Integração oficial do WhatsApp.
- Confirmação e lembretes automáticos.
- Regras de benefício, incluindo pezinho grátis.
- Indicadores básicos e exportação de dados.

### Fase 3 — Gestão ampliada

- Comissões e repasses por profissional.
- Estoque, consumo de produtos e alertas de reposição.
- Equipamentos, manutenção e garantia.
- Lista de espera, sinal via Pix, fidelidade, cupons e campanhas segmentadas.

### Fase 4 — Inteligência e escala

- Chatbot para linguagem natural no WhatsApp.
- Sugestões de horários e recuperação de clientes inativos.
- Multiunidade, assinaturas e planos recorrentes.
- Aplicativo/PWA para profissionais e indicadores avançados.

## 11. Critérios de aceite do MVP

1. Uma recepcionista cria um agendamento sem permitir conflito de profissional.
2. Um cliente agenda pelo link público e recebe confirmação.
3. Um cliente sem horário entra na fila e possui previsão de atendimento.
4. Um profissional chama o próximo cliente elegível e inicia o atendimento.
5. O atendimento permite registrar um ou mais pagamentos.
6. O histórico do cliente reúne agenda, fila, atendimentos e pagamentos.
7. Lembretes só são programados com consentimento, são idempotentes e permitem registrar envio manual sem alegar automação do WhatsApp.
8. Usuários de uma barbearia não conseguem consultar nem alterar dados de outra.
9. As telas operacionais funcionam plenamente em 320 px, enquanto relatórios adaptam gráficos e tabelas sem ocultar dados.
10. O sistema pode ser instalado pelo navegador compatível em celular e computador, abrindo em modo de aplicativo com nome e ícone da marca.
11. O proprietário consegue trocar/remover a logo e cores da própria barbearia, sem afetar outra empresa e mantendo contraste de texto legível.
12. Feedback operacional aparece em notificação acessível, enquanto erros específicos de formulário continuam visíveis junto aos campos.

## 12. Itens deliberadamente fora do primeiro lançamento

- Chatbot com IA e compreensão de linguagem natural.
- App nativo para Android/iOS.
- Marketplace público de barbearias.
- Estoque avançado e manutenção completa de equipamentos.
- Comissões complexas, conciliação bancária e contabilidade.
- Pagamento obrigatório de sinal online.

Esses itens devem ser reavaliados após validar a rotina de agendamento, fila e financeiro básico com barbearias reais.

## Painel administrativo global da plataforma

Além do painel de cada barbearia, o SaaS terá uma área `/admin` separada, destinada exclusivamente a administradores da plataforma e ao suporte operacional. A fundação de autenticação da conta global e a tela inicial de desenvolvimento foram criadas; o acesso ainda é bloqueado fora de `local`/`testing` e não representa o painel operacional completo.

### Funcionalidades

- **Visão geral e saúde:** estado de aplicação, banco, Redis, workers e agendador; jobs pendentes/falhos; armazenamento e ocorrências recentes.
- **Logs e auditoria:** pesquisa paginada e filtrável por nível, serviço, período e identificador de requisição; eventos administrativos auditáveis. Dados pessoais e segredos devem ser redigidos. Não haverá console de comandos, SQL ou leitura arbitrária de arquivos.
- **Integrações:** cadastro e estado de WhatsApp, armazenamento S3, pagamentos e conectores futuros; exibir última verificação, resultado e falhas relevantes.
- **Chaves e tokens:** cadastrar, testar, rotacionar e revogar credenciais. Armazenar cifras no servidor, mostrar somente provedor, rótulo, estado e valor mascarado; nunca retornar o segredo gravado à interface. Auditar cada alteração sem incluir o segredo.
- **Configurações globais:** parâmetros operacionais não secretos, limites e controles de funcionalidades, protegidos por permissões e trilha de alterações.
- **Alertas:** fila parada, falhas repetidas, serviço indisponível ou integração desconectada, sem expor conteúdo sensível.

### Segurança e entidades

O acesso administrativo é global, não pertence a uma barbearia e não deve herdar nem aceitar `barbearia_id` da sessão da equipe. Usa guarda e tabela próprias para autenticação demo, proteção CSRF e limitação de tentativas; o painel e o login são restritos a `local`/`testing` até haver autenticação reforçada, auditoria e autorização global de produção. Segredos usam criptografia em repouso do Laravel; em produção, preferir cofre de segredos gerenciado.

Entidades: `AdministradorDaPlataforma` (guard/tabela de autenticação demo implementados), `IntegracaoDaPlataforma`, `CredencialDeIntegracao` e `EventoDeAuditoria` (itens futuros). Segredos e dados pessoais devem ser excluídos de logs, exceções, dumps e dados de teste.

### Critérios de aceite

1. Usuário de barbearia não acessa rotas administrativas globais.
2. Administrador consulta saúde e logs redigidos com filtros e paginação.
3. Credenciais podem ser cadastradas, verificadas, rotacionadas e revogadas sem revelar um segredo já salvo.
4. Alterações administrativas geram auditoria sem valores secretos.
5. Alertas de serviços e integrações não expõem dados confidenciais.
