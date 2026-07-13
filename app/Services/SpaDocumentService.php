<?php

namespace App\Services;

use App\Models\SiteSetting;

class SpaDocumentService
{
    public function renderIndexHtml(): string
    {
        $path = public_path('index.html');
        if (! is_file($path)) {
            throw new \RuntimeException('Frontend yayınlanmamış. php artisan platform:install çalıştırın.');
        }

        return $this->injectSettings(file_get_contents($path), SiteSetting::current());
    }

    public function renderManifest(): string
    {
        $settings = SiteSetting::current();
        $name = (string) ($settings['site_name'] ?? config('app.name', 'Site'));

        $manifest = [
            'name' => $name,
            'short_name' => $name,
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => (string) ($settings['index_bg_color'] ?? '#070708'),
            'theme_color' => (string) ($settings['index_primary_color'] ?? '#070708'),
        ];

        if (! empty($settings['site_favicon'])) {
            $manifest['icons'] = [[
                'src' => $settings['site_favicon'],
                'sizes' => '192x192',
                'type' => 'image/png',
            ]];
        }

        return json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /** @param  array<string, mixed>  $settings */
    private function injectSettings(string $html, array $settings): string
    {
        $siteName = (string) ($settings['site_name'] ?? config('app.name', ''));
        $metaDesc = (string) ($settings['meta_description'] ?? '');
        $metaKeywords = (string) ($settings['meta_keywords'] ?? '');

        $html = preg_replace('/<title>[^<]*<\/title>/', '<title>'.e($siteName).'</title>', $html) ?? $html;

        $html = preg_replace(
            '/<meta name="description" content="[^"]*">/',
            '<meta name="description" content="'.e($metaDesc).'">',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<meta name="keywords" content="[^"]*">/',
            '<meta name="keywords" content="'.e($metaKeywords).'">',
            $html
        ) ?? $html;

        if (! empty($settings['site_favicon'])) {
            $html = preg_replace(
                '/<link rel="icon"[^>]*id="site-favicon"[^>]*>/',
                '<link rel="icon" href="'.e((string) $settings['site_favicon']).'" id="site-favicon">',
                $html
            ) ?? $html;
        }

        $bootJson = json_encode(
            $settings,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
        );

        $bootScript = '<script>window.__SITE_BOOT__='.$bootJson.';</script>';
        if (! str_contains($html, 'window.__SITE_BOOT__')) {
            $html = str_replace('<script type="module"', $bootScript."\n  ".'<script type="module"', $html);
        }

        $panelFixCss = '<style id="panel-compat-fixes">'
            .'.fixed.inset-0 select, .fixed.inset-0 .overflow-hidden, [class*="rounded-3xl"].overflow-hidden{overflow:visible!important}'
            .'select option{background:#0a0a0a;color:#fff}'
            .'</style>';
        if (! str_contains($html, 'panel-compat-fixes')) {
            $html = str_replace('</head>', $panelFixCss.'</head>', $html);
        }

        // Sidebar link enjeksiyonu ve token doğrulama scripti
        $sidebarScript = <<<'HTML'
<script id="panel-sidebar-inject">
(function() {
    function injectLinks() {
        if (!window.location.pathname.startsWith('/panel')) return;
        
        // Find anchor pointing to special-odds
        const anchor = Array.from(document.querySelectorAll('a')).find(a => {
            const href = a.getAttribute('href');
            return href && href.includes('special-odds');
        });
        
        if (!anchor || document.getElementById('custom-league-link')) return;

        // Helper to replace text in cloned element
        function customizeLink(clone, id, href, text) {
            clone.id = id;
            clone.setAttribute('href', href);
            // Remove active classes
            clone.classList.remove('bg-emerald-500/10', 'text-emerald-500', 'router-link-active', 'router-link-exact-active');
            
            // Find any child with text "Özel Oran" and change it
            const textNodes = Array.from(clone.querySelectorAll('*')).filter(el => el.children.length === 0);
            textNodes.forEach(node => {
                if (node.textContent.includes('Özel Oran')) {
                    node.textContent = text;
                }
            });
            return clone;
        }

        const leagueLink = customizeLink(anchor.cloneNode(true), 'custom-league-link', '/panel/leagues', 'Lig Yönetimi');
        const teamLink = customizeLink(anchor.cloneNode(true), 'custom-team-link', '/panel/teams', 'Takım Yönetimi');

        anchor.parentNode.insertBefore(leagueLink, anchor.nextSibling);
        leagueLink.parentNode.insertBefore(teamLink, leagueLink.nextSibling);
    }

    const observer = new MutationObserver((mutations) => {
        injectLinks();
    });
    observer.observe(document.body, { childList: true, subtree: true });
    window.addEventListener('load', injectLinks);
})();
</script>
HTML;

        if (! str_contains($html, 'panel-sidebar-inject')) {
            $html = str_replace('</body>', $sidebarScript."\n</body>", $html);
        }

        return $html;
    }
}
