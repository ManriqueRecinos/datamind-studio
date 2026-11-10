<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Muestra la vista del Ecosistema de Configuración & Apariencia.
     */
    public function index(Request $request): View
    {
        $isOnline = true;
        // Comprobar estado del microservicio FastAPI
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 1.5]);
            $res = $client->get('http://127.0.0.1:8000/health');
            $isOnline = ($res->getStatusCode() === 200);
        } catch (\Throwable $e) {
            $isOnline = false;
        }

        return view('settings.index', compact('isOnline'));
    }

    /**
     * Guarda las preferencias de UI en sesión o devuelve confirmación JSON.
     */
    public function savePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'palette_name' => 'nullable|string|max:50',
            'colors' => 'nullable|array',
            'font_size' => 'nullable|string|in:xs,sm,base,lg,xl',
            'font_family' => 'nullable|string|max:100',
            'dark_mode' => 'nullable|boolean',
        ]);

        // Guardar en la sesión del usuario si está disponible
        session(['datamind_ui_settings' => $validated]);

        return response()->json([
            'status' => 'success',
            'message' => 'Preferencias de diseño guardadas exitosamente',
            'settings' => $validated
        ]);
    }
}
