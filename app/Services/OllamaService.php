<?php

namespace App\Services;

class OllamaService
{
    private string $baseUrl;
    private string $embeddingModel;

    public function __construct()
    {
        $this->baseUrl       = env('OLLAMA_URL');
        $this->embeddingModel = env('EMBEDDING_MODEL');
    }


    /**
     * Non-streaming chat — kembalikan full response sekaligus.
     */
    public function chat(string $model, array $messages, array $options = []): string
    {
        $payload = $this->buildPayload($model, $messages, false, $options);

        $ch = curl_init($this->baseUrl . '/api/chat');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['message']['content'] ?? '';
    }

 
    public function streamChat(
        string    $model,
        array     $messages,
        array     $options  = [],
        bool      $echo     = true,
        ?callable $onToken  = null
    ): string {
        $payload      = $this->buildPayload($model, $messages, true, $options);
        $fullResponse = '';

        $ch = curl_init($this->baseUrl . '/api/chat');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);

        curl_setopt($ch, CURLOPT_WRITEFUNCTION,
            function ($ch, $chunk) use (&$fullResponse, $onToken, $echo) {
                foreach (explode("\n", $chunk) as $line) {
                    if (!$line) continue;

                    $json = json_decode($line, true);

                    if (isset($json['error'])) {
                        echo 'Error: ' . $json['error'];
                        flush();
                        continue;
                    }

                    if (isset($json['message']['content'])) {
                        $token         = $json['message']['content'];
                        $fullResponse .= $token;

                        if ($onToken) {
                            $onToken($token);
                        }

                        if ($echo) {
                            echo $token;
                        }

                        if (function_exists('ob_flush') && function_exists('flush')) {
                            @ob_flush();
                            flush();
                        }
                    }
                }

                return strlen($chunk);
            }
        );

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/plain');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');

        curl_exec($ch);
        curl_close($ch);

        return $fullResponse;
    }

    /**
     * Kembalikan vektor embedding untuk teks yang diberikan.
     */
    public function embed(string $text): array
    {
        $payload = ['model' => $this->embeddingModel, 'prompt' => $text];

        $ch = curl_init($this->baseUrl . '/api/embeddings');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true)['embedding'] ?? [];
    }

    // ------------------------------------------------------------------
    // PRIVATE HELPERS
    // ------------------------------------------------------------------

    private function buildPayload(
        string $model,
        array  $messages,
        bool   $stream,
        array  $options
    ): array {
        return [
            'model'      => $model,
            'messages'   => $messages,
            'stream'     => $stream,
            'keep_alive' => '30m',
            'options'    => array_merge(['temperature' => 0.7], $options),
        ];
    }
}