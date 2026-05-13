<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Reclamation;
use App\Repository\ReclamationRepository;
use App\Repository\ReponseReclamationRepository;

class AiAssistantService
{
    private $client;
    private $apiKey;
    private $model;
    private $reclamationRepository;
    private $reponseRepository;

    public function __construct(
        HttpClientInterface $client, 
        string $geminiApiKey, 
        string $geminiModel,
        ReclamationRepository $reclamationRepository,
        ReponseReclamationRepository $reponseRepository
    ) {
        $this->client = $client;
        $this->apiKey = $geminiApiKey;
        $this->model = $geminiModel;
        $this->reclamationRepository = $reclamationRepository;
        $this->reponseRepository = $reponseRepository;
    }

    /**
     * Génère la réponse IA en utilisant prioritairement la base de données.
     * Le ? devant string permet d'accepter une valeur null sans erreur PHP.
     */
    public function generateAiResponse(string $typeReclamationValue, ?string $contextSolution = null, ?string $description = null): string
    {
        // 1. PRIORITÉ : Utiliser la réponse fournie en contexte
        if ($contextSolution && !empty(trim($contextSolution))) {
            return $this->adaptExistingResponse($contextSolution, $description);
        }

        // 2. CHERCHER dans la base de données une réponse similaire
        $existingResponse = $this->findSimilarResponseInDatabase($typeReclamationValue, $description);
        if ($existingResponse) {
            return $this->adaptExistingResponse($existingResponse, $description);
        }

        // 3. FALLBACK : Réponse prédéfinie
        return $this->getFallbackResponse($typeReclamationValue);
    }

    /**
     * Cherche une réponse similaire dans les réponses existantes de la base
     */
    private function findSimilarResponseInDatabase(string $typeReclamationValue, ?string $description): ?string
    {
        // Chercher les réponses récentes pour ce type
        $recentResponses = $this->reponseRepository->findRecentResponsesByType($typeReclamationValue, 10);
        
        if (empty($recentResponses)) {
            return null;
        }

        // Si on a une description, essayer de trouver une correspondance plus précise
        if ($description && !empty(trim($description))) {
            foreach ($recentResponses as $response) {
                if ($this->isResponseRelevant($response->getMessage(), $description)) {
                    return $response->getMessage();
                }
            }
        }

        // Sinon, retourner la réponse la plus récente pour ce type
        return $recentResponses[0]->getMessage();
    }

    /**
     * Adapte une réponse existante au contexte actuel
     */
    private function adaptExistingResponse(string $baseResponse, ?string $currentDescription): string
    {
        $adaptedResponse = trim($baseResponse);
        
        // Si la réponse est trop courte et qu'on a une description, ajouter contexte
        if (strlen($adaptedResponse) < 80 && $currentDescription) {
            $adaptedResponse .= "\n\nConcernant votre situation spécifique, nous analysons votre demande et reviendrons vers vous rapidement.";
        }

        // S'assurer qu'il y a un contact support
        if (!str_contains($adaptedResponse, 'support@khadamni.tn')) {
            $adaptedResponse .= "\n\nContact si besoin : support@khadamni.tn";
        }

        return $adaptedResponse;
    }

    /**
     * Vérifie si une réponse est pertinente pour une description donnée
     */
    private function isResponseRelevant(string $response, string $description): bool
    {
        $descriptionLower = strtolower($description);
        $responseLower = strtolower($response);
        
        // Mots-clés importants à chercher
        $keyWords = ['connexion', 'mot de passe', 'cv', 'compte', 'offre', 'emploi', 'stage', 'profil', 'candidature'];
        
        $matchCount = 0;
        foreach ($keyWords as $word) {
            if (str_contains($descriptionLower, $word) && str_contains($responseLower, $word)) {
                $matchCount++;
            }
        }
        
        // Si au moins 1 mot-clé en commun, considérer comme pertinent
        return $matchCount >= 1;
    }

    private function getFallbackResponse(string $type): string {
    
        $responses = [
            'CONNEXION' => " **Problème de connexion**\n\n1. Vérifiez votre email/mot de passe\n2. Utilisez 'Mot de passe oublié'\n3. Vérifiez vos spams\n4. Contactez-nous si le problème persiste\n\nContact : support@khadamni.tn",
            'OFFRE_EMPLOI' => " **Offre d'emploi inaccessible**\n\nSolutions :\n• Rafraîchissez la page (Ctrl+F5)\n• Vérifiez votre connexion internet\n• Utilisez un autre navigateur\n• Contactez l'entreprise émettrice\n\nNotre équipe investigate l'incident.\n\nContact : support@khadamni.tn",
            'CV' => " **Problème de CV**\n\n1. Vérifiez le format (PDF recommandé)\n2. Taille maximale : 5 Mo\n3. Utilisez notre outil de mise en ligne\n4. Support technique : cv@khadamni.tn\n\nContact : support@khadamni.tn",
            'COMPTE' => " **Problème de compte**\n\n• Vérifiez votre email de confirmation\n• Complétez votre profil\n• Mettez à jour vos documents\n• Contactez notre service client\n\nContact : support@khadamni.tn",
            'STAGE' => " **Recherche de stage**\n\n• Consultez notre section dédiée aux stages\n• Mettez à jour votre profil\n• Postulez aux offres correspondantes\n• Activez les alertes emploi\n\nContact : support@khadamni.tn"
        ];
        
        return $responses[$type] ?? "Bonjour, nous avons bien reçu votre réclamation concernant : $type. Notre équipe support analyse votre demande et reviendra vers vous rapidement.";
    }

    public function processNewReclamation(Reclamation $reclamation): bool
    {
        $texte = strtolower($reclamation->getDescription() ?? '');
        $motsCles = ['mot de passe', 'connexion', 'cv', 'compte', 'societe', 'offre', 'emploi', 'stage', 'inscription', 'profil', 'candidature', 'recrutement', 'document', 'fichier'];

        foreach ($motsCles as $mot) {
            if (str_contains($texte, $mot)) {
                return true;
            }
        }
        
        $motsQuestions = ['comment', 'pourquoi', 'quand', 'où', 'quel', 'probleme', 'erreur'];
        foreach ($motsQuestions as $mot) {
            if (str_contains($texte, $mot)) {
                return true;
            }
        }

        return false;
    }
}