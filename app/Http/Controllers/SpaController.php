<?php

namespace App\Http\Controllers;

use App\Services\SpaDocumentService;
use Illuminate\Http\Response;

class SpaController extends Controller
{
    public function index(SpaDocumentService $spa): Response
    {
        return response($spa->renderIndexHtml(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function manifest(SpaDocumentService $spa): Response
    {
        return response($spa->renderManifest(), 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }
}
