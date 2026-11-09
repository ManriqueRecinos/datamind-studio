<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
     * Muestra la interfaz gráfica del chat DataMind AI.
     */
    public function index()
    {
        $isOnline = $this->fastApiClient->checkHealth();
        $connections = $isOnline ? $this->fastApiClient->listConnections() : [];

        return view('chat.index', [
            'isOnline' => $isOnline,
            'connections' => $connections,
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

        $result = $this->fastApiClient->sendMessage(
            $validated['prompt'],
            $validated['session_id'] ?? null,
            $validated['connection_id'] ?? null
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
        ])->validate();

        return $this->fastApiClient->streamChat(
            $validated['prompt'],
            $validated['session_id'] ?? null,
            $validated['connection_id'] ?? null
        );
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
}
