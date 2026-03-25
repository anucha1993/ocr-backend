<?php

namespace App\Services;

use App\Models\ApiProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoTokenService
{
    /**
     * Get a valid access token for the given provider.
     * Auto-refreshes if expired.
     */
    public function getAccessToken(ApiProvider $provider): ?string
    {
        if (!$provider->isTokenExpired() && $provider->access_token) {
            return $provider->access_token;
        }

        return $this->refreshToken($provider);
    }

    /**
     * Refresh the access token using the refresh_token grant.
     */
    public function refreshToken(ApiProvider $provider): ?string
    {
        if (!$provider->token_url || !$provider->refresh_token) {
            return null;
        }

        try {
            $response = Http::asForm()->post($provider->token_url, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $provider->refresh_token,
                'client_id' => $provider->client_id,
                'client_secret' => $provider->client_secret,
            ]);

            if ($response->successful() && $response->json('access_token')) {
                $expiresIn = $response->json('expires_in', 3600);

                $provider->update([
                    'access_token' => $response->json('access_token'),
                    'token_expires_at' => now()->addSeconds($expiresIn - 60),
                ]);

                return $response->json('access_token');
            }

            Log::error('Token refresh failed for provider: ' . $provider->slug, [
                'response' => $response->json(),
            ]);
        } catch (\Exception $e) {
            Log::error('Token refresh exception for provider: ' . $provider->slug, [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
