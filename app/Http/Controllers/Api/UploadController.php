<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $request->file('file')->store('uploads', 'public');

        return response()->json([
            'status' => 'success',
            'message' => 'File uploaded',
            'data' => [
                'path' => $path,
                'url' => asset('storage/'.$path),
            ],
        ], 201);
    }
}
