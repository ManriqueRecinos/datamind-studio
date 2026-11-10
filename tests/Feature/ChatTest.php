<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
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

    public function test_chat_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get('/chat');

        $response->assertStatus(200);
        $response->assertSee('DATAMIND');
        $response->assertSee('chatMessagesContainer');
    }

    public function test_chat_send_requires_prompt(): void
    {
        $response = $this->actingAs($this->user)->postJson('/chat/send', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    public function test_chat_stream_requires_prompt(): void
    {
        $response = $this->actingAs($this->user)->postJson('/chat/stream', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    public function test_chat_export_validates_dataset_id(): void
    {
        $response = $this->actingAs($this->user)->get('/chat/export/invalid@dataset!id');

        $response->assertStatus(400);
    }

    public function test_connections_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get('/connections');

        $response->assertStatus(200);
        $response->assertSee('Conectar Base de Datos');
        $response->assertSee('Explorador de Tablas Detectadas');
    }

    public function test_connections_test_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson('/connections/test', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['engine', 'host', 'port', 'database_name', 'username']);
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Iniciar Sesión');
        $response->assertSee('Crear Cuenta');
    }

    public function test_chat_dataset_validates_dataset_id(): void
    {
        $response = $this->actingAs($this->user)->get('/chat/dataset/invalid@dataset!id');

        $response->assertStatus(400);
    }

    public function test_chat_sessions_list_endpoint_returns_json(): void
    {
        $response = $this->actingAs($this->user)->getJson('/chat/sessions');

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }
}
