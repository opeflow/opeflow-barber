<?php

namespace Database\Seeders;

use App\Enums\PapelDeUsuario;
use App\Models\AdministradorDaPlataforma;
use App\Models\Agendamento;
use App\Models\Barbearia;
use App\Models\Cliente;
use App\Models\ContaDeCliente;
use App\Models\HorarioDeTrabalho;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DesenvolvimentoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('O DesenvolvimentoSeeder só pode ser executado nos ambientes local e testing.');
        }

        DB::transaction(function (): void {
            $barbearia = Barbearia::updateOrCreate(
                ['slug' => 'opeflow-demo'],
                ['nome' => 'Barbearia Opeflow Demonstração', 'email' => 'contato@opeflow.local', 'fuso_horario' => 'America/Araguaina', 'ativa' => true],
            );

            $servico = Servico::updateOrCreate(
                ['barbearia_id' => $barbearia->id, 'nome' => 'Corte masculino'],
                ['descricao' => 'Corte tradicional para demonstração.', 'duracao_em_minutos' => 30, 'preco' => 45, 'ativo' => true],
            );

            $profissional = Profissional::updateOrCreate(
                ['barbearia_id' => $barbearia->id, 'nome' => 'Barbeiro Demonstração'],
                ['telefone' => '63999990002', 'ativo' => true],
            );
            $profissional->servicos()->syncWithoutDetaching([$servico->id]);

            foreach (range(0, 6) as $diaDaSemana) {
                HorarioDeTrabalho::updateOrCreate(
                    ['barbearia_id' => $barbearia->id, 'profissional_id' => $profissional->id, 'dia_da_semana' => $diaDaSemana],
                    ['inicio_as' => '09:00', 'fim_as' => '18:00', 'ativo' => true],
                );
            }

            Usuario::updateOrCreate(
                ['email' => 'dono@opeflow.local'],
                ['barbearia_id' => $barbearia->id, 'profissional_id' => null, 'nome' => 'Proprietário Demonstração', 'senha' => 'OpeflowDev2026!', 'papel' => PapelDeUsuario::Proprietario, 'ativo' => true, 'convite_aceito_em' => now('UTC')],
            );

            Usuario::updateOrCreate(
                ['email' => 'barbeiro@opeflow.local'],
                ['barbearia_id' => $barbearia->id, 'profissional_id' => $profissional->id, 'nome' => 'Barbeiro Demonstração', 'senha' => 'OpeflowDev2026!', 'papel' => PapelDeUsuario::Profissional, 'ativo' => true, 'convite_aceito_em' => now('UTC')],
            );

            $cliente = Cliente::updateOrCreate(
                ['barbearia_id' => $barbearia->id, 'telefone' => '63999990003'],
                ['nome' => 'Cliente Demonstração', 'email' => 'cliente@opeflow.local', 'aceita_lembretes' => false],
            );

            ContaDeCliente::updateOrCreate(
                ['email' => 'cliente@opeflow.local'],
                ['barbearia_id' => $barbearia->id, 'cliente_id' => $cliente->id, 'nome' => $cliente->nome, 'senha' => 'OpeflowDev2026!', 'ativo' => true],
            );

            AdministradorDaPlataforma::updateOrCreate(
                ['email' => 'admin@opeflow.local'],
                ['nome' => 'Administrador Demonstração', 'senha' => 'OpeflowDev2026!', 'ativo' => true],
            );

            $inicio = now($barbearia->fuso_horario)->addDays(2)->setTime(14, 0);
            Agendamento::updateOrCreate(
                ['barbearia_id' => $barbearia->id, 'chave_idempotencia_publica' => 'e0a1df5d-4c8d-4f82-a24c-fc3364cc6d12'],
                ['cliente_id' => $cliente->id, 'profissional_id' => $profissional->id, 'servico_id' => $servico->id, 'inicio_em' => $inicio->utc(), 'fim_em' => $inicio->addMinutes($servico->duracao_em_minutos)->utc(), 'status' => 'confirmado'],
            );
        });

        $this->command?->info('Contas de demonstração criadas/atualizadas. Consulte docs/acessos-desenvolvimento.md.');
    }
}
