<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PassportMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PassportMappingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            PassportMapping::orderBy('name')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'doc_type_code'  => [
                'required', 'string', 'max:10',
                Rule::unique('passport_mappings')->where('country_code', $request->country_code),
            ],
            'country_code'   => 'required|string|max:10',
            'field_map'      => 'required|array|min:1',
            'field_map.*.index' => 'required|integer|min:0',
            'field_map.*.field' => 'required|string',
            'date_format'    => 'nullable|string|max:20',
            'separator'      => 'nullable|string|max:5',
            'is_active'      => 'nullable|boolean',
        ]);

        $validated['date_format'] = $validated['date_format'] ?? 'YYMMDD';
        $validated['separator']   = $validated['separator'] ?? '#';
        $validated['is_active']   = $validated['is_active'] ?? true;

        $mapping = PassportMapping::create($validated);

        return response()->json([
            'message' => 'Mapping created',
            'mapping' => $mapping,
        ], 201);
    }

    public function show(PassportMapping $passportMapping): JsonResponse
    {
        return response()->json($passportMapping);
    }

    public function update(Request $request, PassportMapping $passportMapping): JsonResponse
    {
        $validated = $request->validate([
            'name'           => 'sometimes|required|string|max:100',
            'doc_type_code'  => [
                'sometimes', 'required', 'string', 'max:10',
                Rule::unique('passport_mappings')->where('country_code', $request->country_code ?? $passportMapping->country_code)->ignore($passportMapping->id),
            ],
            'country_code'   => 'sometimes|required|string|max:10',
            'field_map'      => 'sometimes|required|array|min:1',
            'field_map.*.index' => 'required_with:field_map|integer|min:0',
            'field_map.*.field' => 'required_with:field_map|string',
            'date_format'    => 'nullable|string|max:20',
            'separator'      => 'nullable|string|max:5',
            'is_active'      => 'nullable|boolean',
        ]);

        $passportMapping->update($validated);

        return response()->json([
            'message' => 'Mapping updated',
            'mapping' => $passportMapping->fresh(),
        ]);
    }

    public function destroy(PassportMapping $passportMapping): JsonResponse
    {
        $passportMapping->delete();

        return response()->json(['message' => 'Mapping deleted']);
    }
}
