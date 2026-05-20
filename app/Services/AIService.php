<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $provider;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->provider = config('services.ai.provider', 'openai');
        $this->apiKey = config('services.ai.api_key');
        $this->model = config('services.ai.model', $this->defaultModel());
    }

    private function defaultModel(): string
    {
        return match ($this->provider) {
            'claude' => 'claude-sonnet-4-20250514',
            default => 'gpt-4o-mini',
        };
    }

    /**
     * Send a chat message and get a response from the configured AI provider.
     */
    public function chat(string $message, array $conversationHistory = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("API key not configured for provider: {$this->provider}");
        }

        return match ($this->provider) {
            'claude' => $this->chatWithClaude($message, $conversationHistory),
            'openai' => $this->chatWithOpenAI($message, $conversationHistory),
            default => throw new \InvalidArgumentException("Unsupported AI provider: {$this->provider}"),
        };
    }

    private function chatWithOpenAI(string $message, array $history): array
    {
        $messages = $this->buildMessages($message, $history);

        $response = $this->httpClient()
            ->withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => config('services.ai.max_tokens', 1024),
            ]);

        if ($response->failed()) {
            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException('Failed to get response from OpenAI: ' . $response->json('error.message', 'Unknown error'));
        }

        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'],
            'model' => $data['model'],
            'usage' => $data['usage'] ?? null,
            'provider' => 'openai',
        ];
    }

    private function chatWithClaude(string $message, array $history): array
    {
        $messages = $this->buildMessages($message, $history);

        // Claude uses x-api-key header instead of Bearer token
        $response = $this->httpClient()
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => config('services.ai.max_tokens', 1024),
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            Log::error('Claude API error', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException('Failed to get response from Claude: ' . $response->json('error.message', 'Unknown error'));
        }

        $data = $response->json();

        return [
            'content' => $data['content'][0]['text'],
            'model' => $data['model'],
            'usage' => $data['usage'] ?? null,
            'provider' => 'claude',
        ];
    }

    /**
     * Build the messages array from history + new message.
     * Both OpenAI and Claude use the same message format for basic chat.
     */
    private function buildMessages(string $message, array $history): array
    {
        $messages = [];

        foreach ($history as $entry) {
            $messages[] = [
                'role' => $entry['role'],
                'content' => $entry['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        return $messages;
    }

    private function httpClient(): PendingRequest
    {
        return Http::timeout(30)->retry(2, 100, throw: false);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}
