<?php

namespace App\Services\English;

use App\Models\English\EnglishAIConversation;
use App\Models\English\EnglishAIConversationMessage;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class AIConversationService
{
    private const MAX_CONTEXT_MESSAGES = 10;

    public function __construct(
        private LevelService $levelService
    ) {
    }

    /**
     * Start new AI conversation
     */
    public function startConversation(
        User $user,
        string $topic,
        string $scenario = 'general',
        string $difficulty = 'intermediate'
    ): EnglishAIConversation {
        $profile = $this->levelService->getOrCreateProfile($user);

        $conversation = EnglishAIConversation::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'level_id' => $profile->current_level_id,
            'topic' => $topic,
            'scenario' => $scenario,
            'difficulty' => $difficulty,
            'status' => 'active',
            'started_at' => now(),
            'settings' => [
                'max_turns' => 20,
                'correction_mode' => 'gentle',
                'vocabulary_level' => $difficulty,
            ],
        ]);

        $systemPrompt = $this->buildSystemPrompt($topic, $scenario, $difficulty, $profile->currentLevel?->name ?? 'Intermediate');

        $this->addMessage($conversation, 'system', $systemPrompt);

        $greeting = $this->getAIGreeting($topic, $scenario);
        $this->addMessage($conversation, 'assistant', $greeting);

        return $conversation->fresh(['messages']);
    }

    /**
     * Send message and get AI response
     */
    public function sendMessage(EnglishAIConversation $conversation, string $message): array
    {
        $this->addMessage($conversation, 'user', $message);

        $grammarCorrections = $this->analyzeGrammar($message);

        $context = $this->buildContext($conversation);

        try {
            $response = $this->callAnthropicAPI($context);
        } catch (\Exception $e) {
            $response = $this->getFallbackResponse($conversation->topic);
        }

        $assistantMessage = $this->addMessage($conversation, 'assistant', $response);

        $xpEarned = $this->calculateXp($message, $grammarCorrections);
        $profile = $this->levelService->getOrCreateProfile($conversation->user);
        $profile->addXp($xpEarned);

        $conversation->messages_count = ($conversation->messages_count ?? 0) + 2;
        $conversation->total_user_words = ($conversation->total_user_words ?? 0) + str_word_count($message);
        $conversation->save();

        return [
            'response' => $response,
            'message_id' => $assistantMessage->id,
            'grammar_corrections' => $grammarCorrections,
            'xp_earned' => $xpEarned,
            'suggestions' => $this->getSuggestions($message, $conversation->topic),
        ];
    }

    private function addMessage(EnglishAIConversation $conversation, string $role, string $content): EnglishAIConversationMessage
    {
        return EnglishAIConversationMessage::create([
            'id' => Str::uuid(),
            'conversation_id' => $conversation->id,
            'role' => $role,
            'content' => $content,
            'created_at' => now(),
        ]);
    }

    private function buildSystemPrompt(string $topic, string $scenario, string $difficulty, string $level): string
    {
        return <<<PROMPT
You are a friendly English conversation partner helping a student practice English. 

Topic: {$topic}
Scenario: {$scenario}
Student Level: {$level} ({$difficulty})

Guidelines:
1. Speak naturally but adjust vocabulary to the student's level
2. If the student makes grammar mistakes, gently correct them in context
3. Ask follow-up questions to keep the conversation going
4. Encourage the student and provide positive feedback
5. Introduce new vocabulary naturally and explain when needed
6. Keep responses concise (2-4 sentences usually)
7. Stay in character for the scenario

Remember: This is a learning conversation. Be patient and supportive.
PROMPT;
    }

    private function buildContext(EnglishAIConversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderByDesc('created_at')
            ->limit(self::MAX_CONTEXT_MESSAGES)
            ->get()
            ->reverse()
            ->values();

        return $messages->map(fn($m) => [
            'role' => $m->role,
            'content' => $m->content,
        ])->toArray();
    }

    private function callAnthropicAPI(array $context): string
    {
        $apiKey = config('services.anthropic.api_key');

        if (!$apiKey) {
            throw new \Exception('Anthropic API key not configured');
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-3-haiku-20240307',
                    'max_tokens' => 300,
                    'messages' => array_filter($context, fn($m) => $m['role'] !== 'system'),
                    'system' => collect($context)->firstWhere('role', 'system')['content'] ?? '',
                ]);

        if ($response->failed()) {
            throw new \Exception('Anthropic API request failed');
        }

        return $response->json('content.0.text', 'I apologize, could you please repeat that?');
    }

    private function getFallbackResponse(string $topic): string
    {
        $responses = [
            "That's an interesting point! Can you tell me more about your thoughts on this?",
            "I understand. What else would you like to discuss about {$topic}?",
            "Good! Let's continue our conversation. What aspects interest you the most?",
            "That's a great observation! How does that relate to your experience?",
        ];

        return $responses[array_rand($responses)];
    }

    private function analyzeGrammar(string $message): array
    {
        $corrections = [];

        $commonErrors = [
            '/\\bi\\b/' => ['replacement' => 'I', 'rule' => 'Capitalize "I"'],
            '/\\bdont\\b/' => ['replacement' => "don't", 'rule' => 'Use apostrophe for contractions'],
            '/\\bcant\\b/' => ['replacement' => "can't", 'rule' => 'Use apostrophe for contractions'],
            '/\\bwont\\b/' => ['replacement' => "won't", 'rule' => 'Use apostrophe for contractions'],
            '/\\bim\\b/i' => ['replacement' => "I'm", 'rule' => 'Use apostrophe for contractions'],
            '/\\btheres\\b/i' => ['replacement' => "there's", 'rule' => 'Use apostrophe for contractions'],
            '/\\byour\\s+welcome\\b/i' => ['replacement' => "you're welcome", 'rule' => 'Use "you are" contraction'],
            '/\\btheir\\s+is\\b/i' => ['replacement' => "there is", 'rule' => 'Use "there" for location/existence'],
        ];

        foreach ($commonErrors as $pattern => $correction) {
            if (preg_match($pattern, $message, $matches)) {
                $corrections[] = [
                    'original' => $matches[0],
                    'correction' => $correction['replacement'],
                    'rule' => $correction['rule'],
                ];
            }
        }

        return $corrections;
    }

    private function calculateXp(string $message, array $corrections): int
    {
        $wordCount = str_word_count($message);
        $baseXp = min(20, $wordCount);
        $correctnessBonus = empty($corrections) ? 5 : 0;

        return $baseXp + $correctnessBonus;
    }

    private function getSuggestions(string $message, string $topic): array
    {
        return [
            "Could you elaborate on that?",
            "What do you think about...?",
            "Can you give me an example?",
        ];
    }

    private function getAIGreeting(string $topic, string $scenario): string
    {
        $greetings = [
            "general" => "Hello! I'm excited to practice English with you today. Let's talk about {$topic}. What would you like to share?",
            "restaurant" => "Welcome to our restaurant! I'll be your server today. May I help you with the menu?",
            "shopping" => "Hello and welcome to our store! Are you looking for something specific today?",
            "travel" => "Hi there! I hear you're planning to travel. Where are you thinking of going?",
            "job_interview" => "Good morning! Thank you for coming in today. Please, have a seat and tell me a bit about yourself.",
        ];

        return str_replace('{$topic}', $topic, $greetings[$scenario] ?? $greetings['general']);
    }

    public function endConversation(EnglishAIConversation $conversation): array
    {
        $conversation->status = 'completed';
        $conversation->ended_at = now();
        $conversation->duration_seconds = now()->diffInSeconds($conversation->started_at);
        $conversation->save();

        $stats = $this->calculateConversationStats($conversation);

        return [
            'conversation_id' => $conversation->id,
            'duration_minutes' => round($conversation->duration_seconds / 60, 1),
            'messages_count' => $conversation->messages_count,
            'total_words' => $conversation->total_user_words,
            'stats' => $stats,
        ];
    }

    private function calculateConversationStats(EnglishAIConversation $conversation): array
    {
        return [
            'average_message_length' => $conversation->messages_count > 0
                ? round($conversation->total_user_words / ($conversation->messages_count / 2))
                : 0,
            'conversation_duration' => $conversation->duration_seconds,
            'topic' => $conversation->topic,
        ];
    }

    public function getConversationHistory(User $user, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return EnglishAIConversation::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
