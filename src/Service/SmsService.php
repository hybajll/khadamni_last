<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service d'envoi de SMS via Twilio.
 *
 * ─── Configuration requise dans .env ───────────────────────────────────────
 *   TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
 *   TWILIO_AUTH_TOKEN=your_auth_token
 *   TWILIO_FROM_NUMBER=+216XXXXXXXX   (numéro Twilio acheté)
 * ────────────────────────────────────────────────────────────────────────────
 *
 * Alternative gratuite : remplacer l'implémentation par Orange SMS API
 * ou Infobip selon votre opérateur tunisien préféré.
 */
class SmsService
{
    private const TWILIO_API_URL = 'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface     $logger,
        private readonly ?string $twilioAccountSid = null,
        private readonly ?string $twilioAuthToken = null,
        private readonly ?string $twilioFromNumber = null,
    ) {}

    /**
     * Envoie un SMS à un numéro donné.
     *
     * @param string $to      Numéro destinataire au format E.164 (ex: +21698XXXXXX)
     * @param string $message Texte du SMS (max 160 caractères recommandé)
     *
     * @return bool true si envoyé avec succès
     */
    public function send(string $to, string $message): bool
    {
        // Mode "projet académique": si Twilio n'est pas configuré, on ne bloque pas l'appli.
        if (!$this->twilioAccountSid || !$this->twilioAuthToken || !$this->twilioFromNumber) {
            $this->logger->info('SMS ignoré (Twilio non configuré).', [
                'to' => $to,
            ]);
            return true;
        }

        // Nettoyer le numéro (supprimer espaces, tirets)
        $to = preg_replace('/[\s\-]/', '', $to);

        // Ajouter l'indicatif tunisien si absent
        if (!str_starts_with($to, '+')) {
            $to = '+216' . ltrim($to, '0');
        }

        try {
            $url = sprintf(self::TWILIO_API_URL, $this->twilioAccountSid);

            $response = $this->httpClient->request('POST', $url, [
                'auth_basic' => [$this->twilioAccountSid, $this->twilioAuthToken],
                'body'       => [
                    'From' => $this->twilioFromNumber,
                    'To'   => $to,
                    'Body' => $message,
                ],
            ]);

            $data = $response->toArray(false);

            if (isset($data['error_code'])) {
                $this->logger->error('SMS Twilio erreur', [
                    'to'         => $to,
                    'error_code' => $data['error_code'],
                    'message'    => $data['message'] ?? '',
                ]);
                return false;
            }

            $this->logger->info('SMS envoyé avec succès', [
                'to'  => $to,
                'sid' => $data['sid'] ?? '',
            ]);

            return true;

        } catch (\Throwable $e) {
            $this->logger->error('SMS envoi échoué : ' . $e->getMessage(), [
                'to' => $to,
            ]);
            return false;
        }
    }
}
