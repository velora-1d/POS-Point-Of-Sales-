<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PakasirService
{
    public function buildQrisCheckoutUrl(string $orderId, int $amount, string $redirectUrl): string
    {
        $config = $this->getConfig();

        return sprintf(
            '%s/pay/%s/%d?%s',
            rtrim($config['base_url'], '/'),
            $config['slug'],
            $amount,
            http_build_query([
                'order_id' => $orderId,
                'redirect' => $redirectUrl,
                'qris_only' => 1,
            ]),
        );
    }

    public function getTransactionDetail(string $orderId, int $amount): array
    {
        $config = $this->getConfig();

        return $this->sendJsonRequest('GET', '/api/transactiondetail', [
            'project' => $config['slug'],
            'amount' => $amount,
            'order_id' => $orderId,
            'api_key' => $config['api_key'],
        ]);
    }

    public function cancelTransaction(string $orderId, int $amount): array
    {
        $config = $this->getConfig();

        return $this->sendJsonRequest('POST', '/api/transactioncancel', [
            'project' => $config['slug'],
            'amount' => $amount,
            'order_id' => $orderId,
            'api_key' => $config['api_key'],
        ]);
    }

    protected function sendJsonRequest(string $method, string $path, array $payload): array
    {
        $config = $this->getConfig();

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(15)
                ->send($method, rtrim($config['base_url'], '/').$path, [
                    $method === 'GET' ? 'query' : 'json' => $payload,
                ])
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Gagal menghubungi gateway Pakasir.', previous: $exception);
        }

        return $response->json() ?? [];
    }

    protected function getConfig(): array
    {
        $config = [
            'api_key' => config('services.pakasir.api_key'),
            'base_url' => config('services.pakasir.base_url'),
            'slug' => config('services.pakasir.slug'),
        ];

        if (!$config['api_key'] || !$config['slug'] || !$config['base_url']) {
            throw new RuntimeException('Konfigurasi Pakasir belum lengkap.');
        }

        return $config;
    }
}
