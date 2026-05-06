<?php

namespace App\Service;

/**
 * Builds a simple 2-column CV layout from parsed sections.
 *
 * Left column: personal/contact + skills-like sections.
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
        // Include both correct UTF-8 and common mojibake variants we saw in templates.
        $leftKeywords = [
            // French / English
            'contact',
            'coordonnées',
            'coordonnees',
            'coordonnÃ©es',
            'informations personnelles',
            'compétences',
            'compétences techniques',
            'compÃ©tences',
            'competences',
            'skills',
            'langues',
            'languages',
            'certifications',
            'certification',
            'centres d’intérêt',
            "centres d'intérêt",
            "centres d'interet",
            'centres dâ€™intÃ©rÃªt',
            "centres d'intÃ©rÃªt",
            'interests',

            // Arabic
            'الاتصال',
            'التواصل',
            'المهارات',
            'الكفاءات',
            'اللغات',
            'الشهادات',
        ];

        foreach ($leftKeywords as $needle) {
            $needleLower = mb_strtolower($needle);
            if ($lowerTitle === $needleLower || str_contains($lowerTitle, $needleLower)) {
                return true;
            }
        }

        return false;
    }
}

