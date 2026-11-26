<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DatabaseConnectionController;
use App\Http\Controllers\HubController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\NeuralLabController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rutas Públicas de Autenticación de Usuario (Login, Registro y Logout)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Ruta de Inicio Directo a la Consola de Chat (Pública / Modo Libre)
Route::get('/', [ChatController::class, 'index'])->name('home');

// Rutas Públicas del Chat (Acceso Libre & Consultas de Conocimiento Global)
Route::prefix('chat')->name('chat.')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::post('/send', [ChatController::class, 'send'])->name('send');
    Route::post('/stream', [ChatController::class, 'stream'])->name('stream');
    Route::get('/dataset/{datasetId}', [ChatController::class, 'getDataset'])->name('dataset');
    Route::get('/export/{datasetId}', [ChatController::class, 'exportExcel'])->name('export');
});

// Rutas Operativas Protegidas por Autenticación de Usuario (auth)
Route::middleware(['auth'])->group(function () {

    // Hub de Selección de Ecosistemas (Solo para usuarios autenticados)
    Route::get('/hub', [HubController::class, 'index'])->name('hub.index');

    // ECOSISTEMA 1: Chat Privado (Sesiones, Adjuntos y Entrenamiento)
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/sessions', [ChatController::class, 'listSessions'])->name('sessions.list');
        Route::get('/sessions/{sessionId}', [ChatController::class, 'showSession'])->name('sessions.show');
        Route::delete('/sessions/{sessionId}', [ChatController::class, 'destroySession'])->name('sessions.destroy');

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
        Route::get('/explorer', [DatabaseConnectionController::class, 'explorer'])->name('explorer');
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

    // CENTRO DE APRENDIZAJE & INGESTA DE DOCUMENTOS (Ecosistema de Vectorización)
    Route::prefix('training')->name('training.')->group(function () {
        Route::get('/', [TrainingController::class, 'index'])->name('index');
        Route::post('/ingest', [TrainingController::class, 'ingestFile'])->name('ingest');
        Route::get('/vectorized-tables', [TrainingController::class, 'vectorizedTables'])->name('tables');
        Route::get('/vectorized-tables/columns', [TrainingController::class, 'tableColumns'])->name('columns');
        Route::post('/query-blueprint/parse', [TrainingController::class, 'parseBlueprint'])->name('blueprint.parse');
        Route::post('/query-blueprint/compile', [TrainingController::class, 'compileBlueprint'])->name('blueprint.compile');
        Route::post('/query-blueprint/validate', [TrainingController::class, 'validateBlueprint'])->name('blueprint.validate');
        Route::post('/query-blueprint/dry-run', [TrainingController::class, 'dryRunBlueprint'])->name('blueprint.dry_run');

    Route::prefix('neural-lab')->name('neural_lab.')->group(function () {
        Route::get('/', [NeuralLabController::class, 'index'])->name('index');
        Route::post('/train', [NeuralLabController::class, 'train'])->name('train');
        Route::post('/predict', [NeuralLabController::class, 'predict'])->name('predict');
        Route::post('/compare', [NeuralLabController::class, 'compare'])->name('compare');
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


Route::post('/suma-sync-user', function (\Illuminate\Http\Request $request) {
    $token = $request->header('X-Sync-Token');
    if ($token !== 'suma_datamind_sync_2026') {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $data = $request->validate([
        'suma_user_id' => 'required|integer',
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'password' => 'required|string|min:1',
    ]);

    $defaultRoleId = \App\Models\Role::where('slug', 'super-admin')->value('id')
        ?? \App\Models\Role::value('id');

    $emailNormalized = strtolower(trim($data['email']));

    $user = \App\Models\User::where('suma_user_id', $data['suma_user_id'])
        ->orWhereRaw('LOWER(email) = ?', [$emailNormalized])
        ->first();

    $passwordUpdated = false;
    $userCreated = false;

    if (!$user) {
        $user = \App\Models\User::create([
            'suma_user_id' => $data['suma_user_id'],
            'name' => $data['name'],
            'email' => $emailNormalized,
            'password' => $data['password'],
            'role_id' => $defaultRoleId,
            'is_active' => true,
        ]);
        $userCreated = true;
    } else {
        $updates = [];

        if (empty($user->suma_user_id)) {
            $updates['suma_user_id'] = $data['suma_user_id'];
        }
        if ($user->email !== $emailNormalized) {
            $updates['email'] = $emailNormalized;
        }
        if (!empty($data['name']) && $user->name !== $data['name']) {
            $updates['name'] = $data['name'];
        }

        // Si inició sesión en suma_spt con una contraseña diferente a la almacenada en el chat, actualizarla a la nueva
        if (!\Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
            $updates['password'] = $data['password'];
            $updates['password_changed_at'] = now();
            $passwordUpdated = true;
        }

        if (!empty($updates)) {
            $user->update($updates);
        }
    }

    return response()->json([
        'ok' => true,
        'user_id' => $user->id,
        'created' => $userCreated,
        'password_updated' => $passwordUpdated,
    ]);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);


Route::middleware(['auth'])->group(function () {
    Route::get('/training/concepts', function () {
        $baseUrl = rtrim(config('services.fastapi.url', 'http://127.0.0.1:8000'), '/');
        $token = config('services.fastapi.token', 'datamind_api_secret_token_2026');
        $res = \Illuminate\Support\Facades\Http::withToken($token)->get("{$baseUrl}/knowledge/concepts");
        return response()->json($res->json(), $res->status());
    });

    Route::post('/training/concepts', function (\Illuminate\Http\Request $request) {
        $baseUrl = rtrim(config('services.fastapi.url', 'http://127.0.0.1:8000'), '/');
        $token = config('services.fastapi.token', 'datamind_api_secret_token_2026');
        $res = \Illuminate\Support\Facades\Http::withToken($token)->post("{$baseUrl}/knowledge/concepts", [
            'short_name' => $request->input('short_name'),
            'long_name' => $request->input('long_name'),
            'description' => $request->input('description'),
        ]);
        return response()->json($res->json(), $res->status());
    });
});


Route::middleware(['auth'])->group(function () {
    Route::get('/settings/concepts', function () {
        return view('settings.concepts');
    })->name('settings.concepts');

    Route::put('/training/concepts/{conceptId}', function (\Illuminate\Http\Request $request, string $conceptId) {
        $baseUrl = rtrim(config('services.fastapi.url', 'http://127.0.0.1:8000/api/v1'), '/');
        $token = config('services.fastapi.token', 'datamind_api_secret_token_2026');
        $res = \Illuminate\Support\Facades\Http::withToken($token)->put("{$baseUrl}/knowledge/concepts/{$conceptId}", [
            'short_name' => $request->input('short_name'),
            'long_name' => $request->input('long_name'),
            'description' => $request->input('description'),
        ]);
        return response()->json($res->json(), $res->status());
    });
});
