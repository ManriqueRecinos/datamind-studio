<?php

namespace App\Http\Controllers;

use App\Models\Ecosystem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HubController extends Controller
{
    /**
     * Muestra la pantalla central de Selección de Ecosistemas / Inicio de DataMind Studio.
     */
    public function index(Request $request): View
    {
        $ecosystems = collect([]);
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('ecosystems')) {
                $ecosystems = Ecosystem::with(['modules.interfaces'])
                    ->where('is_active', true)
                    ->orderBy('order_num')
                    ->get();
            }
        } catch (\Throwable $e) {
            $ecosystems = collect([]);
        }

        $isOnline = true;
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 1.5]);
            $res = $client->get('http://127.0.0.1:8000/health');
            $isOnline = ($res->getStatusCode() === 200);
        } catch (\Throwable $e) {
            $isOnline = false;
        }

        return view('hub.index', compact('ecosystems', 'isOnline'));
    }
}
