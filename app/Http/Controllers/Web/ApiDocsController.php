<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class ApiDocsController extends Controller
{
    public function index()
    {
        return view('api-docs');
    }

    public function spec()
    {
        $path = resource_path('swagger/openapi.json');
        return response()->file($path, ['Content-Type' => 'application/json']);
    }
}
