<?php

namespace App\Models\Concerns;

trait MapsApiFields
{
    protected function normalizeUrl(?string $url): ?string
    {
        if (! $url) {
            return $url;
        }
        return preg_replace('#https?://[^/]+/storage/#', '/storage/', $url) ?? $url;
    }

    public function toApiArray(): array
    {
        $row = $this->toArray();
        if (array_key_exists('sort_order', $row)) {
            $row['order'] = $row['sort_order'];
        }
        foreach (['image_url', 'logo_url', 'icon_url', 'image_path', 'avatar'] as $field) {
            if (isset($row[$field])) {
                $row[$field] = $this->normalizeUrl($row[$field]);
            }
        }
        if (isset($row['logo_url'])) {
            $row['logo_full_url'] = $row['logo_url'];
        }
        if (isset($row['url'])) {
            $row['link'] = $row['url'];
        }
        if (isset($row['title'])) {
            $row['name'] = $row['title'];
        }
        $row['type'] = $row['icon'] ?? 'link';

        return $row;
    }
}
