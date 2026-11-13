<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
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
        $isOnline = $this->fastApiClient->checkHealth();
        $connections = $isOnline ? $this->fastApiClient->listConnections() : [];
        $activeConnection = collect($connections)->firstWhere('is_active', true) ?? ($connections[0] ?? null);

        return view('training.index', [
            'connections' => $connections,
            'activeConnection' => $activeConnection,
            'isOnline' => $isOnline,
        ]);
    }

    /**
     * Lista paginada (10 en 10) de las tablas vectorizadas de una conexión, con búsqueda por nombre.
     * Usa `synced_tables` de la conexión: no requiere introspección de la base remota.
     */
    public function vectorizedTables(Request $request): JsonResponse
    {
        $connectionId = (string) $request->query('connection_id', '');
        $search = mb_strtolower(trim((string) $request->query('search', '')));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 10)));

        $connection = $this->findConnection($connectionId);
        if (!$connection) {
            return response()->json(['success' => false, 'message' => 'Conexión no encontrada.'], 404);
        }

        $tables = collect($connection['synced_tables'] ?? [])
            ->map(fn ($t) => (string) $t)
            ->filter(fn ($t) => $search === '' || str_contains(mb_strtolower($t), $search))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $total = $tables->count();
        $slice = $tables->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $slice->map(fn ($t) => ['table_name' => $t])->all(),
            'page' => $page,
            'total' => $total,
            'has_more' => ($page * $perPage) < $total,
        ]);
    }

    /**
     * Columnas, llaves foráneas y llave primaria de una tabla vectorizada.
     * La introspección completa se cachea por conexión para no consultar la base remota en cada clic.
     */
    public function tableColumns(Request $request): JsonResponse
    {
        $connectionId = (string) $request->query('connection_id', '');
        $table = trim((string) $request->query('table', ''));

        $connection = $this->findConnection($connectionId);
        if (!$connection) {
            return response()->json(['success' => false, 'message' => 'Conexión no encontrada.'], 404);
        }

        $synced = collect($connection['synced_tables'] ?? [])->map(fn ($t) => mb_strtolower((string) $t));
        if ($table === '' || !$synced->contains(mb_strtolower($table))) {
            return response()->json(['success' => false, 'message' => 'La tabla no está vectorizada en esta conexión.'], 404);
        }

        $payload = Cache::remember(self::tablesCacheKey($connectionId), 600, function () use ($connectionId) {
            $res = $this->fastApiClient->getConnectionTables($connectionId);
            return ($res['success'] ?? false) ? $res['data'] : null;
        });

        if (!$payload) {
            Cache::forget(self::tablesCacheKey($connectionId));
            return response()->json(['success' => false, 'message' => 'No se pudo leer la estructura de la base de datos.'], 502);
        }

        $info = collect($payload['tables'] ?? [])->first(
            fn ($t) => mb_strtolower((string) ($t['table_name'] ?? '')) === mb_strtolower($table)
        );
        if (!$info) {
            return response()->json(['success' => false, 'message' => 'No se encontró la estructura de la tabla.'], 404);
        }

        $fks = collect($info['foreign_keys'] ?? [])->keyBy(fn ($fk) => $fk['column'] ?? '');
        $columns = collect($info['columns'] ?? [])->map(function ($c) use ($fks) {
            $name = (string) ($c['name'] ?? '');
            $fk = $fks->get($name);
            return [
                'name' => $name,
                'type' => $c['type'] ?? null,
                'is_fk' => $fk !== null,
                'references' => $fk ? [
                    'table' => $fk['referenced_table'] ?? null,
                    'column' => $fk['referenced_column'] ?? 'id',
                ] : null,
            ];
        })->values();

        $labelCandidates = ['name', 'nombre', 'descripcion', 'description', 'title', 'titulo', 'label'];
        $labelSuggestion = collect($labelCandidates)->first(
            fn ($cand) => $columns->contains(fn ($c) => mb_strtolower($c['name']) === $cand)
        );

        return response()->json([
            'success' => true,
            'columns' => $columns->all(),
            'primary_key' => $info['primary_key'] ?? 'id',
            'label_suggestion' => $labelSuggestion,
        ]);
    }

    public static function tablesCacheKey(string $connectionId): string
    {
        return 'training.tables.' . $connectionId;
    }

    private function findConnection(string $connectionId): ?array
    {
        if ($connectionId === '') {
            return null;
        }
        $connections = $this->fastApiClient->listConnections();
        $found = collect($connections)->first(fn ($c) => (string) ($c['id'] ?? '') === $connectionId);
        return $found ?: null;
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

    /**
     * Proxy para parsear SQL a Query Blueprint.
     */
    public function parseBlueprint(Request $request): JsonResponse
    {
        $sql = trim((string) $request->input('sql', ''));
        $dialect = (string) $request->input('dialect', 'postgres');

        if (empty($sql)) {
            return response()->json(['success' => false, 'message' => 'La consulta SQL está vacía.'], 422);
        }

        $res = $this->fastApiClient->parseQueryBlueprint($sql, $dialect);
        return response()->json($res, $res['success'] ? 200 : 422);
    }

    /**
     * Proxy para compilar Query Blueprint a SQL.
     */
    public function compileBlueprint(Request $request): JsonResponse
    {
        $blueprint = $request->input('blueprint');
        $dialect = (string) $request->input('dialect', 'postgres');

        if (!is_array($blueprint)) {
            return response()->json(['success' => false, 'message' => 'El blueprint debe ser un objeto JSON válido.'], 422);
        }

        $res = $this->fastApiClient->compileQueryBlueprint($blueprint, $dialect);
        return response()->json($res, $res['success'] ? 200 : 422);
    }

    /**
     * Proxy para validar Query Blueprint contra tablas vectorizadas.
     */
    public function validateBlueprint(Request $request): JsonResponse
    {
        $blueprint = $request->input('blueprint');
        $connectionId = (string) $request->input('connection_id', '');

        if (!is_array($blueprint) || empty($connectionId)) {
            return response()->json(['success' => false, 'message' => 'Faltan parámetros requeridos (blueprint, connection_id).'], 422);
        }

        $res = $this->fastApiClient->validateQueryBlueprint($blueprint, $connectionId);
        return response()->json($res, $res['success'] ? 200 : 422);
    }

    /**
     * Proxy para ejecutar Dry-Run (LIMIT 20) de forma segura.
     */
    public function dryRunBlueprint(Request $request): JsonResponse
    {
        $sql = $request->input('sql');
        $blueprint = $request->input('blueprint');
        $connectionId = (string) $request->input('connection_id', '');
        $dialect = (string) $request->input('dialect', 'postgres');

        if (empty($connectionId)) {
            return response()->json(['success' => false, 'message' => 'Se requiere el connection_id.'], 422);
        }

        if (empty($sql) && empty($blueprint)) {
            return response()->json(['success' => false, 'message' => 'Debe suministrar una consulta SQL o un blueprint.'], 422);
        }

        $res = $this->fastApiClient->dryRunQueryBlueprint(
            is_string($sql) ? $sql : null,
            is_array($blueprint) ? $blueprint : null,
            $connectionId,
            $dialect
        );

        return response()->json($res, $res['success'] ? 200 : 400);
    }
}
