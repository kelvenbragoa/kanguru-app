<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\MediaPath;
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

        $stored = $request->file('file')->store('uploads', 'public');
        $path = MediaPath::normalize('storage/'.$stored);

        return response()->json([
            'status' => 'success',
            'message' => 'File uploaded',
            'data' => [
                'path' => $path,
                // Keep `url` as the same relative path so clients never persist a host.
                'url' => $path,
            ],
        ], 201);
    }
}
