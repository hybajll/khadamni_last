<?php

namespace App\Service;

/**
 * Builds a simple 2-column CV layout from parsed sections.
 * Left column: contact + skills-like sections.
 * Right column: profile + education + experience + projects, etc.
 */
final class CvLayoutBuilder
{
    /**
     * @param array<int, array{title: string, items: string[]}> $sections
     * @return array{left: array<int, array{title: string, items: string[]}>, right: array<int, array{title: string, items: string[]}>}
     */
    public function build(array $sections): array
    {
        $left = [];
        $right = [];

        foreach ($sections as $section) {
            $title = trim((string) ($section['title'] ?? ''));
            $key = mb_strtolower($title);

            if ($this->isLeftColumnSection($key)) {
                $left[] = $section;
            } else {
                $right[] = $section;
            }
        }

        // Keep something on the right even if everything matched the left keywords.
        if ($right === [] && $left !== []) {
            $right[] = array_shift($left);
        }

        return [
            'left' => $left,
            'right' => $right,
        ];
    }

    private function isLeftColumnSection(string $lowerTitle): bool
    {
        $leftKeywords = [
            // French / English
            'contact',
            'coordonnées',
            'informations personnelles',
            'compétences',
            'competences',
            'skills',
            'langues',
            'languages',
            'certifications',
            'certification',
            'centres d’intérêt',
            "centres d'intérêt",
            'interests',

            // Arabic
            'التواصل',
            'الاتصال',
            'المهارات',
            'الكفاءات',
            'اللغات',
            'الشهادات',
        ];

        foreach ($leftKeywords as $needle) {
            if ($lowerTitle === $needle || str_contains($lowerTitle, $needle)) {
                return true;
            }
        }

        return false;
    }
}

