<?php
/** Frontend route doğrulama — php scripts/verify_routes.php */
$base = 'http://127.0.0.1:8000/api';
$paths = [
    'GET /settings', 'GET /banners', 'GET /bonuses/featured', 'GET /social-media/homepage',
    'GET /ticket-events/homepage', 'GET /daily-wheel', 'GET /leaderboard',
    'POST /admin/login', 'GET /admin/dashboard', 'GET /admin/banners', 'GET /admin/admins',
    'GET /admin/scratch-card', 'GET /admin/telegram', 'GET /admin/wheel/history',
    'GET /admin/ticket-participations', 'GET /admin/market', 'GET /admin/users?per_page=3',
];

$login = json_decode(file_get_contents($base.'/admin/login', false, stream_context_create([
    'http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => json_encode(['email' => 'test@gmail.com', 'password' => 'testtest'])],
])), true);
$token = $login['data']['token'] ?? '';
$ok = 0;
$fail = 0;
foreach ($paths as $line) {
    [$method, $path] = explode(' ', $line, 2);
    $ctx = ['http' => ['method' => $method, 'ignore_errors' => true]];
    if (str_starts_with($path, '/admin/') && $path !== '/admin/login') {
        $ctx['http']['header'] = "Authorization: Bearer {$token}\r\n";
    }
    if ($method === 'POST' && $path === '/admin/login') {
        $ctx['http']['header'] = "Content-Type: application/json\r\n";
        $ctx['http']['content'] = json_encode(['email' => 'test@gmail.com', 'password' => 'testtest']);
    }
    $res = @file_get_contents($base.$path, false, stream_context_create($ctx));
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
        $code = (int) $m[0];
    }
    if ($code >= 200 && $code < 400) {
        echo "OK  {$code} {$line}\n";
        $ok++;
    } else {
        echo "FAIL {$code} {$line}\n";
        $fail++;
    }
}
echo "\n{$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);
