<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    protected FastApiClient $fastApiClient;

    public function __construct(FastApiClient $fastApiClient)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }
        $this->fastApiClient = $fastApiClient;
    }

    /**
     * Obtiene el identificador único del usuario autenticado para aislar su historial.
     */
    protected function resolveUserId(): string
    {
        if (Auth::check()) {
            return 'user_' . Auth::id();
        }
        return 'guest_user';
    }

    /**
     * Muestra la interfaz gráfica del chat DataMind AI con el historial de sesiones del usuario.
     */
    public function index()
    {
        $isOnline = $this->fastApiClient->checkHealth();
        $userId = $this->resolveUserId();
        $connections = $isOnline ? $this->fastApiClient->listConnections() : [];
        $chatSessions = $isOnline ? $this->fastApiClient->listChatSessions($userId) : [];

        return view('chat.index', [
            'isOnline' => $isOnline,
            'connections' => $connections,
            'chatSessions' => $chatSessions,
        ]);
    }

    /**
     * Procesa una consulta al chat en modo síncrono.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data) && $raw = $request->getContent()) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        $validated = validator($data, [
            'prompt' => 'required|string|max:2000',
            'session_id' => 'nullable|string',
            'connection_id' => 'nullable|string',
        ])->validate();

        $user = Auth::user();
        $userRole = $user?->role?->slug ?? 'guest';
        $userPermissions = $user?->role?->permissions->pluck('slug')->toArray() ?? [];

        $result = $this->fastApiClient->sendMessage(
            $validated['prompt'],
            $validated['session_id'] ?? null,
            $validated['connection_id'] ?? null,
            $this->resolveUserId(),
            $userRole,
            $userPermissions
        );

        return response()->json($result);
    }

    /**
     * Inicia la transmisión de respuesta por streaming SSE hacia el navegador.
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function stream(Request $request): StreamedResponse
    {
        $data = $request->all();
        if (empty($data) && $raw = $request->getContent()) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        $validated = validator($data, [
            'prompt' => 'required|string|max:2000',
            'session_id' => 'nullable|string',
            'connection_id' => 'nullable|string',
            'retry_message_id' => 'nullable|string',
            'attachment_ids' => 'nullable|array',
        ])->validate();

        return $this->fastApiClient->streamChat(
            $validated['prompt'],
            $validated['session_id'] ?? null,
            $validated['connection_id'] ?? null,
            $this->resolveUserId(),
            $validated['retry_message_id'] ?? null,
            $validated['attachment_ids'] ?? []
        );
    }

    /**
     * Lista el historial de sesiones de conversación del usuario autenticado.
     */
    public function listSessions(): JsonResponse
    {
        $sessions = $this->fastApiClient->listChatSessions($this->resolveUserId());
        return response()->json(['success' => true, 'data' => $sessions]);
    }

    /**
     * Obtiene los mensajes completos de una conversación específica del usuario.
     */
    public function showSession(string $sessionId): JsonResponse
    {
        $result = $this->fastApiClient->getChatSessionDetail($sessionId, $this->resolveUserId());
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Sube un archivo adjunto tabular o documental (.csv, .tsv, .xlsx, .xls, .pdf, .docx) a FastAPI.
     */
    public function uploadAttachment(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:15360|mimes:csv,txt,xlsx,xls,pdf,docx',
            'session_id' => 'required|string',
        ]);

        $file = $request->file('file');
        $sessionId = $request->input('session_id');
        $userId = $this->resolveUserId();

        $result = $this->fastApiClient->uploadAttachment($file, $sessionId, $userId);
        return response()->json($result, $result['success'] ? 201 : 422);
    }

    /**
     * Elimina un archivo adjunto de la sesión.
     */
    public function destroyAttachment(string $attachmentId): JsonResponse
    {
        $result = $this->fastApiClient->deleteAttachment($attachmentId, $this->resolveUserId());
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Elimina una conversación del historial del usuario.
     */
    public function destroySession(string $sessionId): JsonResponse
    {
        $result = $this->fastApiClient->deleteChatSession($sessionId, $this->resolveUserId());
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Obtiene las filas completas y configuración de columnas de un dataset en caché para construir gráficos.
     */
    public function getDataset(string $datasetId): JsonResponse
    {
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $datasetId)) {
            return response()->json(['success' => false, 'message' => 'Dataset inválido.'], 400);
        }

        $result = $this->fastApiClient->getDatasetJson($datasetId);
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Descarga el reporte Excel (.xlsx) correspondiente a un dataset generado por MariaDB.
     *
     * @param string $datasetId
     * @return Response
     */
    public function exportExcel(string $datasetId)
    {
        // Validar identificador seguro del dataset
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $datasetId)) {
            abort(400, 'Identificador de dataset inválido.');
        }

        $response = $this->fastApiClient->downloadExcel($datasetId);

        if (!$response->successful()) {
            abort($response->status(), 'No se encontró el conjunto de datos o ha expirado.');
        }

        $filename = "datamind_report_{$datasetId}.xlsx";
        if ($disposition = $response->header('Content-Disposition')) {
            if (preg_match('/filename="([^"]+)"/', $disposition, $matches)) {
                $filename = $matches[1];
            }
        }

        return response($response->body(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Lista las reglas, relaciones manuales, fórmulas y ejemplos SQL aprendidos por la IA.
     */
    public function listKnowledgeRules(Request $request): JsonResponse
    {
        $connId = $request->query('connection_id');
        $result = $this->fastApiClient->listKnowledgeRules($connId);
        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Registra y vectoriza una nueva cápsula de entrenamiento para la IA.
     */
    public function trainKnowledgeRule(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data) && $raw = $request->getContent()) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        $validated = validator($data, [
            'category' => 'required|string|in:custom_relation,business_rule,kpi_formula,sql_example',
            'title' => 'required|string|min:3|max:255',
            'content' => 'required|string|min:5|max:4000',
            'connection_id' => 'nullable|string',
            'source_table' => 'nullable|string',
            'source_column' => 'nullable|string',
            'target_table' => 'nullable|string',
            'target_column' => 'nullable|string',
            'label_column' => 'nullable|string',
            'sql_query' => 'nullable|string',
        ])->validate();

        $result = $this->fastApiClient->trainKnowledgeRule($validated);
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Elimina una regla aprendida de pgvector.
     */
    public function deleteKnowledgeRule(string $ruleId): JsonResponse
    {
        $result = $this->fastApiClient->deleteKnowledgeRule($ruleId);
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Lista candidatos de conocimiento y propuestas de curación.
     */
    public function listKnowledgeCandidates(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userRole = $user?->role?->slug ?? 'guest';
        $userPermissions = $user?->role?->permissions->pluck('slug')->toArray() ?? [];

        $result = $this->fastApiClient->listCandidates(
            $this->resolveUserId(),
            $userRole,
            $userPermissions,
            $request->query('connection_id'),
            $request->query('scope'),
            $request->query('status'),
            $request->boolean('proposed_only')
        );

        return response()->json($result);
    }

    /**
     * Confirma una regla candidata para el usuario autenticado.
     */
    public function confirmUserCandidate(string $candidateId): JsonResponse
    {
        $result = $this->fastApiClient->confirmUserCandidate($candidateId, $this->resolveUserId());
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Postula una regla candidata a revisión corporativa.
     */
    public function proposeGlobalCandidate(string $candidateId): JsonResponse
    {
        $result = $this->fastApiClient->proposeGlobalCandidate($candidateId, $this->resolveUserId());
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Promueve un candidato a global verificado (exclusivo administradores con manage-knowledge).
     */
    public function promoteGlobalCandidate(Request $request, string $candidateId): JsonResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->hasRole('super-admin', 'admin') && !$user->hasPermission('manage-knowledge'))) {
            return response()->json(['success' => false, 'message' => 'Acceso denegado: Se requiere rol administrativo para promover reglas corporativas.'], 403);
        }

        $userRole = $user->role?->slug ?? 'admin';
        $userPermissions = $user->role?->permissions->pluck('slug')->toArray() ?? [];

        $result = $this->fastApiClient->promoteGlobalCandidate(
            $candidateId,
            $this->resolveUserId(),
            $userRole,
            $userPermissions,
            $request->input('edited_content'),
            $request->input('reason')
        );

        $status = $result['success'] ? 200 : ($result['status'] ?? 400);
        return response()->json($result, $status);
    }

    /**
     * Descarta un candidato.
     */
    public function rejectCandidate(Request $request, string $candidateId): JsonResponse
    {
        $user = Auth::user();
        $userRole = $user?->role?->slug ?? 'guest';
        $userPermissions = $user?->role?->permissions->pluck('slug')->toArray() ?? [];

        $result = $this->fastApiClient->rejectCandidate(
            $candidateId,
            $this->resolveUserId(),
            $userRole,
            $userPermissions,
            $request->input('reason')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Revierte una regla activa a su estado anterior o la desactiva.
     */
    public function rollbackCandidate(Request $request, string $candidateId): JsonResponse
    {
        $user = Auth::user();
        $userRole = $user?->role?->slug ?? 'guest';
        $userPermissions = $user?->role?->permissions->pluck('slug')->toArray() ?? [];

        $result = $this->fastApiClient->rollbackCandidate(
            $candidateId,
            $this->resolveUserId(),
            $userRole,
            $userPermissions,
            $request->input('reason')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
