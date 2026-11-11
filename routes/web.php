<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DatabaseConnectionController;
use App\Http\Controllers\HubController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rutas Públicas de Autenticación de Usuario (Login, Registro y Logout)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas de Inicio y Hub de Selección de Ecosistemas
Route::get('/', [HubController::class, 'index'])->name('home');
Route::get('/hub', [HubController::class, 'index'])->name('hub.index');

// Rutas Operativas Protegidas por Autenticación de Usuario (auth)
Route::middleware(['auth'])->group(function () {

    // ECOSISTEMA 1: Chat & Analítica Inteligente IA
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::post('/send', [ChatController::class, 'send'])->name('send');
        Route::post('/stream', [ChatController::class, 'stream'])->name('stream');
        Route::get('/sessions', [ChatController::class, 'listSessions'])->name('sessions.list');
        Route::get('/sessions/{sessionId}', [ChatController::class, 'showSession'])->name('sessions.show');
        Route::delete('/sessions/{sessionId}', [ChatController::class, 'destroySession'])->name('sessions.destroy');
        Route::get('/dataset/{datasetId}', [ChatController::class, 'getDataset'])->name('dataset');
        Route::get('/export/{datasetId}', [ChatController::class, 'exportExcel'])->name('export');

        // Adjuntos de Archivos Tabulares en Chat (DuckDB / Polars)
        Route::post('/attachments', [ChatController::class, 'uploadAttachment'])->name('attachments.upload');
        Route::delete('/attachments/{id}', [ChatController::class, 'destroyAttachment'])->name('attachments.destroy');

        // Entrenamiento de Base de Conocimiento (Requiere permiso chat.train)
        Route::middleware(['permission:chat.train'])->group(function () {
            Route::get('/training/rules', [ChatController::class, 'listKnowledgeRules'])->name('training.list');
            Route::post('/training/train', [ChatController::class, 'trainKnowledgeRule'])->name('training.train');
            Route::delete('/training/rules/{ruleId}', [ChatController::class, 'deleteKnowledgeRule'])->name('training.destroy');

            // Gestión y Curación de Candidatos de Conocimiento Implícito (Fase 1)
            Route::get('/candidates', [ChatController::class, 'listKnowledgeCandidates'])->name('candidates.list');
            Route::post('/candidates/{id}/confirm-user', [ChatController::class, 'confirmUserCandidate'])->name('candidates.confirm_user');
            Route::post('/candidates/{id}/propose-global', [ChatController::class, 'proposeGlobalCandidate'])->name('candidates.propose_global');
            Route::post('/candidates/{id}/promote-global', [ChatController::class, 'promoteGlobalCandidate'])->name('candidates.promote_global');
            Route::post('/candidates/{id}/reject', [ChatController::class, 'rejectCandidate'])->name('candidates.reject');
            Route::post('/candidates/{id}/rollback', [ChatController::class, 'rollbackCandidate'])->name('candidates.rollback');
        });
    });

    // ECOSISTEMA 2: Gestor Dinámico de Bases de Datos & Vectorización
    Route::prefix('connections')->name('connections.')->group(function () {
        Route::get('/', [DatabaseConnectionController::class, 'index'])->name('index');
        Route::get('/list', [DatabaseConnectionController::class, 'list'])->name('list');
        Route::get('/{connectionId}/schemas', [DatabaseConnectionController::class, 'schemas'])->name('schemas');
        Route::get('/{connectionId}/tables', [DatabaseConnectionController::class, 'tables'])->name('tables');

        // Mutaciones de BD (Requiere permiso connections.manage)
        Route::middleware(['permission:connections.manage'])->group(function () {
            Route::post('/test', [DatabaseConnectionController::class, 'test'])->name('test');
            Route::post('/', [DatabaseConnectionController::class, 'store'])->name('store');
            Route::put('/{connectionId}', [DatabaseConnectionController::class, 'update'])->name('update');
            Route::post('/{connectionId}/activate', [DatabaseConnectionController::class, 'activate'])->name('activate');
            Route::post('/{connectionId}/vectorize', [DatabaseConnectionController::class, 'vectorize'])->name('vectorize');
            Route::delete('/{connectionId}', [DatabaseConnectionController::class, 'destroy'])->name('destroy');
        });
    });

    // ECOSISTEMA 3: Configuración & Apariencia
    Route::prefix('settings')->name('settings.')->group(function () {
        // Vista Unificada de Apariencia & Paletas
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/preferences', [SettingsController::class, 'savePreferences'])->name('preferences');

        // Administración de Usuarios & Auditoría IA (Requiere permiso users.manage o roles.manage)
        Route::middleware(['permission:users.manage,roles.manage'])->prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::put('/{user}/password', [UserController::class, 'updatePassword'])->name('password');
            Route::post('/{user}/toggle', [UserController::class, 'toggleActive'])->name('toggle');
            Route::post('/{user}/role', [UserController::class, 'assignRole'])->name('assignRole');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::get('/{user}/ai-history', [UserController::class, 'aiHistory'])->name('aiHistory');
        });

        // Administración de Roles & Permisos RBAC (Requiere permiso roles.manage)
        Route::middleware(['permission:roles.manage'])->prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RolePermissionController::class, 'index'])->name('index');
            Route::post('/', [RolePermissionController::class, 'storeRole'])->name('store');
            Route::put('/{role}', [RolePermissionController::class, 'updateRole'])->name('update');
            Route::post('/{role}/toggle', [RolePermissionController::class, 'toggleActiveRole'])->name('toggle');
            Route::post('/{role}/permissions', [RolePermissionController::class, 'syncRolePermissions'])->name('sync');
            Route::delete('/{role}', [RolePermissionController::class, 'destroyRole'])->name('destroy');
            Route::get('/{role}/users', [RolePermissionController::class, 'roleUsers'])->name('users');
        });
    });

});
