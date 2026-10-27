# Guia do cliente

## Como marcar ou consultar um horário nesta versão

O agendamento público para novas reservas está disponível pelo link oficial compartilhado pela barbearia. Não é necessário criar conta para reservar. Para criar acesso ao portal, use `/cliente/cadastro` com o código da barbearia e o mesmo e-mail já registrado pela equipe; confirme o link recebido por e-mail antes de entrar em `/cliente/entrar`. O envio depende da configuração de e-mail pela plataforma.

Abra o link oficial, escolha serviço, profissional (opcional), data e horário, informe nome e telefone e confirme. Os horários são revalidados no envio; se alguém reservar antes, escolha outro. Se não tiver o link, peça-o pelo canal oficial. Se chegar sem marcar, a recepção pode cadastrar sua presença na fila; a previsão é aproximada.

Para testar e-mails de confirmação no ambiente local, abra o Mailpit em `http://localhost:8025`. Em produção, a barbearia/plataforma deve configurar um servidor SMTP válido; sem ele, não será possível confirmar novos acessos.

No agendamento, você pode opcionalmente autorizar lembretes de horário e benefícios pelo WhatsApp. A equipe prepara a mensagem e faz o envio manual; o sistema não envia mensagens automaticamente. A autorização é opcional.

No portal, horários futuros ainda não iniciados podem ser reagendados. Informe a nova data e hora; serviço e barbeiro são mantidos, e a disponibilidade é confirmada antes da gravação. O horário anterior permanece no histórico. Se houver benefício reservado, a nova data precisa continuar dentro da validade dele. Cancelamentos e reagendamentos exigem conexão com a internet.

## Seus dados

Seus dados de contato podem ser cadastrados pela equipe para organizar sua reserva ou atendimento. Informe somente os dados necessários e avise a barbearia se encontrar algum erro. Não envie senha, código bancário ou documentos pelo chat para conseguir um horário.

## Acesso e funcionalidades planejadas

No ambiente local, use também as credenciais publicadas em [Acessos de desenvolvimento](./acessos-desenvolvimento.md). O link de verificação expira e só deve ser aberto pelo titular do e-mail. Na tela de acesso, use **Esqueci minha senha** para receber um link de uso único que expira em 60 minutos. Sem internet, a tela offline pode mostrar somente um resumo previamente salvo; ele pode estar desatualizado e não confirma disponibilidade.
