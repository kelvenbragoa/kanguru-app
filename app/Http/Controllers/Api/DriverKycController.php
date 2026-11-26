<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverKycController extends Controller
{
    public function show(Request $request)
    {
        $verification = DriverVerification::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['status' => DriverVerification::STATUS_PENDING]
        );

        return response()->json(['status' => 'success', 'data' => $verification]);
    }

    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_number' => 'required|string|max:64',
            'license_image' => 'required|string|max:500',
            'insurance_image' => 'required|string|max:500',
            'vehicle_reg_image' => 'required|string|max:500',
            'criminal_record_image' => 'nullable|string|max:500',
            'id_document_image' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $verification = DriverVerification::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                ...$request->only([
                    'license_number', 'license_image', 'insurance_image',
                    'vehicle_reg_image', 'criminal_record_image', 'id_document_image',
                ]),
                'status' => DriverVerification::STATUS_PENDING,
                'submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'admin_notes' => null,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Documentos enviados para análise',
            'data' => $verification,
        ]);
    }

    public function adminIndex(Request $request)
    {
        $query = DriverVerification::query()
            ->with(['user:id,name,email', 'user.profile:id,user_id,phone'])
            ->orderByDesc('updated_at');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(min(max($request->integer('per_page', 20), 1), 100)),
        ]);
    }

    public function review(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $verification = DriverVerification::query()->findOrFail($id);
        $verification->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $request->status === 'approved' ? 'Motorista aprovado' : 'KYC rejeitado',
            'data' => $verification->fresh(['user:id,name,email', 'user.profile:id,user_id,phone']),
        ]);
    }
}
