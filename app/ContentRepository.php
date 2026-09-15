<?php

declare(strict_types=1);

final class ContentRepository
{
    public static function page(string $key, string $lang, bool $preview = false): ?array
    {
        $stmt = db()->prepare('SELECT p.page_key, pt.* FROM pages p JOIN page_translations pt ON pt.page_id = p.id WHERE p.page_key = ? AND pt.lang = ? LIMIT 1');
        $stmt->execute([$key, $lang]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $json = $preview ? $row['draft_json'] : $row['published_json'];
        if (!$preview && $row['published_at'] === null) {
            return null;
        }
        $row['content'] = json_decode((string) $json, true) ?: [];
        $row['seo_title'] = $preview ? $row['draft_seo_title'] : $row['published_seo_title'];
        $row['seo_description'] = $preview ? $row['draft_seo_description'] : $row['published_seo_description'];
        return $row;
    }

    public static function services(string $lang, bool $preview = false, bool $includeHidden = false): array
    {
        $where = $includeHidden ? '' : 'WHERE s.is_visible = 1';
        $stmt = db()->prepare("SELECT s.*, st.lang, st.draft_name, st.draft_description, st.published_name, st.published_description, st.translation_status, st.published_at FROM services s JOIN service_translations st ON st.service_id = s.id AND st.lang = ? {$where} ORDER BY s.sort_order, s.id");
        $stmt->execute([$lang]);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            if (!$preview && $row['published_at'] === null) {
                continue;
            }
            $row['name'] = $preview ? $row['draft_name'] : $row['published_name'];
            $row['description'] = $preview ? $row['draft_description'] : $row['published_description'];
            $rows[] = $row;
        }
        return $rows;
    }

    public static function settings(): array
    {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}

