<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdCardReaderSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdCardReaderSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(IdCardReaderSetting::current());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ws_host'      => 'required|string|max:255',
            'ws_port'      => 'required|integer|min:1|max:65535',
            'auto_connect' => 'boolean',
            'auto_save'    => 'boolean',
        ]);

        $setting = IdCardReaderSetting::current();
        $setting->update($validated);

        return response()->json([
            'message' => 'Settings saved',
            'setting' => $setting,
        ]);
    }
}
