<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentTypeRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentTypeRuleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(DocumentTypeRule::orderBy('document_type')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document_type'  => 'required|string|max:50|unique:document_type_rules,document_type',
            'label'          => 'required|string|max:100',
            'validity_years' => 'required|integer|min:1|max:99',
            'offset_days'    => 'required|integer|min:-365|max:365',
            'is_active'      => 'boolean',
        ]);

        $rule = DocumentTypeRule::create($data);
        return response()->json($rule, 201);
    }

    public function update(Request $request, DocumentTypeRule $documentTypeRule): JsonResponse
    {
        $data = $request->validate([
            'document_type'  => 'sometimes|string|max:50|unique:document_type_rules,document_type,' . $documentTypeRule->id,
            'label'          => 'sometimes|string|max:100',
            'validity_years' => 'sometimes|integer|min:1|max:99',
            'offset_days'    => 'sometimes|integer|min:-365|max:365',
            'is_active'      => 'boolean',
        ]);

        $documentTypeRule->update($data);
        return response()->json($documentTypeRule);
    }

    public function destroy(DocumentTypeRule $documentTypeRule): JsonResponse
    {
        $documentTypeRule->delete();
        return response()->json(['message' => 'ลบสำเร็จ']);
    }
}
