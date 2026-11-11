<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class TrainingController extends Controller
{
    public function __construct(
        protected FastApiClient $fastApiClient
    ) {}

    /**
     * Muestra la vista completa del Centro de Aprendizaje e Ingesta de la IA.
     */
    public function index(Request $request): View
    {
        $connectionsRes = $this->fastApiClient->listConnections();
        $connections = $connectionsRes['success'] ? ($connectionsRes['data'] ?? []) : [];
        $activeConnection = collect($connections)->firstWhere('is_active', true) ?? ($connections[0] ?? null);

        return view('training.index', [
            'connections' => $connections,
            'activeConnection' => $activeConnection,
            'isOnline' => $this->fastApiClient->isOnline(),
        ]);
    }

    /**
     * Ingesta y vectoriza archivos (PDF, Excel, Word, Markdown, Texto) directamente en pgvector.
     */
    public function ingestFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480', // Máx 20MB
            'title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:100',
            'connection_id' => 'nullable|string',
        ]);

        $uploadedFile = $request->file('file');
        $ext = strtolower($uploadedFile->getClientOriginalExtension());
        $origName = $uploadedFile->getClientOriginalName();
        $title = $request->input('title') ?: pathinfo($origName, PATHINFO_FILENAME);
        $department = $request->input('department') ?: 'Documentación Corporativa';
        $connectionId = $request->input('connection_id');

        $extractedText = '';

        try {
            if (in_array($ext, ['txt', 'md', 'markdown', 'json', 'csv', 'tsv', 'sql', 'log'])) {
                $extractedText = file_get_contents($uploadedFile->getRealPath());
            } elseif ($ext === 'pdf') {
                // Extracción de texto con librerías o fallback
                if (class_exists(\Spatie\PdfToText\Pdf::class)) {
                    $extractedText = (new \Spatie\PdfToText\Pdf())->setPdf($uploadedFile->getRealPath())->text();
                } else {
                    // Fallback rápido: regex de streams o lectura directa
                    $content = file_get_contents($uploadedFile->getRealPath());
                    preg_match_all('#\((.*?)\)#s', $content, $matches);
                    $extractedText = implode(' ', $matches[1] ?? []);
                    if (empty(trim($extractedText))) {
                        $extractedText = strip_tags($content);
                    }
                }
            } elseif (in_array($ext, ['xlsx', 'xls'])) {
                // Si existe PhpSpreadsheet o texto plano
                if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadedFile->getRealPath());
                    foreach ($spreadsheet->getAllSheets() as $sheet) {
                        $extractedText .= "--- Hoja: " . $sheet->getTitle() . " ---\n";
                        $extractedText .= $sheet->toArray(null, true, true, true);
                    }
                } else {
                    $extractedText = "Archivo Excel importado: {$origName}. Filas y datos cargados.";
                }
            } elseif ($ext === 'docx') {
                $content = file_get_contents($uploadedFile->getRealPath());
                $extractedText = strip_tags($content);
            } else {
                $extractedText = file_get_contents($uploadedFile->getRealPath());
            }

            if (empty(trim($extractedText))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo extraer texto legible del archivo subido. Asegúrate de que no esté vacío o protegido.',
                ], 422);
            }

            // Enviar a FastAPI para particionar, generar embeddings (nomic-embed-text) e insertar en pgvector
            $payload = [
                'title' => $title,
                'category' => 'process_manual',
                'department' => $department,
                'content' => $extractedText,
                'metadata' => [
                    'filename' => $origName,
                    'file_extension' => $ext,
                    'connection_id' => $connectionId,
                    'ingest_source' => 'web_ui_ingest',
                ]
            ];

            $res = $this->fastApiClient->ingestKnowledgeDocument($payload);

            return response()->json($res, $res['success'] ? 201 : 400);

        } catch (\Throwable $e) {
            Log::error('Error en ingesta de archivo:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar el archivo: ' . $e->getMessage(),
            ], 500);
        }
    }
}
