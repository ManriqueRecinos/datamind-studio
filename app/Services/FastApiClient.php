<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FastApiClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;

    public function __construct()
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }
        $this->baseUrl = rtrim(config('services.fastapi.url', 'http://127.0.0.1:8000/api/v1'), '/');
        $this->apiKey = config('services.fastapi.key', 'datamind_api_secret_token_2026');
        $this->timeout = 120;
    }

    /**
     * Envía una consulta síncrona al microservicio FastAPI.
     *
     * @param string $prompt
     * @param string|null $sessionId
     * @param string|null $connectionId
     * @return array
     */
    public function sendMessage(string $prompt, ?string $sessionId = null, ?string $connectionId = null, string $userId = 'user'): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/chat", array_filter([
                    'prompt' => $prompt,
                    'session_id' => $sessionId,
                    'connection_id' => $connectionId,
                    'user_id' => $userId,
                ]));

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FastAPI error response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Error al comunicarse con el motor de IA (' . $response->status() . ').',
            ];
        } catch (\Throwable $e) {
            Log::error('FastAPI connection exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'No se pudo conectar con el servicio local de IA: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Transmite el flujo de eventos SSE desde FastAPI hacia el cliente HTTP en tiempo real.
     *
     * @param string $prompt
     * @param string|null $sessionId
     * @param string|null $connectionId
     * @return StreamedResponse
     */
    public function streamChat(string $prompt, ?string $sessionId = null, ?string $connectionId = null, string $userId = 'user'): StreamedResponse
    {
        $client = new GuzzleClient([
            'timeout' => 120,
            'read_timeout' => 120,
        ]);

        return response()->stream(function () use ($client, $prompt, $sessionId, $connectionId, $userId) {
            // Deshabilitar buffers de salida PHP
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            try {
                $response = $client->post("{$this->baseUrl}/chat/stream", [
                    'headers' => [
                        'Authorization' => "Bearer {$this->apiKey}",
                        'Accept' => 'text/event-stream',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => array_filter([
                        'prompt' => $prompt,
                        'session_id' => $sessionId,
                        'connection_id' => $connectionId,
                        'user_id' => $userId,
                    ]),
                    'stream' => true,
                ]);

                $body = $response->getBody();

                while (!$body->eof()) {
                    $chunk = $body->read(256);
                    echo $chunk;
                    flush();
                }
            } catch (\Throwable $e) {
                Log::error('Error en stream de chat FastAPI', ['error' => $e->getMessage()]);
                $errorPayload = json_encode(['error' => $e->getMessage()]);
                echo "event: error\ndata: {$errorPayload}\n\n";
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Descarga el reporte Excel (.xlsx) generado a partir de un dataset temporal.
     *
     * @param string $datasetId
     * @return \Illuminate\Http\Client\Response
     */
    public function downloadExcel(string $datasetId)
    {
        return Http::withToken($this->apiKey)
            ->timeout(30)
            ->get("{$this->baseUrl}/export/excel/{$datasetId}");
    }

    /**
     * Obtiene las filas completas y configuración de columnas de un dataset en caché para construir gráficos.
     *
     * @param string $datasetId
     * @return array
     */
    public function getDatasetJson(string $datasetId): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(20)
                ->get("{$this->baseUrl}/export/dataset/{$datasetId}");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json('detail') ?? 'No se encontró el dataset solicitado.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Lista el historial de sesiones de conversación del usuario autenticado.
     *
     * @param string $userId
     * @return array
     */
    public function listChatSessions(string $userId): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->get("{$this->baseUrl}/chat/sessions", ['user_id' => $userId]);

            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Throwable $e) {
            Log::warning('Error listando sesiones de chat desde FastAPI', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtiene el detalle y mensajes de una sesión de chat específica.
     *
     * @param string $sessionId
     * @param string $userId
     * @return array
     */
    public function getChatSessionDetail(string $sessionId, string $userId): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(20)
                ->get("{$this->baseUrl}/chat/sessions/{$sessionId}", ['user_id' => $userId]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json('detail') ?? 'No se encontró la conversación.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Elimina una sesión del historial de conversaciones del usuario.
     *
     * @param string $sessionId
     * @param string $userId
     * @return array
     */
    public function deleteChatSession(string $sessionId, string $userId): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->delete("{$this->baseUrl}/chat/sessions/{$sessionId}?user_id=" . urlencode($userId));

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json('detail') ?? 'No se pudo eliminar la conversación.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verifica la disponibilidad del servicio FastAPI.
     *
     * @return bool
     */
    public function checkHealth(): bool
    {
        try {
            $rootUrl = preg_replace('#/api/v1$#', '', $this->baseUrl);
            $response = Http::timeout(3)->get("{$rootUrl}/health");
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Obtiene la lista de conexiones de bases de datos registradas.
     *
     * @return array
     */
    public function listConnections(): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->get("{$this->baseUrl}/connections");

            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Throwable $e) {
            Log::warning('Error listando conexiones desde FastAPI', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Prueba en vivo credenciales de una conexión PostgreSQL o MariaDB/MySQL.
     *
     * @param array $payload
     * @return array
     */
    public function testConnection(array $payload): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(15)
            ->post("{$this->baseUrl}/connections/test", $payload);

        if ($response->successful()) {
            return ['success' => true, 'data' => $response->json()];
        }

        return [
            'success' => false,
            'message' => $response->json('detail') ?? 'Error al probar la conexión (' . $response->status() . ').',
        ];
    }

    /**
     * Registra una nueva conexión de base de datos.
     *
     * @param array $payload
     * @return array
     */
    public function createConnection(array $payload): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(20)
            ->post("{$this->baseUrl}/connections", $payload);

        if ($response->successful()) {
            return ['success' => true, 'data' => $response->json()];
        }

        return [
            'success' => false,
            'message' => $response->json('detail') ?? 'No se pudo guardar la conexión.',
        ];
    }

    /**
     * Activa una conexión de base de datos como predeterminada para el Chat.
     *
     * @param string $connectionId
     * @return array
     */
    public function activateConnection(string $connectionId): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(15)
            ->post("{$this->baseUrl}/connections/{$connectionId}/activate");

        if ($response->successful()) {
            return ['success' => true, 'data' => $response->json()];
        }

        return [
            'success' => false,
            'message' => $response->json('detail') ?? 'No se pudo activar la conexión.',
        ];
    }

    /**
     * Obtiene todas las tablas detectadas y su estado de vectorización para una conexión.
     *
     * @param string $connectionId
     * @return array
     */
    public function getConnectionTables(string $connectionId): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->get("{$this->baseUrl}/connections/{$connectionId}/tables");

        if ($response->successful()) {
            return ['success' => true, 'data' => $response->json()];
        }

        return [
            'success' => false,
            'message' => $response->json('detail') ?? 'No se pudieron obtener las tablas de la base de datos.',
        ];
    }

    /**
     * Vectoriza en pgvector el conjunto de tablas seleccionadas por el usuario.
     *
     * @param string $connectionId
     * @param array $tables
     * @return array
     */
    public function vectorizeTables(string $connectionId, array $tables): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(180)
            ->post("{$this->baseUrl}/connections/{$connectionId}/vectorize", [
                'tables' => array_values($tables),
            ]);

        if ($response->successful()) {
            return ['success' => true, 'data' => $response->json()];
        }

        return [
            'success' => false,
            'message' => $response->json('detail') ?? 'Error durante la vectorización de tablas.',
        ];
    }

    /**
     * Elimina una conexión y sus vectores de esquema asociados.
     *
     * @param string $connectionId
     * @return array
     */
    public function deleteConnection(string $connectionId): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(20)
            ->delete("{$this->baseUrl}/connections/{$connectionId}");

        if ($response->successful()) {
            return ['success' => true, 'data' => $response->json()];
        }

        return [
            'success' => false,
            'message' => $response->json('detail') ?? 'No se pudo eliminar la conexión.',
        ];
    }

    /**
     * Lista las reglas, relaciones manuales, fórmulas y ejemplos SQL aprendidos por la IA.
     */
    public function listKnowledgeRules(?string $connectionId = null): array
    {
        try {
            $query = $connectionId ? ['connection_id' => $connectionId] : [];
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->get("{$this->baseUrl}/knowledge/rules", $query);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }
            return ['success' => false, 'message' => 'No se pudieron obtener las reglas aprendidas.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Enseña y vectoriza una cápsula de conocimiento en pgvector.
     */
    public function trainKnowledgeRule(array $payload): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post("{$this->baseUrl}/knowledge/train", $payload);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json('detail') ?? 'No se pudo guardar la enseñanza en la IA.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Elimina una regla o cápsula de entrenamiento de pgvector.
     */
    public function deleteKnowledgeRule(string $ruleId): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->delete("{$this->baseUrl}/knowledge/rules/{$ruleId}");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json('detail') ?? 'No se pudo eliminar la regla.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
