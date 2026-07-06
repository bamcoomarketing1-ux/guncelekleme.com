<?php

namespace App\Services;

use App\Support\CspPolicy;
use Illuminate\Support\Facades\File;

class FrontendPublisher
{
    public function sourcePath(): string
    {
        return resource_path('frontend');
    }

    public function publish(): void
    {
        $src = $this->sourcePath();
        if (! is_dir($src)) {
            throw new \RuntimeException("Frontend kaynağı bulunamadı: {$src}");
        }

        foreach (['assets', 'index.html', 'robots.txt', 'sitemap.xml', 'manifest.json', 'favicon.ico'] as $item) {
            $from = $src.DIRECTORY_SEPARATOR.$item;
            if (! file_exists($from)) {
                continue;
            }
            $to = public_path($item);
            if (is_dir($from)) {
                File::deleteDirectory($to);
                File::copyDirectory($from, $to);
            } else {
                File::copy($from, $to);
            }
        }

        $js = public_path('assets/index-ChvzUPTI.js');
        if (is_file($js)) {
            $text = file_get_contents($js);
            $text = preg_replace('/baseURL:"https:\\\\u200b[^"]+"/', 'baseURL:"/api"', $text) ?? $text;
            $text = str_replace('alijhgfdjhgjhdfhjgdfjhgjdfg.site/api', '/api', $text);
            $text = preg_replace('/baseURL:"https:\/\/[^"]*?\/api"/', 'baseURL:"/api"', $text) ?? $text;
            $text = str_replace(
                'ne.interceptors.response.use(e=>e.data,e=>{',
                'ne.interceptors.response.use(e=>{const d=e.data;return d&&d.status==="success"&&(d.success=!0),d},e=>{',
                $text
            );
            $text = str_replace(
                'console.error("Failed to fetch user data",o)}}return{token:e,user:t,isAuthenticated:s,setAuth:n,logout:r,fetchUser:a}}),jo=cr("adminAuth"',
                'console.error("Failed to fetch user data",o)}}return document.addEventListener("visibilitychange",()=>{document.visibilityState==="visible"&&e.value&&!window.location.pathname.startsWith("/panel")&&a()}),{token:e,user:t,isAuthenticated:s,setAuth:n,logout:r,fetchUser:a}}),jo=cr("adminAuth"',
                $text
            );
            $text = str_replace(
                'ne.interceptors.request.use(e=>{const t=window.location.pathname.startsWith("/panel"),s=localStorage.getItem(t?"admin_token":"auth_token");return s&&(e.headers.Authorization=`Bearer ${s}`),e},e=>Promise.reject(e));',
                'ne.interceptors.request.use(e=>{const u=String(e.url||""),m=(e.method||"get").toLowerCase(),a=u.startsWith("/admin")||u==="/admin/login"||(u==="/settings"&&m==="post"),t=a?localStorage.getItem("admin_token"):localStorage.getItem("auth_token");return t&&(e.headers.Authorization=`Bearer ${t}`),e},e=>Promise.reject(e));',
                $text
            );
            $text = str_replace(
                'if(e.response.status===401){const s=e.config.url?.endsWith("/login"),n=window.location.pathname.startsWith("/panel");s||(n?(localStorage.removeItem("admin_token"),localStorage.removeItem("admin_data"),window.location.pathname!=="/panel/login"&&(window.location.href="/panel/login")):(localStorage.removeItem("auth_token"),localStorage.removeItem("user_data"),window.location.pathname!=="/"&&(window.location.href="/")))}',
                'if(e.response.status===401){const u=String(e.config.url||""),s=u.endsWith("/login"),a=u.startsWith("/admin")||(u==="/settings"&&(e.config.method||"get").toLowerCase()==="post"),p=window.location.pathname.startsWith("/panel");s||(a?(localStorage.removeItem("admin_token"),localStorage.removeItem("admin_data"),window.location.pathname!=="/panel/login"&&(window.location.href="/panel/login")):!p&&!u.startsWith("/admin")&&(localStorage.removeItem("auth_token"),localStorage.removeItem("user_data"),window.location.pathname!=="/"&&(window.location.href="/")))}',
                $text
            );
            $text = str_replace(
                'As=cr("settings",()=>{const e=N({site_name:"Nexu V1",',
                'As=cr("settings",()=>{const _b=typeof window<"u"&&window.__SITE_BOOT__||null;const e=N({site_name:_b?.site_name||"Nexu V1",',
                $text
            );
            $text = str_replace(
                'index_primary_color:e.value.index_primary_color||"#f30f48"})};return{settings:e,loading:t,fetchSettings:',
                'index_primary_color:e.value.index_primary_color||"#f30f48"})};_b&&r(_b);return{settings:e,loading:t,fetchSettings:',
                $text
            );
            file_put_contents($js, $text);
        }

        $html = public_path('index.html');
        if (is_file($html)) {
            $text = file_get_contents($html);
            $text = $this->stripCloudflareBeacon($text);
            $text = preg_replace(
                '/<meta http-equiv="Content-Security-Policy" content="[^"]*">/',
                '<meta http-equiv="Content-Security-Policy" content="'.CspPolicy::metaContent().'">',
                $text
            ) ?? $text;
            if (! str_contains($text, 'Content-Security-Policy')) {
                $csp = '<meta http-equiv="Content-Security-Policy" content="'.CspPolicy::metaContent().'">';
                $text = str_replace('<meta name="viewport"', $csp."\n  ".'<meta name="viewport"', $text);
            }
            file_put_contents($html, $text);
        }

        foreach (['manifest.json', 'sitemap.xml'] as $file) {
            $path = public_path($file);
            if (! is_file($path)) {
                continue;
            }
            $text = file_get_contents($path);
            $cleaned = $this->stripCloudflareBeacon($text);
            if ($cleaned !== $text) {
                file_put_contents($path, $cleaned);
            }
        }

        $this->assertChunksPresent();
    }

    private function stripCloudflareBeacon(string $text): string
    {
        $text = preg_replace('/<script defer src="https:\/\/static\.cloudflareinsights\.com[^<]+<\/script>\s*/', '', $text) ?? $text;
        $text = preg_replace('/<script[^>]*cloudflareinsights[^>]*><\/script>\s*/i', '', $text) ?? $text;

        return $text;
    }

    public function assertChunksPresent(): void
    {
        $js = public_path('assets/index-ChvzUPTI.js');
        if (! is_file($js)) {
            return;
        }

        $text = file_get_contents($js);
        preg_match_all('/assets\/[A-Za-z0-9_.-]+\.(?:js|css)/', $text, $prefixed);
        preg_match_all('/[A-Za-z][A-Za-z0-9_.-]*-[A-Za-z0-9_-]{6,8}\.(?:js|css)/', $text, $bare);
        $files = array_unique(array_merge(
            array_map('basename', $prefixed[0] ?? []),
            $bare[0] ?? []
        ));

        $missing = [];
        foreach ($files as $file) {
            if (! is_file(public_path('assets/'.$file))) {
                $missing[] = $file;
            }
        }

        if ($missing !== []) {
            throw new \RuntimeException(
                'Eksik frontend chunk dosyaları: '.implode(', ', array_slice($missing, 0, 8)).
                (count($missing) > 8 ? ' ...' : '').
                '. Çalıştırın: python scripts/download_frontend_chunks.py'
            );
        }
    }
}
