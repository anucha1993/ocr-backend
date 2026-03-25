<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Labour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdCardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Labour::where('user_id', $request->user()->id)->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%")
                  ->orWhere('id_card', 'like', "%{$search}%")
                  ->orWhere('passport_no', 'like', "%{$search}%")
                  ->orWhere('nationality', 'like', "%{$search}%");
            });
        }

        $labours = $query->paginate($request->input('per_page', 20));

        return response()->json($labours);
    }

    public function destroy(Labour $labour): JsonResponse
    {
        $labour->delete();

        return response()->json(['message' => 'ลบข้อมูลสำเร็จ']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => 'nullable|string|in:idcard,passport',
            'id_card'       => 'nullable|string|max:20',
            'passport_no'   => 'nullable|string|max:20',
            'prefix'        => 'nullable|string|max:50',
            'firstname'     => 'required|string|max:255',
            'lastname'      => 'required|string|max:255',
            'birthdate'     => 'nullable|date',
            'address'       => 'nullable|string',
            'nationality'   => 'nullable|string|max:100',
            'issue_date'    => 'nullable|date',
            'expiry_date'   => 'nullable|date',
            'photo'         => 'nullable|string',
        ]);

        $validated['user_id'] = $request->user()->id;

        // Determine unique key: passport_no or id_card (scoped to this user)
        if (!empty($validated['passport_no'])) {
            $labour = Labour::updateOrCreate(
                ['passport_no' => $validated['passport_no'], 'user_id' => $validated['user_id']],
                $validated
            );
        } elseif (!empty($validated['id_card'])) {
            $labour = Labour::updateOrCreate(
                ['id_card' => $validated['id_card'], 'user_id' => $validated['user_id']],
                $validated
            );
        } else {
            return response()->json(['โดยย่อ' => 'กรุณาระบุเลขบัตรประชาชนหรือเลข Passport'], 422);
        }

        return response()->json([
            'ผล' => $labour->wasRecentlyCreated ? 'เพิ่มข้อมูลเรียบร้อย' : 'อัปเดตข้อมูลเรียบร้อย',
            'สมาชิก' => $labour,
        ], $labour->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Labour $labour): JsonResponse
    {
        return response()->json($labour);
    }
}
