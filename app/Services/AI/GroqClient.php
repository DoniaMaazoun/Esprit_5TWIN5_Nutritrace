<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Client AI pour Groq (compatible OpenAI)
 * Réutilise la configuration existante config('services.ai')
 */
class GroqClient implements AIClientInterface
{
    public function isEnabled(): bool
    {
        return filled(config('services.ai.key'));
    }

    public function complete(array $messages, int $maxTokens = 400): ?string
    {
        if (!$this->isEnabled()) {
            Log::warning('Groq API: Key not configured');
            return null;
        }

        try {
            $baseUrl = rtrim(config('services.ai.base_url'), '/');
            $model = config('services.ai.model');
            $endpoint = $baseUrl . '/chat/completions';

            Log::info('Groq API: Request initiated', [
                'model' => $model,
                'endpoint' => $endpoint,
                'messages_count' => count($messages),
                'max_tokens' => $maxTokens,
            ]);

            $response = Http::withToken(config('services.ai.key'))
                ->acceptJson()
                ->timeout(25)
                ->post($endpoint, [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.6,
                    'max_tokens' => $maxTokens,
                ]);

            $statusCode = $response->status();

            if ($response->failed()) {
                $errorType = match(true) {
                    $statusCode === 401 => 'authentication_failed',
                    $statusCode === 403 => 'forbidden',
                    $statusCode === 404 => 'not_found',
                    $statusCode === 429 => 'rate_limited',
                    $statusCode >= 500 => 'server_error',
                    default => 'unknown_error',
                };

                Log::warning('Groq API: Request failed', [
                    'status_code' => $statusCode,
                    'error_type' => $errorType,
                    'model' => $model,
                ]);

                return null;
            }

            $responseData = $response->json();
            $content = $responseData['choices'][0]['message']['content'] ?? null;

            if ($content === null) {
                Log::error('Groq API: Invalid response structure', [
                    'status_code' => $statusCode,
                    'has_choices' => isset($responseData['choices']),
                    'choices_count' => count($responseData['choices'] ?? []),
                ]);
                return null;
            }

            Log::info('Groq API: Request successful', [
                'status_code' => $statusCode,
                'model' => $model,
                'response_length' => strlen($content),
            ]);

            return trim((string) $content) ?: null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Groq API: Connection error', [
                'message' => 'Network connection failed',
            ]);
            return null;
        } catch (\Illuminate\Http\Client\TimeoutException $e) {
            Log::error('Groq API: Timeout error', [
                'timeout_seconds' => 25,
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Groq API: Unexpected error', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            return null;
        }
    }
}
