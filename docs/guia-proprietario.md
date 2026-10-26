# Guia do proprietário da barbearia

## Acessar e criar a barbearia

1. Abra o endereço do sistema fornecido pela equipe responsável pela instalação.
2. Na primeira utilização, escolha **Criar conta** ou acesse `/criar-conta`.
3. Informe o nome da barbearia, seu nome, e-mail e uma senha com pelo menos oito caracteres. A primeira conta criada fica com o perfil de proprietário.
4. Nas próximas visitas, entre em `/entrar` usando o e-mail e a senha cadastrados. Para sair, use **Sair** no menu.

Não compartilhe a senha de proprietário. A criação de usuários e convites individuais para a equipe ainda está pendente; por isso, não crie acessos improvisados nem compartilhe uma sessão do proprietário.

## Configurar a operação

Abra **Mais → Operação** no celular ou **Operação** no menu lateral do computador (`/configuracoes/operacao`).

1. Em **Identidade visual**, escolha uma logo PNG, JPG ou WebP de até 2 MB e defina a cor principal e a cor da navegação. A prévia é imediata; clique em **Salvar identidade visual** para aplicar. O contraste do texto é calculado para preservar a leitura. Use **Remover logo** se quiser voltar ao símbolo padrão.
2. Copie o **Link de agendamento online** nesta página e divulgue-o no WhatsApp, Instagram ou perfil da barbearia. O cliente reserva sem criar conta; não há integração com o WhatsApp, então você envia o link usando suas próprias mensagens.
3. Cadastre cada serviço com nome, duração em minutos e preço.
4. Cadastre cada profissional.
5. Selecione o profissional, vincule os serviços que ele executa e cadastre seus horários semanais. Confira dia, abertura e fechamento antes de salvar. Dentro de cada turno, cadastre pausas recorrentes (por exemplo, almoço); o período não será oferecido para agendamento em nenhuma semana.
6. Em **Benefício de retorno**, escolha o serviço que gera o crédito (por exemplo, Corte), o serviço gratuito (por exemplo, Pezinho), após quantos dias ficará disponível e quando expirará. Ao concluir um atendimento elegível, o benefício aparece na ficha do cliente com um código de resgate. O cliente informa esse código e o mesmo telefone usado no cadastro no link público; o crédito fica reservado para o serviço e horário. Cancelar ou marcar falta libera o crédito novamente se ainda estiver no prazo; marcar o agendamento como atendido consome o benefício. Benefícios vencidos são expirados automaticamente.

Para dar acesso a cada pessoa, abra **Mais → Equipe** (`/configuracoes/equipe`), informe nome, e-mail e papel, e associe o cadastro de profissional quando for barbeiro. Copie o link gerado e envie em canal privado. O link expira em 48 horas e só pode ser usado uma vez; a pessoa cria a própria senha. Se precisar, gere outro convite para invalidar o anterior ou desative o acesso de alguém.

Os horários e pausas são recorrentes por dia da semana. Uma pausa precisa ficar dentro do turno e não pode se sobrepor a outra pausa. Para uma folga ou indisponibilidade pontual, cadastre um bloqueio com início e fim. O sistema impede bloqueios que conflitem com reservas ou atendimentos em andamento e remove o intervalo da disponibilidade do agendamento online. Você pode remover um bloqueio futuro para liberar novamente o período. O calendário de feriados ainda não está disponível.

## Rotina diária

- **Clientes:** em `/clientes`, procure por nome ou telefone. Cadastre o cliente antes de criar um agendamento ou colocá-lo na fila. Marque consentimento para lembretes somente quando autorizado pelo cliente; é possível retirar a autorização no cadastro, o que cancela lembretes pendentes.
- **Agenda:** em `/agenda`, escolha a data, clique em **Novo agendamento**, selecione cliente, profissional e serviço e confira a data e a hora. O sistema impede conflito para o profissional.
- **Fila presencial:** em `/fila`, registre quem chegou sem reserva. Use **Chamar próximo**, depois **Iniciar atendimento** quando a pessoa se apresentar. A previsão é aproximada.
- **Pagamento:** durante o atendimento, registre um ou mais pagamentos (Pix, dinheiro, crédito, débito ou outro). O sistema não deixa receber acima do saldo. Depois, conclua o atendimento.
- **Financeiro:** em `/financeiro`, escolha uma data para conferir recebimentos, despesas categorizadas e saldo operacional (receitas menos despesas). Use **Registrar despesa** para lançar uma saída com descrição, categoria, valor, data e hora local.
- **Relatórios:** em `/relatorios`, selecione o período para comparar recebimentos, despesas, saldo, ticket médio, receita por serviço/profissional, cancelamentos e faltas. Use **Exportar CSV** para baixar as movimentações do intervalo.

**Lembretes:** em `/lembretes`, consulte avisos 24h e 2h antes de horários e avisos de benefícios. Com consentimento do cliente, abra o WhatsApp para revisar e enviar a mensagem. Depois, registre o envio manualmente. O sistema não conecta à API nem confirma entrega; cancelar o agendamento cancela lembretes pendentes.

O benefício de retorno pode ser resgatado no link público, mas não possui histórico detalhado de utilização. Fechamento formal de caixa e estornos auditáveis ainda não estão disponíveis. Pagamento parcial é registrado, mas o sistema não obriga quitar o saldo antes de concluir. Despesas lançadas não podem ser editadas ou removidas nesta versão; confira os dados antes de registrar.

## Instalar no celular ou computador

Em um navegador compatível, abra o menu do navegador e escolha **Instalar aplicativo** ou **Adicionar à tela inicial**. Em produção, a instalação exige HTTPS. No ambiente local, use `localhost`; instalação e notificações variam conforme navegador e sistema.
