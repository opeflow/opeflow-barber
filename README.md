# Opeflow Barber

Sistema de gestão para barbearias, desenvolvido com Laravel, Vue/Inertia, PostgreSQL e Redis.

## Ambiente local com Docker

Os requisitos da máquina são somente Docker Desktop e Docker Compose. PHP, Composer, Node, PostgreSQL e Redis são executados em containers.

1. Crie o arquivo de ambiente:

   ```powershell
   Copy-Item .env.example .env
   ```

2. Construa as imagens e inicialize dependências, chave da aplicação e banco:

   ```powershell
   docker compose build
   docker compose --profile inicializacao run --rm inicializar
   ```

3. Suba a aplicação, Vite, filas e agendador:

   ```powershell
   docker compose up -d
   ```

Abra `http://localhost:8000`. O Vite fica disponível na porta `5173` para atualização durante o desenvolvimento.

## Guias de uso

- [Proprietário da barbearia](docs/guia-proprietario.md)
- [Barbeiro e equipe](docs/guia-equipe.md)
- [Cliente](docs/guia-cliente.md)

O painel interno está em desenvolvimento. O guia do cliente deixa explícito que login, portal de cliente e reserva pública ainda não estão disponíveis.

## Serviços

| Serviço | Endereço | Responsabilidade |
| --- | --- | --- |
| `aplicacao` | `http://localhost:8000` | Laravel/PHP |
| `frontend` | `http://localhost:5173` | Vite, Vue e Tailwind |
| `banco` | rede Docker interna | PostgreSQL |
| `redis` | rede Docker interna | Cache e filas |
| `fila` | — | Laravel Horizon e jobs em segundo plano |
| `agendador` | — | Lembretes e tarefas programadas |

Para acompanhar os logs, use `docker compose logs -f aplicacao frontend fila agendador`.

## Testes automatizados

Os testes rodam no container PHP e usam o banco isolado `opeflow_barber_test`, separado do banco local da aplicação:

```powershell
docker compose exec aplicacao php artisan test
```

Em uma instalação nova, o banco de testes é criado pelo script de inicialização do PostgreSQL. Ele nunca deve apontar para `opeflow_barber`.
