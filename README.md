# Laravel AI Integration

A clean, minimal Laravel package for integrating OpenAI and Anthropic Claude into your application via a single unified service class.

Switch between providers with one environment variable — no code changes needed.

## What's inside

- `AIService` — core service handling both OpenAI and Claude APIs
- `AIChatController` — thin REST controller, delegates everything to the service
- `AIChatRequest` — validates incoming message and conversation history
- Single endpoint: `POST /api/ai/chat`
- Retry logic, error logging, provider-specific auth handled automatically

## Requirements

- PHP 8.2+
- Laravel 11
- An OpenAI or Anthropic API key

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set your provider and key in `.env`:

```env
AI_PROVIDER=openai        # or: claude
AI_API_KEY=your-key-here
AI_MODEL=gpt-4o-mini      # or: claude-sonnet-4-20250514
AI_MAX_TOKENS=1024
```

## Usage

```bash
curl -X POST http://localhost:8000/api/ai/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "What is Laravel?", "history": []}'
```

With conversation history:

```bash
curl -X POST http://localhost:8000/api/ai/chat \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Can you expand on that?",
    "history": [
      {"role": "user", "content": "What is Laravel?"},
      {"role": "assistant", "content": "Laravel is a PHP web framework..."}
    ]
  }'
```

Response:

```json
{
  "content": "Laravel is a PHP web application framework...",
  "model": "gpt-4o-mini",
  "provider": "openai",
  "usage": {
    "prompt_tokens": 12,
    "completion_tokens": 84,
    "total_tokens": 96
  }
}
```

## Switching providers

To switch from OpenAI to Claude, change two lines in `.env`:

```env
AI_PROVIDER=claude
AI_API_KEY=your-anthropic-key
AI_MODEL=claude-sonnet-4-20250514
```

No code changes required.

## Project structure

```
app/
├── Http/
│   ├── Controllers/Api/AIChatController.php
│   └── Requests/AIChatRequest.php
├── Services/
│   └── AIService.php
config/
└── services.php
routes/
└── api.php
```

## Notes

- HTTP client is configured with 30s timeout and 2 automatic retries
- API errors are logged via Laravel's Log facade
- Both providers use the same message format for conversation history
- Auth is handled per-provider: Bearer token for OpenAI, `x-api-key` header for Claude

## Author

Arshak Gabrielyan — Full-Stack PHP Developer  
[LinkedIn](https://linkedin.com/in/arshak-gabrielyan-7a3876236) · [Upwork](https://www.upwork.com/freelancers/~01542fd7c6c78ef67c)
