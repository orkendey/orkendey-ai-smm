<?php

declare(strict_types=1);

namespace App\Socialite;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class InstagramProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopes = [
        'instagram_business_basic',
        'instagram_business_content_publish',
    ];

    protected function getAuthUrl($state): string
    {
        return 'https://www.instagram.com/oauth/authorize?'.http_build_query([
            'force_reauth' => 'true',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'response_type' => 'code',
            'scope' => implode(',', $this->getScopes()),
            'state' => $state,
        ]);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.instagram.com/oauth/access_token';
    }

    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(config('trypost.platforms.instagram.graph_api').'/me', [
            RequestOptions::QUERY => [
                'access_token' => $token,
                'fields' => 'id,username,account_type,name,profile_picture_url',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['username'] ?? null,
            'name' => $user['name'] ?? $user['username'] ?? null,
            'avatar' => $user['profile_picture_url'] ?? null,
        ]);
    }

    public function getAccessTokenResponse($code): array
    {
        $code = trim((string) $code);
        $code = preg_replace('/(?:#_|%23_)$/i', '', $code) ?? $code;

        // Meta documents this endpoint using multipart/form-data.
        $multipart = [];
        foreach ($this->getTokenFields($code) as $name => $contents) {
            $multipart[] = ['name' => $name, 'contents' => (string) $contents];
        }

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::MULTIPART => $multipart,
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return $this->exchangeForLongLivedToken($data);
    }

    protected function exchangeForLongLivedToken(array $data): array
    {
        $response = $this->getHttpClient()->get(config('trypost.platforms.instagram.auth_api').'/access_token', [
            RequestOptions::QUERY => [
                'grant_type' => 'ig_exchange_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'access_token' => data_get($data, 'access_token'),
            ],
        ]);

        $longLivedData = json_decode((string) $response->getBody(), true);

        return array_merge($data, [
            'access_token' => $longLivedData['access_token'],
            'expires_in' => $longLivedData['expires_in'] ?? null,
        ]);
    }

    protected function getTokenFields($code): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUrl,
            'code' => $code,
        ];
    }
}
