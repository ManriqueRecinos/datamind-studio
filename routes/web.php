<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DatabaseConnectionController;
use Illuminate\Support\Facades\Route;

// Rutas de DataMind Studio - Asistente IA y RAG Multi-Base de Datos
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
Route::post('/chat/stream', [ChatController::class, 'stream'])->name('chat.stream');
Route::get('/chat/export/{datasetId}', [ChatController::class, 'exportExcel'])->name('chat.export');

// Rutas del Gestor Dinámico de Bases de Datos y Vectorización Selectiva
Route::get('/connections', [DatabaseConnectionController::class, 'index'])->name('connections.index');
Route::get('/connections/list', [DatabaseConnectionController::class, 'list'])->name('connections.list');
Route::post('/connections/test', [DatabaseConnectionController::class, 'test'])->name('connections.test');
Route::post('/connections', [DatabaseConnectionController::class, 'store'])->name('connections.store');
Route::post('/connections/{connectionId}/activate', [DatabaseConnectionController::class, 'activate'])->name('connections.activate');
Route::get('/connections/{connectionId}/tables', [DatabaseConnectionController::class, 'tables'])->name('connections.tables');
Route::post('/connections/{connectionId}/vectorize', [DatabaseConnectionController::class, 'vectorize'])->name('connections.vectorize');
Route::delete('/connections/{connectionId}', [DatabaseConnectionController::class, 'destroy'])->name('connections.destroy');

Route::get('/', function () {
    $categories = ['Base de Datos', 'Nodos IA', 'Pipelines ETL', 'Vectores HNSW', 'Almacenamiento', 'Seguridad', 'Monitoreo'];
    $states = ['Disponible', 'Stock Bajo', 'Agotado', 'En Tránsito'];
    $items = [];

    for ($i = 1; $i <= 65; $i++) {
        $category = $categories[($i - 1) % count($categories)];
        $state = ($i % 11 === 0) ? 'Agotado' : (($i % 5 === 0) ? 'Stock Bajo' : (($i % 7 === 0) ? 'En Tránsito' : 'Disponible'));
        
        $names = [
            'Cluster PostgreSQL 17 + pgvector',
            'Conector Analítico MariaDB',
            'Índice Vectorial HNSW 768-dim',
            'Motor Inferencia Ollama Qwen 2.5',
            'Módulo de Embeddings DataMind',
            'Guardrail AST SQLGlot Read-Only',
            'Exportador Dinámico OpenPyXL',
            'Pipeline de Sincronización DDL',
            'Caché Temporal de Datasets',
            ' Conector Universal Multi-DB',
            'Stream Server-Sent Events (SSE)',
            ' Conector de Auditoría JSON',
            'Analizador Semántico de Esquemas',
        ];
        $itemName = trim($names[($i - 1) % count($names)]) . ' - Instancia ' . $i;
        $stock = ($state === 'Agotado') ? 0 : (($state === 'Stock Bajo') ? rand(5, 40) : rand(120, 3500));
        $unit = 'unid.';

        $items[] = [
            'id' => $i,
            'code' => 'DM-' . str_pad($i, 3, '0', STR_PAD_LEFT),
            'name' => $itemName,
            'category' => $category,
            'stock' => number_format($stock) . ' ' . $unit,
            'state' => $state
        ];
    }

    return view('welcome', compact('items'));
});
