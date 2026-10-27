# Acessos de demonstração no desenvolvimento

O `DesenvolvimentoSeeder` prepara uma barbearia, um serviço, um barbeiro, um cliente com agendamento de exemplo e quatro contas para experimentar os perfis existentes e os acessos separados de demonstração.

## Criar ou atualizar os dados

Na raiz do projeto, execute pelos containers:

```powershell
docker compose exec aplicacao php artisan migrate --seed
```

O seeder pode ser executado novamente sem duplicar as contas. Ele só roda nos ambientes `local` e `testing`; não executa em produção. As senhas são armazenadas com hash.
Em desenvolvimento, a confirmação de conta cliente é pré-aprovada pelo seeder. Novas contas recebem e-mail de confirmação no Mailpit, acessível em `http://localhost:8025`.

## Contas padrão

| Perfil | E-mail | Senha | Entrada |
| --- | --- | --- | --- |
| Proprietário da barbearia demo | `dono@opeflow.local` | `OpeflowDev2026!` | `/entrar` |
| Barbeiro/profissional demo | `barbeiro@opeflow.local` | `OpeflowDev2026!` | `/entrar` |
| Cliente demo | `cliente@opeflow.local` | `OpeflowDev2026!` | `/cliente/entrar` |
| Administrador global demo | `admin@opeflow.local` | `OpeflowDev2026!` | `/admin/entrar` |

A barbearia de demonstração também tem o link público `/agendar/opeflow-demo`.

## Limites importantes

- As credenciais são públicas e fixas para desenvolvimento. Nunca as reutilize, publique como contas de produção ou habilite este seed em produção.
- O cliente demo pode consultar e cancelar apenas seus próprios horários futuros ainda não iniciados. Benefícios reservados válidos são liberados e lembretes pendentes cancelados junto à reserva. Reagendamento ainda não está disponível; recuperação de senha usa o e-mail de desenvolvimento do Mailpit.
- O portal de cliente está habilitado fora de `local`/`testing`, mas exige conta verificada por e-mail; configure SMTP antes de publicar.
- O login de administrador permanece restrito a desenvolvimento. A tela exibe indicadores agregados e checagens de saúde, mas logs filtrados, integrações, segredos e auditoria administrativa não foram implementados. Não habilite o painel global em produção antes de MFA e controles de autorização/auditoria.
- O administrador global não pertence a uma barbearia. A conta do cliente é vinculada a um `Cliente`; as contas do dono e do barbeiro continuam no provedor de equipe da barbearia.
