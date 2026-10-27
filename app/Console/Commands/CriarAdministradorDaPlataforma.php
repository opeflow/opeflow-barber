<?php

namespace App\Console\Commands;

use App\Models\AdministradorDaPlataforma;
use App\Models\EventoDeAuditoria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CriarAdministradorDaPlataforma extends Command
{
    protected $signature = 'administracao:criar-administrador {--nome=} {--email=}';

    protected $description = 'Provisiona uma conta global inicial sem expor a senha em argumentos do terminal';

    public function handle(): int
    {
        $nome = trim((string) ($this->option('nome') ?: $this->ask('Nome do administrador')));
        $email = mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('E-mail do administrador'))));

        if ($nome === '' || mb_strlen($nome) > 120 || ! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $this->components->error('Informe nome e e-mail válidos.');

            return self::FAILURE;
        }

        if (AdministradorDaPlataforma::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            $this->components->error('Já existe um administrador com esse e-mail.');

            return self::FAILURE;
        }

        $senha = (string) $this->secret('Senha (mínimo 16 caracteres)');
        $confirmacao = (string) $this->secret('Confirme a senha');
        if (mb_strlen($senha) < 16 || ! hash_equals($senha, $confirmacao)) {
            $this->components->error('Senha curta ou confirmação diferente. Nenhuma conta foi criada.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($nome, $email, $senha): void {
            $administrador = AdministradorDaPlataforma::query()->create([
                'nome' => $nome,
                'email' => $email,
                'senha' => $senha,
                'ativo' => true,
            ]);
            EventoDeAuditoria::query()->create([
                'administrador_da_plataforma_id' => null,
                'acao' => 'administrador.provisionado',
                'nivel' => 'info',
                'servico' => 'linha_de_comando',
                'descricao_redigida' => 'Conta administrativa provisionada por procedimento de linha de comando.',
                'metadados' => ['alvo_id' => $administrador->id],
                'created_at' => now('UTC'),
            ]);
        });

        $this->components->info('Administrador provisionado. No primeiro acesso em produção, configure MFA e guarde os códigos de recuperação.');

        return self::SUCCESS;
    }
}
