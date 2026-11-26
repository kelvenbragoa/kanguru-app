<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SiteLeadController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'formulario' => 'required|string|in:contacto,parceiro,colaborador,lista',
            'nome' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:40',
            'assunto' => 'nullable|string|max:120',
            'mensagem' => 'nullable|string|max:5000',
            'pagina' => 'nullable|string|max:500',
            'website' => 'nullable|string|max:120', // honeypot mirror if sent
        ]);

        if ($validator->fails()) {
            $messages = collect($validator->errors()->all())->map(fn ($message) => ['message' => $message])->values();

            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $messages,
            ], 422);
        }

        // Ignore obvious bot honeypot fields if present
        if ($request->filled('website') || $request->filled('company_url')) {
            return response()->json(['status' => 'success', 'message' => 'OK'], 201);
        }

        $data = $validator->validated();
        $payload = collect($request->all())
            ->except(['_subject', 'enviado_em'])
            ->all();

        SiteLead::create([
            'formulario' => $data['formulario'],
            'nome' => $data['nome'] ?? $request->input('name'),
            'email' => $data['email'] ?? null,
            'telefone' => $data['telefone'] ?? $request->input('phone'),
            'assunto' => $data['assunto'] ?? null,
            'mensagem' => $data['mensagem'] ?? $request->input('message'),
            'payload' => $payload,
            'pagina' => $data['pagina'] ?? null,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Recebido. Obrigado.',
        ], 201);
    }
}
