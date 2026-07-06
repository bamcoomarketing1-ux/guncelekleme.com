<?php

use App\Http\Controllers\SpaController;
use App\Services\UploadService;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post', 'options'], '/cdn-cgi/rum', fn () => response('', 204));

Route::get('/manifest.json', [SpaController::class, 'manifest']);

Route::get('/storage/{path}', function (string $path) {
    $full = UploadService::resolvePublicPath($path);
    if ($full) {
        return response()->file($full);
    }
    abort(404);
})->where('path', '.*');

Route::get('/{any?}', [SpaController::class, 'index'])
    ->where('any', '^(?!api|storage|up|assets|favicon\.ico|manifest\.json|robots\.txt|sitemap\.xml).*$');
