<?php

namespace Tests\Feature;

use Tests\TestCase;

class ChatTest extends TestCase
{
    public function test_chat_page_is_accessible(): void
    {
        $response = $this->get('/chat');

        $response->assertStatus(200);
        $response->assertSee('DATAMIND');
        $response->assertSee('chatMessagesContainer');
    }

    public function test_chat_send_requires_prompt(): void
    {
        $response = $this->postJson('/chat/send', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    public function test_chat_stream_requires_prompt(): void
    {
        $response = $this->postJson('/chat/stream', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    public function test_chat_export_validates_dataset_id(): void
    {
        $response = $this->get('/chat/export/invalid@dataset!id');

        $response->assertStatus(400);
    }

    public function test_connections_page_is_accessible(): void
    {
        $response = $this->get('/connections');

        $response->assertStatus(200);
        $response->assertSee('Conectar Base de Datos');
        $response->assertSee('Explorador de Tablas Detectadas');
    }

    public function test_connections_test_validates_required_fields(): void
    {
        $response = $this->postJson('/connections/test', []);

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
        $response = $this->get('/chat/dataset/invalid@dataset!id');

        $response->assertStatus(400);
    }

    public function test_chat_sessions_list_endpoint_returns_json(): void
    {
        $response = $this->getJson('/chat/sessions');

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }
}
