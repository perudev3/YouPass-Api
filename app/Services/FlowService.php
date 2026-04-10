<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FlowService
{
    protected string $apiKey;
    protected string $secretKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey    = config('services.flow.api_key');
        $this->secretKey = config('services.flow.secret_key');
        $this->apiUrl    = config('services.flow.api_url');
    }

    /**
     * Firma los parámetros y hace POST a un endpoint de Flow.
     */
    public function post(string $endpoint, array $params): array
    {
        $params['apiKey'] = $this->apiKey;

        // Ordenar alfabéticamente y concatenar para firmar
        ksort($params);
        $toSign = '';
        foreach ($params as $key => $value) {
            $toSign .= $key . $value;
        }

        $params['s'] = hash_hmac('sha256', $toSign, $this->secretKey);

        $response = Http::asForm()->post("{$this->apiUrl}/{$endpoint}", $params);
        // ← Agrega esto temporalmente
    \Log::info('Flow request params', $params);
    \Log::info('Flow response', $response->json() ?? ['raw' => $response->body()]);

        return $response->json();
    }

    /**
     * Firma y hace GET (para consultar estado de pago).
     */
    public function get(string $endpoint, array $params): array
    {
        $params['apiKey'] = $this->apiKey;

        ksort($params);
        $toSign = '';
        foreach ($params as $key => $value) {
            $toSign .= $key . $value;
        }

        $params['s'] = hash_hmac('sha256', $toSign, $this->secretKey);

        $response = Http::get("{$this->apiUrl}/{$endpoint}", $params);

        return $response->json();
    }
}