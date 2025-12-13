<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SentenceBuilderDataService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const BASE_PATH = 'data/english/games/sentence-builder';
    
    /**
     * Get config
     */
    public function getConfig(): array
    {
        return Cache::remember('sentence_builder_config', self::CACHE_TTL, function () {
            return $this->readJsonFile(self::BASE_PATH . '/config.json');
        });
    }
    
    /**
     * Get all levels
     */
    public function getLevels(): array
    {
        return Cache::remember('sentence_builder_levels', self::CACHE_TTL, function () {
            $data = $this->readJsonFile(self::BASE_PATH . '/levels.json');
            return $data['levels'] ?? [];
        });
    }
    
    /**
     * Get single level
     */
    public function getLevel(int $levelNumber): ?array
    {
        $levels = $this->getLevels();
        return collect($levels)->firstWhere('number', $levelNumber);
    }
    
    /**
     * Get sentences for a grammar topic
     */
    public function getSentences(string $cefrLevel, string $grammarTopic): array
    {
        $cacheKey = "sentence_builder_{$cefrLevel}_{$grammarTopic}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cefrLevel, $grammarTopic) {
            $path = self::BASE_PATH . "/sentences/{$cefrLevel}/{$grammarTopic}.json";
            
            if (!file_exists(base_path($path))) {
                return [];
            }
            
            $data = $this->readJsonFile($path);
            return $data['sentences'] ?? [];
        });
    }
    
    /**
     * Get single sentence by ID
     */
    public function getSentence(string $sentenceId): ?array
    {
        // Parse ID: "sp_001" -> need to find which file it belongs to
        // Helper mapping or search approach needed. 
        // For simplicity, we might need to pass the level/topic or precise ID structure.
        // Let's assume the ID structure is unique enough or we search efficiently.
        
        // BETTER APPROACH: The session knows the level/topic. 
        // But for validateAnswer we just have ID.
        // Let's iterate all levels? No, too slow.
        // Let's enforce sentence ID format: {topic}_{id}, e.g., "sp_001"
        // And we need a map of topic -> level.
        
        $topic = $this->getTopicFromId($sentenceId);
        if (!$topic) return null;
        
        // Find level for topic to know folder? 
        // Or just map topic -> folder map.
        $levelMap = [
            'sp' => 'A1', 'tb' => 'A1',
            'pc' => 'A2', 'ps' => 'A2',
            'pp' => 'B1', 'c1' => 'B1',
            'pv' => 'B2', 'rs' => 'B2',
            'inv' => 'C1'
        ];
        
        // Map prefix to topic file name
        $fileMap = [
            'sp' => 'simple_present', 'tb' => 'to_be',
            'pc' => 'present_continuous', 'ps' => 'past_simple',
            'pp' => 'present_perfect', 'c1' => 'conditionals_1',
            'pv' => 'passive_voice', 'rs' => 'reported_speech',
            'inv' => 'inversion'
        ];
        
        $prefix = explode('_', $sentenceId)[0];
        $cefr = $levelMap[$prefix] ?? null;
        $filename = $fileMap[$prefix] ?? null;
        
        if (!$cefr || !$filename) return null;
        
        $sentences = $this->getSentences($cefr, $filename);
        return collect($sentences)->firstWhere('id', $sentenceId);
    }
    
    private function getTopicFromId(string $id): ?string {
        $parts = explode('_', $id);
        return $parts[0] ?? null;
    }
    
    /**
     * Get sentences for a level (all topics combined)
     */
    public function getSentencesForLevel(int $levelNumber): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }
        
        $cefrLevel = $level['cefr_level'];
        $topics = $level['grammar_topics'] ?? [];
        
        $allSentences = [];
        foreach ($topics as $topic) {
            $sentences = $this->getSentences($cefrLevel, $topic);
            $allSentences = array_merge($allSentences, $sentences);
        }
        
        return $allSentences;
    }
    
    /**
     * Scramble sentence words
     */
    public function scrambleWords(array $words): array
    {
        // Shuffle ALL components including punctuation
        // This makes the game harder but more complete (user must place "." or "?")
        
        $scrambled = $words;
        $maxAttempts = 10;
        $attempts = 0;
        
        do {
            shuffle($scrambled);
            $attempts++;
        } while ($scrambled === $words && count($words) > 1 && $attempts < $maxAttempts);
        
        return [
            'scrambled_words' => $scrambled,
            'punctuation' => [], // Deprecated/Empty as they are now in words
            'total_words' => count($words),
        ];
    }
    
    /**
     * Read JSON file
     */
    private function readJsonFile(string $relativePath): array
    {
        $fullPath = base_path($relativePath);
        if (!file_exists($fullPath)) {
            return []; // Return empty if not found
        }
        
        $content = file_get_contents($fullPath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        
        return $data;
    }
}
