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
        $this->timeout = (int) config('services.fastapi.timeout', 120);
    }

    /**
     * Envía una consulta síncrona al microservicio FastAPI.
     *
     * @param string $prompt
     * @param string|null $sessionId
     * @param string|null $connectionId
     * @return array
     */
    public function sendMessage(
        string $prompt,
        ?string $sessionId = null,
        ?string $connectionId = null,
        string $userId = 'user',
        string $userRole = 'user',
        array $userPermissions = []
    ): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/chat", array_filter([
                    'prompt' => $prompt,
                    'session_id' => $sessionId,
                    'connection_id' => $connectionId,
                    'user_id' => $userId,
                    'user_role' => $userRole,
                    'user_permissions' => $userPermissions,
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
     * @param string $userId
     * @param string|null $retryMessageId
     * @return StreamedResponse
     */
    public function streamChat(
        string $prompt,
        ?string $sessionId = null,
        ?string $connectionId = null,
        string $userId = 'user',
        ?string $retryMessageId = null,
        array $attachmentIds = []
    ): StreamedResponse
    {
        $client = new GuzzleClient([
            'timeout' => 240,
            'read_timeout' => 240,
        ]);

        return response()->stream(function () use ($client, $prompt, $sessionId, $connectionId, $userId, $retryMessageId, $attachmentIds) {
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
                        'retry_message_id' => $retryMessageId,
                        'attachment_ids' => !empty($attachmentIds) ? $attachmentIds : null,
                    ], fn($val) => !is_null($val)),
                    'stream' => true,
                ]);

                $body = $response->getBody();

                while (!$body->eof()) {
                    $chunk = $body->read(256);
                    echo $chunk;
                    if (function_exists('ob_flush')) {
                        @ob_flush();
                    }
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
     * Sube un archivo adjunto tabular a FastAPI para analítica local en DuckDB.
     */
    public function uploadAttachment($file, string $sessionId, string $userId): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post("{$this->baseUrl}/chat/attachments", [
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json('detail') ?? 'Error al subir el archivo a FastAPI.',
            ];
        } catch (\Throwable $e) {
            Log::error('Error en uploadAttachment FastAPI', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Elimina un archivo adjunto del chat.
     */
    public function deleteAttachment(string $attachmentId, string $userId): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->delete("{$this->baseUrl}/chat/attachments/{$attachmentId}", [
                    'user_id' => $userId,
                ]);

            return ['success' => $response->successful()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
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
    /**
     * Actualiza la configuración, credenciales o esquemas de una conexión existente.
     *
     * @param string $connectionId
     * @param array $payload
     * @return array
     */
    public function updateConnection(string $connectionId, array $payload): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(20)
            ->put("{$this->baseUrl}/connections/{$connectionId}", $payload);

        if ($response->successful()) {
            return ['success' => true, 'data' => $response->json()];
        }

        return [
            'success' => false,
            'message' => $response->json('detail') ?? 'No se pudo actualizar la conexión.',
        ];
    }

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
    public function getConnectionSchemas(string $connectionId): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->get("{$this->baseUrl}/connections/{$connectionId}/schemas");

        if ($response->successful()) {
            return ['success' => true, 'data' => $response->json()];
        }

        return [
            'success' => false,
            'message' => $response->json('detail') ?? 'No se pudieron obtener los esquemas de la base de datos.',
        ];
    }

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
     * Ingesta y vectoriza un documento (PDF, Excel, Word, Markdown, Texto) en pgvector.
     */
    public function ingestKnowledgeDocument(array $payload): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(120)
                ->post("{$this->baseUrl}/knowledge/ingest", $payload);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'message' => $response->json('detail') ?? 'No se pudo vectorizar el documento en la base de conocimiento.',
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

    /**
     * Lista candidatos de conocimiento y propuestas de curación.
     */
    public function listCandidates(
        string $userId,
        string $userRole,
        array $userPermissions = [],
        ?string $connectionId = null,
        ?string $scope = null,
        ?string $status = null,
        bool $proposedOnly = false
    ): array {
        try {
            $permsHeader = implode(',', $userPermissions);
            $query = array_filter([
                'connection_id' => $connectionId,
                'scope' => $scope,
                'status' => $status,
                'proposed_only' => $proposedOnly ? 'true' : null,
            ]);

            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'X-User-Id' => $userId,
                    'X-User-Role' => $userRole,
                    'X-User-Permissions' => $permsHeader,
                ])
                ->timeout(15)
                ->get("{$this->baseUrl}/knowledge/candidates", $query);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'message' => $response->json('detail') ?? 'Error consultando candidatos.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Confirma una regla candidata para el usuario autenticado.
     */
    public function confirmUserCandidate(string $candidateId, string $userId): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders(['X-User-Id' => $userId])
                ->timeout(15)
                ->post("{$this->baseUrl}/knowledge/candidates/{$candidateId}/confirm-user");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'message' => $response->json('detail') ?? 'Error confirmando candidato.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Postula una regla candidata para revisión global corporativa.
     */
    public function proposeGlobalCandidate(string $candidateId, string $userId): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders(['X-User-Id' => $userId])
                ->timeout(15)
                ->post("{$this->baseUrl}/knowledge/candidates/{$candidateId}/propose-global");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'message' => $response->json('detail') ?? 'Error postulando candidato.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Promueve un candidato a global verificado (exclusivo administradores).
     */
    public function promoteGlobalCandidate(
        string $candidateId,
        string $userId,
        string $userRole,
        array $userPermissions = [],
        ?string $editedContent = null,
        ?string $reason = null
    ): array {
        try {
            $permsHeader = implode(',', $userPermissions);
            $payload = array_filter([
                'edited_content' => $editedContent,
                'reason' => $reason,
            ]);

            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'X-User-Id' => $userId,
                    'X-User-Role' => $userRole,
                    'X-User-Permissions' => $permsHeader,
                ])
                ->timeout(20)
                ->post("{$this->baseUrl}/knowledge/candidates/{$candidateId}/promote-global", $payload);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $response->json('detail') ?? 'Error promoviendo regla a global.'
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Descarta un candidato.
     */
    public function rejectCandidate(
        string $candidateId,
        string $userId,
        string $userRole,
        array $userPermissions = [],
        ?string $reason = null
    ): array {
        try {
            $permsHeader = implode(',', $userPermissions);
            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'X-User-Id' => $userId,
                    'X-User-Role' => $userRole,
                    'X-User-Permissions' => $permsHeader,
                ])
                ->timeout(15)
                ->post("{$this->baseUrl}/knowledge/candidates/{$candidateId}/reject", ['reason' => $reason]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'message' => $response->json('detail') ?? 'Error descartando candidato.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Revierte una regla activa a su estado anterior o la desactiva.
     */
    public function rollbackCandidate(
        string $candidateId,
        string $userId,
        string $userRole,
        array $userPermissions = [],
        ?string $reason = null
    ): array {
        try {
            $permsHeader = implode(',', $userPermissions);
            $response = Http::withToken($this->apiKey)
                ->withHeaders([
                    'X-User-Id' => $userId,
                    'X-User-Role' => $userRole,
                    'X-User-Permissions' => $permsHeader,
                ])
                ->timeout(15)
                ->post("{$this->baseUrl}/knowledge/candidates/{$candidateId}/rollback", ['reason' => $reason]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'message' => $response->json('detail') ?? 'Error al hacer rollback de la regla.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
