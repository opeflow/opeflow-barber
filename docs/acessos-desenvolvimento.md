# Acessos de demonstração no desenvolvimento

O `DesenvolvimentoSeeder` prepara uma barbearia, um serviço, um barbeiro, um cliente com agendamento de exemplo e quatro contas para experimentar os perfis existentes e os acessos separados de demonstração.

## Criar ou atualizar os dados

Na raiz do projeto, execute pelos containers:

```powershell
docker compose exec aplicacao php artisan migrate --seed
```

O seeder pode ser executado novamente sem duplicar as contas. Ele só roda nos ambientes `local` e `testing`; não executa em produção. As senhas são armazenadas com hash.

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
- O cliente demo pode consultar seus próprios agendamentos e abrir o link para criar outro. Cadastro público de conta, recuperação de senha, cancelamento e reagendamento não estão incluídos.
- O login de administrador é uma fundação isolada para desenvolvimento. O painel de logs, integrações, segredos e auditoria ainda não foi implementado. Rotas e contas de cliente/admin retornam 404 fora de `local`/`testing` até a conclusão da autenticação e dos controles de produção.
- O administrador global não pertence a uma barbearia. A conta do cliente é vinculada a um `Cliente`; as contas do dono e do barbeiro continuam no provedor de equipe da barbearia.
