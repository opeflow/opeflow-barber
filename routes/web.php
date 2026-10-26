<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AgendamentoPublicoController;
use App\Http\Controllers\AutenticacaoController;
use App\Http\Controllers\CadastroOperacionalController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\EquipeController;
use App\Http\Controllers\FilaController;
use App\Http\Controllers\LembretesController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PainelController;
use App\Http\Controllers\RelatoriosController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/criar-conta', [OnboardingController::class, 'formulario'])->name('criar-conta');
    Route::post('/criar-conta', [OnboardingController::class, 'criar'])->name('registrar-conta');
    Route::get('/entrar', [AutenticacaoController::class, 'formulario'])->name('entrar');
    Route::post('/entrar', [AutenticacaoController::class, 'entrar'])->name('autenticar');
});

Route::get('/agendar/{barbearia:slug}/logo', [AgendamentoPublicoController::class, 'exibirLogoPublico'])->name('agendamento.publico.logo');
Route::get('/agendar/{barbearia:slug}', [AgendamentoPublicoController::class, 'exibir'])->middleware('throttle:60,1')->name('agendamento.publico');
Route::post('/agendar/{barbearia:slug}', [AgendamentoPublicoController::class, 'criar'])->middleware('throttle:10,1')->name('agendamento.publico.criar');
Route::get('/convites/equipe', [EquipeController::class, 'formularioDeAceite'])->middleware('throttle:30,1')->name('equipe.aceitar');
Route::post('/convites/equipe/validar', [EquipeController::class, 'validarConvite'])->middleware('throttle:10,1')->name('equipe.validar-convite');
Route::post('/convites/equipe/ativar', [EquipeController::class, 'aceitar'])->middleware('throttle:10,1')->name('equipe.aceitar.salvar');

Route::middleware('auth')->group(function (): void {
    Route::get('/', [PainelController::class, 'exibir'])->name('inicio');
    Route::post('/sair', [AutenticacaoController::class, 'sair'])->name('sair');
    Route::get('/barbearia/logo', [CadastroOperacionalController::class, 'exibirLogo'])->name('barbearia.logo');

    Route::get('/clientes', [ClientesController::class, 'exibir'])->name('clientes.exibir');
    Route::post('/clientes', [ClientesController::class, 'criar'])->name('clientes.criar');
    Route::post('/clientes/{cliente}/retirar-consentimento', [ClientesController::class, 'retirarConsentimento'])->name('clientes.retirar-consentimento');

    Route::get('/lembretes', [LembretesController::class, 'exibir'])->name('lembretes.exibir');
    Route::post('/lembretes/{lembrete}/marcar-enviado', [LembretesController::class, 'marcarEnviado'])->name('lembretes.marcar-enviado');

    Route::get('/fila', [FilaController::class, 'exibir'])->name('fila.exibir');
    Route::post('/fila', [FilaController::class, 'adicionar'])->name('fila.adicionar');
    Route::post('/fila/chamar-proximo', [FilaController::class, 'chamarProximo'])->name('fila.chamar-proximo');
    Route::post('/fila/{item}/iniciar', [FilaController::class, 'iniciarAtendimento'])->name('fila.iniciar');
    Route::post('/fila/{item}/concluir', [FilaController::class, 'concluirAtendimento'])->name('fila.concluir');
    Route::post('/fila/{item}/desistir', [FilaController::class, 'desistir'])->name('fila.desistir');
    Route::post('/atendimentos/{atendimento}/pagamentos', [FinanceiroController::class, 'registrarPagamento'])->name('pagamentos.registrar');
    Route::get('/financeiro', [FinanceiroController::class, 'exibir'])->name('financeiro.exibir');
    Route::post('/financeiro/despesas', [FinanceiroController::class, 'registrarDespesa'])->name('financeiro.despesas.registrar');
    Route::get('/relatorios', [RelatoriosController::class, 'exibir'])->name('relatorios.exibir');
    Route::get('/relatorios/exportar', [RelatoriosController::class, 'exportar'])->name('relatorios.exportar');

    Route::get('/agenda', [AgendaController::class, 'exibir'])->name('agenda.exibir');
    Route::post('/agenda', [AgendaController::class, 'criar'])->name('agenda.criar');
    Route::delete('/agenda/{agendamento}', [AgendaController::class, 'cancelar'])->name('agenda.cancelar');
    Route::post('/agenda/{agendamento}/atendido', [AgendaController::class, 'marcarAtendido'])->name('agenda.atendido');
    Route::post('/agenda/{agendamento}/nao-compareceu', [AgendaController::class, 'marcarNaoCompareceu'])->name('agenda.nao-compareceu');

    Route::get('/configuracoes/operacao', [CadastroOperacionalController::class, 'exibir'])->name('configuracoes.operacao');
    Route::get('/configuracoes/equipe', [EquipeController::class, 'exibir'])->name('equipe.exibir');
    Route::post('/configuracoes/equipe/convites', [EquipeController::class, 'convidar'])->name('equipe.convidar');
    Route::post('/configuracoes/equipe/{usuario}/convite', [EquipeController::class, 'renovarConvite'])->name('equipe.renovar-convite');
    Route::patch('/configuracoes/equipe/{usuario}/acesso', [EquipeController::class, 'alterarAcesso'])->name('equipe.alterar-acesso');
    Route::post('/configuracoes/identidade-visual', [CadastroOperacionalController::class, 'atualizarIdentidade'])->name('configuracoes.identidade');
    Route::post('/configuracoes/beneficios', [CadastroOperacionalController::class, 'salvarRegraDeBeneficio'])->name('configuracoes.beneficios.salvar');
    Route::post('/configuracoes/profissionais', [CadastroOperacionalController::class, 'criarProfissional'])->name('profissionais.criar');
    Route::post('/configuracoes/servicos', [CadastroOperacionalController::class, 'criarServico'])->name('servicos.criar');
    Route::post('/configuracoes/profissionais/{profissional}/servicos', [CadastroOperacionalController::class, 'vincularServico'])->name('profissionais.servicos.vincular');
    Route::post('/configuracoes/profissionais/{profissional}/horarios', [CadastroOperacionalController::class, 'adicionarHorario'])->name('profissionais.horarios.adicionar');
    Route::post('/configuracoes/profissionais/{profissional}/horarios/{horario}/pausas', [CadastroOperacionalController::class, 'criarPausa'])->name('profissionais.horarios.pausas.criar');
    Route::delete('/configuracoes/profissionais/{profissional}/horarios/{horario}/pausas/{pausa}', [CadastroOperacionalController::class, 'removerPausa'])->name('profissionais.horarios.pausas.remover');
    Route::post('/configuracoes/profissionais/{profissional}/bloqueios', [CadastroOperacionalController::class, 'criarBloqueio'])->name('profissionais.bloqueios.criar');
    Route::delete('/configuracoes/profissionais/{profissional}/bloqueios/{bloqueio}', [CadastroOperacionalController::class, 'removerBloqueio'])->name('profissionais.bloqueios.remover');
});
