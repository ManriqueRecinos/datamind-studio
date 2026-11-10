<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DatabaseConnectionController;
use Illuminate\Support\Facades\Route;

// Rutas de Autenticación de Usuario (Login, Registro y Logout)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas de DataMind Studio - Asistente IA, Historial por Usuario y Constructor de Gráficos
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
Route::post('/chat/stream', [ChatController::class, 'stream'])->name('chat.stream');
Route::get('/chat/sessions', [ChatController::class, 'listSessions'])->name('chat.sessions.list');
Route::get('/chat/sessions/{sessionId}', [ChatController::class, 'showSession'])->name('chat.sessions.show');
Route::delete('/chat/sessions/{sessionId}', [ChatController::class, 'destroySession'])->name('chat.sessions.destroy');
Route::get('/chat/dataset/{datasetId}', [ChatController::class, 'getDataset'])->name('chat.dataset');
Route::get('/chat/export/{datasetId}', [ChatController::class, 'exportExcel'])->name('chat.export');
Route::get('/chat/training/rules', [ChatController::class, 'listKnowledgeRules'])->name('chat.training.list');
Route::post('/chat/training/train', [ChatController::class, 'trainKnowledgeRule'])->name('chat.training.train');
Route::delete('/chat/training/rules/{ruleId}', [ChatController::class, 'deleteKnowledgeRule'])->name('chat.training.destroy');

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
