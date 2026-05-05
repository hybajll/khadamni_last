<?php

namespace App\Service;

final class MockArticlesService
{
    /**
     * @return array<int, array{lang: 'fr'|'ar', title: string, date: \DateTimeImmutable, summary: string, content: string}>
     */
    public function getLatest(): array
    {
        $today = new \DateTimeImmutable('today');

        return [
            [
                'lang' => 'fr',
                'title' => 'Marché de l’emploi : ce que les recruteurs regardent en premier',
                'date' => $today->sub(new \DateInterval('P1D')),
                'summary' => 'En quelques secondes, un recruteur scanne votre CV : titre, mots-clés, projets, et clarté.',
                'content' => "Un bon CV facilite la lecture : un titre clair, des sections visibles (Profil, Compétences, Projets), et des mots-clés liés au poste.\n\nAstuce : mettez en avant vos technologies et projets concrets, même académiques.",
            ],
            [
                'lang' => 'fr',
                'title' => 'CV : 5 erreurs fréquentes à éviter',
                'date' => $today->sub(new \DateInterval('P3D')),
                'summary' => 'Un CV trop long, sans structure, ou sans résultats mesurables peut vous pénaliser.',
                'content' => "Évitez : fautes d’orthographe, informations inutiles, expériences non datées, et compétences trop vagues.\n\nPréférez : des phrases courtes, des listes, et un ordre logique.",
            ],
            [
                'lang' => 'ar',
                'title' => 'شنوّة يهمّ الريكروتور في السيرة الذاتية؟',
                'date' => $today->sub(new \DateInterval('P2D')),
                'summary' => 'أوّل نظرة تكون سريعة: العنوان، المهارات، المشاريع، والتنظيم.',
                'content' => "السيرة الذاتية لازم تكون واضحة ومنظمة: نبذة قصيرة، مهارات تقنية، مشاريع، ودراسة.\n\nركّز على الكلمات المفتاحية اللي تطلبها الخدمة/التدريب.",
            ],
            [
                'lang' => 'ar',
                'title' => 'كيفاش تزيد فرصتك في القبول؟',
                'date' => $today->sub(new \DateInterval('P5D')),
                'summary' => 'تنظيم المحتوى، تصحيح اللغة، وإبراز المشاريع يخلي CV أقوى.',
                'content' => "حطّ مشاريعك حتى كان صغيرة، وبيّن شنوّة استعملت (Symfony, PHP, SQL...)\n\nوخلي CV نظيف: نفس الخط، نفس التنسيق، ومعلومات دقيقة.",
            ],
        ];
    }
}

