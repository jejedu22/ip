<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client de l'API CaptchEtat (PISTE / AIFE).
 *
 * Le captcha est inactif tant que CAPTCHETAT_CLIENT_ID est vide (développement, tests).
 * L'image est récupérée côté serveur puis servie par l'application : le navigateur ne contacte jamais PISTE
 * et les identifiants OAuth ne quittent pas le serveur.
 */
class CaptchEtat
{
    private const ENDPOINTS = [
        'prod' => ['oauth' => 'https://oauth.piste.gouv.fr', 'api' => 'https://api.piste.gouv.fr'],
        'sandbox' => ['oauth' => 'https://sandbox-oauth.piste.gouv.fr', 'api' => 'https://sandbox-api.piste.gouv.fr'],
    ];

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(CAPTCHETAT_CLIENT_ID)%')]
        private readonly string $clientId,
        #[Autowire('%env(CAPTCHETAT_CLIENT_SECRET)%')]
        private readonly string $clientSecret,
        #[Autowire('%env(CAPTCHETAT_ENV)%')]
        private readonly string $env,
    ) {
    }

    public function isEnabled(): bool
    {
        return '' !== $this->clientId && '' !== $this->clientSecret;
    }

    public function newId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function isValidId(string $id): bool
    {
        return 1 === preg_match('/^[a-f0-9]{32}$/', $id);
    }

    /**
     * @return array{0: string, 1: string}|null [contenu, type MIME] ou null si le service est injoignable
     */
    public function image(string $id): ?array
    {
        try {
            $response = $this->http->request('GET', $this->api().'/piste/captcha/simple-captcha-endpoint', [
                'query' => ['get' => 'image', 'c' => 'captchaFR', 't' => $id],
                'auth_bearer' => $this->token(),
                'timeout' => 5,
            ]);

            return [$response->getContent(), $response->getHeaders()['content-type'][0] ?? 'image/jpeg'];
        } catch (\Throwable $e) {
            $this->logger->error('CaptchEtat : image indisponible.', ['exception' => $e::class]);

            return null;
        }
    }

    /**
     * Valide la saisie. Si PISTE est injoignable on laisse passer (disponibilité d'un signalement
     * avant tout) : le champ piège et la limitation de débit restent actifs.
     */
    public function validate(string $id, string $code): bool
    {
        if (!self::isValidId($id) || '' === trim($code)) {
            return false;
        }

        try {
            $response = $this->http->request('POST', $this->api().'/piste/captcha/valider-captcha', [
                'json' => ['id' => $id, 'code' => trim($code)],
                'auth_bearer' => $this->token(),
                'timeout' => 5,
            ]);

            return 'true' === strtolower(trim($response->getContent()));
        } catch (\Throwable $e) {
            $this->logger->error('CaptchEtat : validation impossible, captcha ignoré.', ['exception' => $e::class]);

            return true;
        }
    }

    private function api(): string
    {
        return (self::ENDPOINTS[$this->env] ?? self::ENDPOINTS['prod'])['api'];
    }

    private function token(): string
    {
        return $this->cache->get('captchetat_token_'.$this->env, function (ItemInterface $item): string {
            $oauth = (self::ENDPOINTS[$this->env] ?? self::ENDPOINTS['prod'])['oauth'];
            $data = $this->http->request('POST', $oauth.'/api/oauth/token', [
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'piste.captchetat',
                ],
                'timeout' => 5,
            ])->toArray();

            // Jeton valable 3600 s : on le renouvelle un peu avant.
            $item->expiresAfter(max(60, ((int) ($data['expires_in'] ?? 3600)) - 120));

            return (string) $data['access_token'];
        });
    }
}
