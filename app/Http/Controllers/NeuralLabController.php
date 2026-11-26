<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NeuralLabController extends Controller
{
    private function pythonApiUrl(string $path): string
    {
        $base = rtrim(config('services.python_rag.url', env('PYTHON_RAG_URL', 'http://127.0.0.1:8000')), '/');
        return "{$base}/api/v1/neural-lab/{$path}";
    }

    public function index()
    {
        return view('neural_lab.index');
    }

    public function train(Request $request)
    {
        $response = Http::timeout(60)->post($this->pythonApiUrl('train'), $request->all());
        return response($response->body(), $response->status())->header('Content-Type', 'application/json');
    }

    public function predict(Request $request)
    {
        $response = Http::timeout(30)->post($this->pythonApiUrl('predict'), $request->all());
        return response($response->body(), $response->status())->header('Content-Type', 'application/json');
    }

    public function compare(Request $request)
    {
        $response = Http::timeout(90)->post($this->pythonApiUrl('compare'), $request->all());
        return response($response->body(), $response->status())->header('Content-Type', 'application/json');
    }
}
