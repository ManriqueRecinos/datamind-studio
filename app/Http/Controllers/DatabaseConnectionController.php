<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DatabaseConnectionController extends Controller
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
     * Muestra la vista dedicada de Bases de Datos Guardadas y registro modal.
     */
    public function index()
    {
        $isOnline = $this->fastApiClient->checkHealth();
        $connections = $isOnline ? $this->fastApiClient->listConnections() : [];

        return view('connections.index', [
            'isOnline' => $isOnline,
            'connections' => $connections,
        ]);
    }

    /**
     * Muestra la vista dedicada del Explorador de Esquemas y Tablas para Vectorización.
     */
    public function explorer(Request $request)
    {
        $isOnline = $this->fastApiClient->checkHealth();
        $connections = $isOnline ? $this->fastApiClient->listConnections() : [];
        $activeConnection = collect($connections)->firstWhere('is_active', true) ?? ($connections[0] ?? null);

        $selectedConnectionId = $request->query('connection_id');
        if ($selectedConnectionId) {
            $matched = collect($connections)->firstWhere('id', (int) $selectedConnectionId);
            if ($matched) {
                $activeConnection = $matched;
            }
        }

        return view('connections.explorer', [
            'isOnline' => $isOnline,
            'connections' => $connections,
            'activeConnection' => $activeConnection,
        ]);
    }

    /**
     * Lista las conexiones registradas en formato JSON.
     */
    public function list(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'connections' => $this->fastApiClient->listConnections(),
        ]);
    }

    /**
     * Prueba las credenciales de una base de datos PostgreSQL o MariaDB/MySQL sin guardar.
     */
    public function test(Request $request): JsonResponse
    {
        $data = $this->extractPayload($request);
        $validated = validator($data, [
            'engine' => 'required|string|in:mariadb,mysql,postgresql,postgres',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'database_name' => 'required|string|max:120',
            'username' => 'required|string|max:120',
            'password' => 'nullable|string',
        ])->validate();

        $validated['password'] = $validated['password'] ?? '';
        $result = $this->fastApiClient->testConnection($validated);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Guarda una nueva conexión tras verificar que responde correctamente.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->extractPayload($request);
        $validated = validator($data, [
            'name' => 'required|string|min:2|max:120',
            'engine' => 'required|string|in:mariadb,mysql,postgresql,postgres',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'database_name' => 'required|string|max:120',
            'username' => 'required|string|max:120',
            'password' => 'nullable|string',
            'set_active' => 'nullable|boolean',
            'selected_schemas' => 'nullable|array',
            'selected_schemas.*' => 'string',
        ])->validate();

        $validated['password'] = $validated['password'] ?? '';
        $validated['set_active'] = $validated['set_active'] ?? true;
        if (isset($data['selected_schemas']) && is_array($data['selected_schemas'])) {
            $validated['selected_schemas'] = array_values($data['selected_schemas']);
        }

        $result = $this->fastApiClient->createConnection($validated);

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Marca una conexión como activa para el asistente de chat.
     */
    /**
     * Actualiza una conexión existente (nombre, credenciales o esquemas seleccionados).
     */
    public function update(Request $request, string $connectionId): JsonResponse
    {
        $data = $this->extractPayload($request);
        $validated = validator($data, [
            'name' => 'nullable|string|min:2|max:120',
            'engine' => 'nullable|string|in:mariadb,mysql,postgresql,postgres',
            'host' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'database_name' => 'nullable|string|max:120',
            'username' => 'nullable|string|max:120',
            'password' => 'nullable|string',
            'selected_schemas' => 'nullable|array',
            'selected_schemas.*' => 'string',
            'set_active' => 'nullable|boolean',
        ])->validate();

        if (isset($data['selected_schemas']) && is_array($data['selected_schemas'])) {
            $validated['selected_schemas'] = array_values($data['selected_schemas']);
        }

        $result = $this->fastApiClient->updateConnection($connectionId, $validated);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function activate(string $connectionId): JsonResponse
    {
        $result = $this->fastApiClient->activateConnection($connectionId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Obtiene todos los esquemas disponibles en la base de datos usando las credenciales almacenadas.
     */
    public function schemas(string $connectionId): JsonResponse
    {
        $result = $this->fastApiClient->getConnectionSchemas($connectionId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Detecta y lista todas las tablas de una conexión junto con su estado de vectorización.
     */
    public function tables(string $connectionId): JsonResponse
    {
        $result = $this->fastApiClient->getConnectionTables($connectionId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Vectoriza selectivamente en pgvector las tablas seleccionadas por el usuario.
     */
    public function vectorize(Request $request, string $connectionId): JsonResponse
    {
        $data = $this->extractPayload($request);
        $validated = validator($data, [
            'tables' => 'required|array|min:1',
            'tables.*' => 'required|string',
        ])->validate();

        $result = $this->fastApiClient->vectorizeTables($connectionId, $validated['tables']);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Elimina una conexión y limpia sus vectores de esquema en pgvector.
     */
    public function destroy(string $connectionId): JsonResponse
    {
        $result = $this->fastApiClient->deleteConnection($connectionId);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    private function extractPayload(Request $request): array
    {
        $data = $request->all();
        if (empty($data) && $raw = $request->getContent()) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
        return $data;
    }
}
