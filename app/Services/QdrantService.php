<?php

namespace App\Services;

class QdrantService
{
    private string $baseUrl;
    private string $collection;

    public function __construct()
    {
        $this->baseUrl    = env('QDRANT_URL');
        $this->collection = env('QDRANT_COLLECTION');
    }

    public function search(array $vector, int $limit = 5, float $scoreThreshold = 0.60): array
    {
        try {
            $payload = [
                'vector'          => $vector,
                'limit'           => $limit,
                'score_threshold' => $scoreThreshold,
                'with_payload'    => true,
                'with_vector'     => false,
            ];

            $response = $this->post("/collections/{$this->collection}/points/search", $payload);

            return array_map(fn($point) => [
                'text'  => $point['payload']['content'] ?? '',
                'title' => $point['payload']['title']   ?? '',
                'score' => $point['score']              ?? 0,
            ], $response['result'] ?? []);

        } catch (\Exception $e) {
            error_log('Qdrant Error: ' . $e->getMessage());
            return [];
        }
    }

    private function post(string $path, array $payload): array
    {
        $ch = curl_init($this->baseUrl . $path);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("cURL Error: $error");
        }

        curl_close($ch);

        return json_decode($response, true) ?? [];
    }
}