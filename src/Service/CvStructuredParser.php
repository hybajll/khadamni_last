<?php

namespace App\Service;

final class CvStructuredParser
{
    /**
     * Turns plain CV text into simple sections for rendering in HTML/PDF.
     *
     * @return array<int, array{title: string, items: string[]}>
     */
    public function parse(string $text): array
    {
        $clean = trim($text);
        if ($clean === '') {
            return [];
        }

        $clean = str_replace(["\r\n", "\r"], "\n", $clean);
        $lines = array_map('trim', explode("\n", $clean));

        $sections = [];
        $currentTitle = 'Contenu';
        $currentItems = [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            if ($this->isHeading($line)) {
                if ($currentItems !== []) {
                    $sections[] = [
                        'title' => $currentTitle,
                        'items' => $currentItems,
                    ];
                }

                $currentTitle = $this->normalizeHeading($line);
                $currentItems = [];
                continue;
            }

            $currentItems[] = $this->normalizeItem($line);
        }

        if ($currentItems !== []) {
            $sections[] = [
                'title' => $currentTitle,
                'items' => $currentItems,
            ];
        }

        // If we only got a single "catch-all" section, try to split inline headings
        // (common when text comes from PDF extraction or older AI output).
        if (count($sections) === 1 && mb_strtolower($sections[0]['title']) === 'contenu') {
            return $this->splitInlineHeadings($sections[0]['items']);
        }

        return $sections;
    }

    private function isHeading(string $line): bool
    {
        $line = trim($this->stripBulletPrefix($line));

        $known = [
            // French / English
            'résumé', 'resume', 'profil', 'profile',
            'compétences', 'competences', 'skills',
            'expériences', 'experiences', 'experience',
            'projets', 'projects', 'projets / expériences', 'expériences / projets',
            'formation', 'éducation', 'education',
            'langues', 'languages',
            'certifications', 'certification',
            'centres d’intérêt', "centres d'intérêt", 'interests',
            'contact', 'coordonnées', 'coordonnees',

            // Arabic (common CV headings)
            'الملخص', 'نبذة',
            'الخبرات', 'الخبرة', 'التجارب',
            'التعليم', 'الدراسة', 'التكوين',
            'المهارات', 'الكفاءات',
            'المشاريع', 'المشروع',
            'اللغات',
            'الشهادات',
            'التواصل', 'الاتصال',
        ];

        $lower = mb_strtolower(rtrim($line, ':'));
        if (in_array($lower, $known, true)) {
            return true;
        }

        // All-caps headings (e.g. "PROFIL", "FORMATION").
        $letters = preg_replace('/[^\\p{L}]+/u', '', $line) ?? '';
        if ($letters !== '' && mb_strtoupper($letters) === $letters && mb_strlen($letters) >= 4) {
            return true;
        }

        return str_ends_with($line, ':') && mb_strlen($line) <= 40;
    }

    private function normalizeHeading(string $line): string
    {
        $line = trim(rtrim($this->stripBulletPrefix($line), ':'));
        if ($line === '') {
            return 'Contenu';
        }

        // Keep headings readable; don't force full uppercase here (the AI already outputs ALL CAPS).
        return mb_strtoupper(mb_substr($line, 0, 1)).mb_substr($line, 1);
    }

    private function normalizeItem(string $line): string
    {
        $line = trim($this->stripBulletPrefix($line));
        return $line === '' ? '—' : $line;
    }

    private function stripBulletPrefix(string $line): string
    {
        $line = trim($line);

        // Common bullet prefixes (including mojibake variants).
        $prefixes = ['•', '-', '*', '–', '—', 'â€¢', 'â€“', 'â€”'];
        foreach ($prefixes as $p) {
            if (str_starts_with($line, $p.' ')) {
                return ltrim(substr($line, strlen($p)), " \t");
            }
            if (str_starts_with($line, $p)) {
                return ltrim(substr($line, strlen($p)), " \t");
            }
        }

        return $line;
    }

    /**
     * @param string[] $items
     * @return array<int, array{title: string, items: string[]}>
     */
    private function splitInlineHeadings(array $items): array
    {
        $sections = [];
        $currentTitle = 'Informations personnelles';
        $currentItems = [];

        foreach ($items as $item) {
            $maybeHeading = $this->stripBulletPrefix($item);
            // Remove common emoji prefixes that older content may contain.
            $maybeHeading = preg_replace('/^[^\\p{L}\\p{N}]+\\s*/u', '', $maybeHeading) ?? $maybeHeading;

            if ($maybeHeading !== '' && $this->isHeading($maybeHeading)) {
                if ($currentItems !== []) {
                    $sections[] = ['title' => $currentTitle, 'items' => $currentItems];
                }
                $currentTitle = $this->normalizeHeading($maybeHeading);
                $currentItems = [];
                continue;
            }

            $currentItems[] = $item;
        }

        if ($currentItems !== []) {
            $sections[] = ['title' => $currentTitle, 'items' => $currentItems];
        }

        return $sections !== [] ? $sections : [['title' => 'Contenu', 'items' => $items]];
    }
}
