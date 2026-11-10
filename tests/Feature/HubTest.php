<?php

namespace Tests\Feature;

use App\Models\Ecosystem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HubTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name' => 'Super Administrador',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('secret123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_hub_home_page_is_accessible(): void
    {
        $response = $this->get('/hub');

        $response->assertStatus(200);
        $response->assertSee('Inicio');
        $response->assertSee('Selecciona un módulo operativo para continuar');
    }

    public function test_hub_renders_seeded_ecosystems(): void
    {
        $this->seed(\Database\Seeders\EcosystemSeeder::class);

        $response = $this->get('/hub');

        $response->assertStatus(200);
        $response->assertSee('Configuración');
        $response->assertSee('Chat IA');
        $response->assertSee('Vectorización');
        $response->assertSee('Ingresar');
    }

    public function test_settings_page_is_accessible(): void
    {
        $this->seed(\Database\Seeders\EcosystemSeeder::class);

        $response = $this->actingAs($this->user)->get('/settings');

        $response->assertStatus(200);
        $response->assertSee('Ecosistema de Configuración');
        $response->assertSee('Paletas Corporativas Predefinidas');
        $response->assertSee('Tipografía y Tamaño de Texto');
    }
}
