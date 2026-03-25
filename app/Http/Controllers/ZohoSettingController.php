<?php

namespace App\Http\Controllers;

use App\Models\ZohoSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZohoSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = ZohoSetting::latest()->first();

        if (!$settings) {
            return response()->json([
                'exists' => false,
                'data' => null,
            ]);
        }

        return response()->json([
            'exists' => true,
            'data' => [
                'client_id' => $settings->client_id,
                'client_secret' => str_repeat('•', 20) . substr($settings->client_secret, -4),
                'refresh_token' => str_repeat('•', 20) . substr($settings->refresh_token, -4),
                'api_domain' => $settings->api_domain,
                'updated_at' => $settings->updated_at->toDateTimeString(),
            ],
        ]);
    }

    public function saveSettings(Request $request): JsonResponse
    {
        $existing = ZohoSetting::latest()->first();

        $validated = $request->validate([
            'client_id' => 'required|string|max:500',
            'client_secret' => [$existing ? 'nullable' : 'required', 'string', 'max:500'],
            'refresh_token' => [$existing ? 'nullable' : 'required', 'string', 'max:2000'],
            'api_domain' => 'nullable|url|max:255',
        ]);

        if (empty($validated['api_domain'])) {
            $validated['api_domain'] = 'https://www.zohoapis.com';
        }

        $data = array_filter($validated, fn($v) => $v !== null && $v !== '');

        if ($existing) {
            $existing->update($data);
        } else {
            ZohoSetting::create($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Zoho settings saved successfully.',
        ]);
    }

    public function testConnection(): JsonResponse
    {
        $settings = ZohoSetting::latest()->first();

        if (!$settings) {
            return response()->json(['success' => false, 'error' => 'No Zoho settings configured.']);
        }

        // Get access token
        $tokenResponse = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $settings->refresh_token,
            'client_id' => $settings->client_id,
            'client_secret' => $settings->client_secret,
        ]);

        if (!$tokenResponse->successful() || !$tokenResponse->json('access_token')) {
            return response()->json([
                'success' => false,
                'error' => $tokenResponse->json('error') ?? 'Failed to obtain access token.',
            ]);
        }

        $accessToken = $tokenResponse->json('access_token');
        $apiDomain = $settings->api_domain ?? 'https://www.zohoapis.com';

        // Test with current user
        $response = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $accessToken,
        ])->get($apiDomain . '/crm/v2/users?type=CurrentUser');

        if ($response->successful()) {
            $users = $response->json('users');
            return response()->json([
                'success' => true,
                'message' => 'Connected successfully to Zoho CRM.',
                'user' => $users[0]['full_name'] ?? 'Unknown',
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $response->json('message') ?? 'Failed to connect to Zoho CRM API.',
        ]);
    }

    public function refreshAccessToken(): JsonResponse
    {
        $settings = ZohoSetting::latest()->first();

        if (!$settings) {
            return response()->json(['success' => false, 'error' => 'No Zoho settings configured.'], 400);
        }

        $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $settings->refresh_token,
            'client_id' => $settings->client_id,
            'client_secret' => $settings->client_secret,
        ]);

        if ($response->successful() && $response->json('access_token')) {
            return response()->json([
                'success' => true,
                'message' => 'Access token refreshed successfully.',
                'expires_in' => $response->json('expires_in'),
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $response->json('error') ?? 'Failed to obtain access token.',
        ], 400);
    }
}
