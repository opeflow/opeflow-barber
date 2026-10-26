<?php

use App\Enums\PapelDeUsuario;
use App\Models\Barbearia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->barbearia = Barbearia::create([
        'nome' => 'Navalha Azul',
        'slug' => 'navalha-azul',
        'ativa' => true,
    ]);
    $this->proprietario = Usuario::create([
        'barbearia_id' => $this->barbearia->id,
        'nome' => 'Proprietário',
        'email' => 'dono@navalha.local',
        'senha' => 'senha-de-teste-segura',
        'papel' => PapelDeUsuario::Proprietario,
        'ativo' => true,
    ]);
});

it('permite ao proprietário personalizar cores e enviar uma logo isolada na própria barbearia', function (): void {
    Storage::fake('public');
    $imagem = UploadedFile::fake()->createWithContent(
        'marca.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'),
    );

    $this->actingAs($this->proprietario)->post('/configuracoes/identidade-visual', [
        'cor_primaria' => '#B42318',
        'cor_navegacao' => '#14213D',
        'logo' => $imagem,
    ])->assertRedirect()->assertSessionHas('sucesso');

    $this->barbearia->refresh();
    expect($this->barbearia->cor_primaria)->toBe('#B42318')
        ->and($this->barbearia->cor_navegacao)->toBe('#14213D')
        ->and($this->barbearia->logo_path)->toStartWith('barbearias/'.$this->barbearia->id.'/');

    Storage::disk('public')->assertExists($this->barbearia->logo_path);
    $this->get('/barbearia/logo')->assertOk()->assertHeader('content-type', 'image/png');
    $this->get('/')->assertInertia(fn ($pagina) => $pagina
        ->where('usuarioPainel.corPrimaria', '#B42318')
        ->where('usuarioPainel.corNavegacao', '#14213D')
        ->where('usuarioPainel.logoUrl', route('barbearia.logo')));
});

it('não permite que um profissional altere a identidade visual da barbearia', function (): void {
    $profissional = Usuario::create([
        'barbearia_id' => $this->barbearia->id,
        'nome' => 'Barbeiro',
        'email' => 'barbeiro@navalha.local',
        'senha' => 'senha-de-teste-segura',
        'papel' => PapelDeUsuario::Profissional,
        'ativo' => true,
    ]);

    $this->actingAs($profissional)->post('/configuracoes/identidade-visual', [
        'cor_primaria' => '#B42318',
        'cor_navegacao' => '#14213D',
    ])->assertForbidden();

    expect($this->barbearia->fresh()->cor_primaria)->toBe('#2563EB');
});

it('valida o formato das cores antes de atualizar a marca', function (): void {
    $this->actingAs($this->proprietario)->from('/configuracoes/operacao')->post('/configuracoes/identidade-visual', [
        'cor_primaria' => 'azul',
        'cor_navegacao' => '#123',
    ])->assertSessionHasErrors(['cor_primaria', 'cor_navegacao']);

    expect($this->barbearia->fresh()->cor_primaria)->toBe('#2563EB');
});
