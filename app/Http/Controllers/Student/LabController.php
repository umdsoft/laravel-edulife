<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LabCategory;
use App\Models\LabExperiment;
use App\Models\LabAttempt;
use App\Models\LabUserProgress;
use App\Models\LabBadge;
use App\Models\LabLeaderboard;
use App\Models\LabSavedExperiment;
use App\Models\LabRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LabController extends Controller
{
    /**
     * Lab asosiy sahifasi - kategoriyalar va featured tajribalar
     */
    public function index()
    {
        $user = Auth::user();
        
        $categories = LabCategory::query()
            ->active()
            ->ordered()
            ->withCount(['experiments' => fn($q) => $q->active()])
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'slug' => $cat->slug,
                'name' => $cat->localized_name,
                'description' => $cat->localized_description,
                'icon' => $cat->icon,
                'color' => $cat->color,
                'gradient' => $cat->gradient,
                'experiments_count' => $cat->experiments_count,
                'avg_rating' => $cat->avg_rating,
                'progress' => $user ? $cat->getProgressForUser($user) : null,
            ]);
        
        $featuredExperiments = LabExperiment::query()
            ->active()
            ->featured()
            ->with('category')
            ->limit(6)
            ->get()
            ->map(fn($exp) => $this->formatExperimentCard($exp, $user));
        
        $freeExperiments = LabExperiment::query()
            ->active()
            ->free()
            ->with('category')
            ->orderByDesc('total_completions')
            ->limit(8)
            ->get()
            ->map(fn($exp) => $this->formatExperimentCard($exp, $user));
        
        // User progress
        $userProgress = null;
        $recentAttempts = [];
        
        if ($user) {
            $progress = LabUserProgress::firstOrCreate(['user_id' => $user->id]);
            $userProgress = $progress->getDashboardStats();
            
            $recentAttempts = LabAttempt::where('user_id', $user->id)
                ->with('experiment.category')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn($attempt) => [
                    'id' => $attempt->id,
                    'experiment' => [
                        'id' => $attempt->experiment->id,
                        'title' => $attempt->experiment->localized_title,
                        'slug' => $attempt->experiment->slug,
                        'category' => $attempt->experiment->category->localized_name,
                    ],
                    'status' => $attempt->status,
                    'percentage' => $attempt->percentage,
                    'time_spent' => $attempt->time_spent_text,
                    'can_continue' => $attempt->can_resume,
                    'started_at' => $attempt->started_at->diffForHumans(),
                ]);
        }
        
        // Leaderboard top 10
        $leaderboard = LabLeaderboard::getTopUsers('weekly', 10)
            ->map(fn($entry) => $entry->toDisplayData());
        
        return Inertia::render('Student/Lab/Index', [
            'categories' => $categories,
            'featuredExperiments' => $featuredExperiments,
            'freeExperiments' => $freeExperiments,
            'userProgress' => $userProgress,
            'recentAttempts' => $recentAttempts,
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Kategoriya sahifasi - barcha tajribalar ro'yxati
     */
    public function category(string $slug)
    {
        $user = Auth::user();
        
        $category = LabCategory::where('slug', $slug)
            ->active()
            ->firstOrFail();
        
        $experiments = LabExperiment::where('category_id', $category->id)
            ->active()
            ->orderBy('experiment_number')
            ->get()
            ->map(fn($exp) => $this->formatExperimentCard($exp, $user));
        
        $categoryData = [
            'id' => $category->id,
            'slug' => $category->slug,
            'name' => $category->localized_name,
            'description' => $category->localized_description,
            'icon' => $category->icon,
            'color' => $category->color,
            'gradient' => $category->gradient,
            'banner_image' => $category->banner_image,
            'total_experiments' => $category->total_experiments,
            'avg_rating' => $category->avg_rating,
            'progress' => $user ? $category->getProgressForUser($user) : null,
        ];
        
        return Inertia::render('Student/Lab/Category', [
            'category' => $categoryData,
            'experiments' => $experiments,
        ]);
    }

    /**
     * Tajriba batafsil sahifasi
     */
    public function show(string $slug)
    {
        $user = Auth::user();
        
        $experiment = LabExperiment::where('slug', $slug)
            ->active()
            ->with(['category', 'badgeOnComplete', 'badgeOnPerfect'])
            ->firstOrFail();
        
        $isAccessible = $experiment->isAccessibleBy($user);
        $userProgress = $user ? $experiment->getProgressForUser($user) : null;
        
        // Prerequisites check
        $prerequisites = $experiment->getPrerequisites()
            ->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->localized_title,
                'slug' => $p->slug,
                'completed' => $user ? $experiment->hasCompletedPrerequisites($user) : false,
            ]);
        
        // Ratings
        $ratings = LabRating::where('experiment_id', $experiment->id)
            ->approved()
            ->with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($r) => $r->toDisplayData());
        
        $ratingsSummary = [
            'avg' => $experiment->avg_rating,
            'total' => $experiment->total_ratings,
            'distribution' => $this->getRatingDistribution($experiment),
        ];
        
        return Inertia::render('Student/Lab/Show', [
            'experiment' => [
                'id' => $experiment->id,
                'slug' => $experiment->slug,
                'title' => $experiment->localized_title,
                'short_description' => $experiment->localized_short_description,
                'description' => $experiment->localized_description,
                'category' => [
                    'id' => $experiment->category->id,
                    'slug' => $experiment->category->slug,
                    'name' => $experiment->category->localized_name,
                    'color' => $experiment->category->color,
                ],
                'grade_level' => $experiment->grade_level,
                'difficulty' => $experiment->difficulty,
                'difficulty_label' => $experiment->difficulty_label,
                'difficulty_color' => $experiment->difficulty_color,
                'estimated_duration' => $experiment->estimated_duration,
                'duration_text' => $experiment->duration_text,
                'is_free' => $experiment->is_free,
                'is_premium' => $experiment->is_premium,
                'objectives' => $experiment->localized_objectives,
                'theory' => $experiment->localized_theory,
                'formulas' => $experiment->formulas,
                'required_equipment' => $experiment->required_equipment,
                'tasks_count' => $experiment->tasks_count,
                'total_points' => $experiment->total_points,
                'xp_reward' => $experiment->getXpRewardFor($user),
                'coin_reward' => $experiment->getCoinRewardFor($user),
                'badge_on_complete' => $experiment->badgeOnComplete ? [
                    'id' => $experiment->badgeOnComplete->id,
                    'name' => $experiment->badgeOnComplete->localized_name,
                    'icon' => $experiment->badgeOnComplete->icon,
                    'rarity' => $experiment->badgeOnComplete->rarity,
                ] : null,
                'thumbnail' => $experiment->thumbnail,
                'video_tutorial_url' => $experiment->video_tutorial_url,
                'avg_rating' => $experiment->avg_rating,
                'total_ratings' => $experiment->total_ratings,
                'total_completions' => $experiment->total_completions,
            ],
            'isAccessible' => $isAccessible,
            'userProgress' => $userProgress,
            'prerequisites' => $prerequisites,
            'ratings' => $ratings,
            'ratingsSummary' => $ratingsSummary,
        ]);
    }

    /**
     * Simulyatsiya sahifasi - asosiy tajriba interfeysi
     */
    public function simulate(string $slug)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Tajribani boshlash uchun tizimga kiring');
        }
        
        $experiment = LabExperiment::where('slug', $slug)
            ->active()
            ->with('category')
            ->firstOrFail();
        
        // Check access
        if (!$experiment->isAccessibleBy($user)) {
            return redirect()->route('student.lab.show', $slug)
                ->with('error', 'Bu tajribaga kirish uchun premium obuna kerak');
        }
        
        // Check prerequisites
        if (!$experiment->hasCompletedPrerequisites($user)) {
            return redirect()->route('student.lab.show', $slug)
                ->with('error', 'Avval oldingi tajribalarni yakunlang');
        }
        
        // Get or create attempt
        $attempt = LabAttempt::where('user_id', $user->id)
            ->where('experiment_id', $experiment->id)
            ->whereIn('status', ['in_progress', 'paused'])
            ->first();
        
        if (!$attempt) {
            $attemptNumber = LabAttempt::where('user_id', $user->id)
                ->where('experiment_id', $experiment->id)
                ->count() + 1;
            
            $attempt = LabAttempt::create([
                'user_id' => $user->id,
                'experiment_id' => $experiment->id,
                'attempt_number' => $attemptNumber,
                'started_at' => now(),
                'total_tasks' => count($experiment->tasks ?? []),
                'max_score' => $experiment->total_points ?? 100,
                'device_type' => $this->getDeviceType(),
                'browser' => substr(request()->header('User-Agent'), 0, 100),
                'ip_address' => request()->ip(),
            ]);
        }
        
        $attempt->start();
        
        return Inertia::render('Student/Lab/Simulate', [
            'experiment' => [
                'id' => $experiment->id,
                'slug' => $experiment->slug,
                'title' => $experiment->localized_title,
                'category' => $experiment->category->localized_name,
                'difficulty' => $experiment->difficulty,
                'simulation_type' => $experiment->simulation_type,
                'simulation_config' => $experiment->simulation_config,
                'tasks' => $experiment->tasks,
                'formulas' => $experiment->formulas,
                'theory' => $experiment->localized_theory,
                'objectives' => $experiment->localized_objectives,
                'required_equipment' => $experiment->required_equipment,
                'total_points' => $experiment->total_points,
                'passing_points' => $experiment->passing_points,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'current_task' => $attempt->current_task,
                'tasks_progress' => $attempt->tasks_progress,
                'simulation_state' => $attempt->simulation_state,
                'measurements' => $attempt->measurements,
                'time_spent_seconds' => $attempt->time_spent_seconds,
            ],
        ]);
    }

    /**
     * Leaderboard sahifasi
     */
    public function leaderboard(Request $request)
    {
        $period = $request->get('period', 'weekly');
        $filter = $request->get('filter', 'all'); // all, school, region
        
        $query = LabLeaderboard::where('period_type', $period);
        
        if ($filter === 'school' && Auth::check()) {
            $school = Auth::user()->student_profile?->school;
            if ($school) {
                $query->where('user_school', $school);
            }
        }
        
        $leaderboard = $query->topRanks(100)
            ->get()
            ->map(fn($entry) => $entry->toDisplayData());
        
        // Current user rank
        $userRank = null;
        if (Auth::check()) {
            $userRank = LabLeaderboard::getUserRank(Auth::id(), $period);
        }
        
        return Inertia::render('Student/Lab/Leaderboard', [
            'leaderboard' => $leaderboard,
            'period' => $period,
            'filter' => $filter,
            'userRank' => $userRank,
        ]);
    }

    /**
     * Badges sahifasi
     */
    public function badges()
    {
        $user = Auth::user();
        
        $badges = LabBadge::active()
            ->visible()
            ->ordered()
            ->get()
            ->map(function ($badge) use ($user) {
                $earned = false;
                if ($user) {
                    $progress = LabUserProgress::where('user_id', $user->id)->first();
                    $earned = $progress && in_array($badge->id, $progress->badges_earned ?? []);
                }
                
                return [
                    'id' => $badge->id,
                    'slug' => $badge->slug,
                    'name' => $badge->localized_name,
                    'description' => $badge->localized_description,
                    'condition_text' => $badge->condition_description,
                    'icon' => $badge->icon,
                    'color' => $badge->color,
                    'background_gradient' => $badge->background_gradient,
                    'rarity' => $badge->rarity,
                    'rarity_color' => $badge->rarity_color,
                    'xp_reward' => $badge->xp_reward,
                    'coin_reward' => $badge->coin_reward,
                    'earned' => $earned,
                ];
            });
        
        // Group by rarity
        $badgesByRarity = $badges->groupBy('rarity');
        
        return Inertia::render('Student/Lab/Badges', [
            'badges' => $badges,
            'badgesByRarity' => $badgesByRarity,
        ]);
    }

    /**
     * User progress sahifasi
     */
    public function progress()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        $progress = LabUserProgress::firstOrCreate(['user_id' => $user->id]);
        
        // Category progress
        $categories = LabCategory::active()->ordered()->get()
            ->map(function ($cat) use ($user) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->localized_name,
                    'slug' => $cat->slug,
                    'color' => $cat->color,
                    'icon' => $cat->icon,
                    'progress' => $cat->getProgressForUser($user),
                ];
            });
        
        // Recent attempts with details
        $recentAttempts = LabAttempt::where('user_id', $user->id)
            ->with('experiment.category')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'experiment' => [
                    'title' => $a->experiment->localized_title,
                    'slug' => $a->experiment->slug,
                    'category' => $a->experiment->category->localized_name,
                ],
                'status' => $a->status,
                'status_label' => $a->status_label,
                'percentage' => $a->percentage,
                'grade' => $a->grade,
                'xp_earned' => $a->xp_earned,
                'time_spent' => $a->time_spent_text,
                'created_at' => $a->created_at->format('d.m.Y H:i'),
            ]);
        
        // Earned badges
        $earnedBadges = $progress->getEarnedBadges()
            ->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->localized_name,
                'icon' => $b->icon,
                'color' => $b->color,
                'rarity' => $b->rarity,
            ]);
        
        return Inertia::render('Student/Lab/Progress', [
            'stats' => $progress->getDashboardStats(),
            'categories' => $categories,
            'recentAttempts' => $recentAttempts,
            'earnedBadges' => $earnedBadges,
            'skills' => $progress->skills ?? [],
            'bestExperiments' => $progress->best_experiments ?? [],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════════════

    protected function formatExperimentCard(LabExperiment $exp, $user = null): array
    {
        return [
            'id' => $exp->id,
            'slug' => $exp->slug,
            'title' => $exp->localized_title,
            'short_description' => $exp->localized_short_description,
            'category' => [
                'slug' => $exp->category->slug,
                'name' => $exp->category->localized_name,
                'color' => $exp->category->color,
            ],
            'grade_level' => $exp->grade_level,
            'difficulty' => $exp->difficulty,
            'difficulty_label' => $exp->difficulty_label,
            'difficulty_color' => $exp->difficulty_color,
            'estimated_duration' => $exp->estimated_duration,
            'is_free' => $exp->is_free,
            'is_premium' => $exp->is_premium,
            'thumbnail' => $exp->thumbnail,
            'avg_rating' => $exp->avg_rating,
            'total_completions' => $exp->total_completions,
            'xp_reward' => $exp->xp_reward,
            'is_accessible' => $user ? $exp->isAccessibleBy($user) : $exp->is_free,
            'user_progress' => $user ? $exp->getProgressForUser($user) : null,
        ];
    }

    protected function getRatingDistribution(LabExperiment $experiment): array
    {
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = LabRating::where('experiment_id', $experiment->id)
                ->where('rating', $i)
                ->approved()
                ->count();
            $distribution[$i] = $count;
        }
        return $distribution;
    }

    protected function getDeviceType(): string
    {
        $userAgent = strtolower(request()->header('User-Agent') ?? '');
        
        if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/i', $userAgent)) {
            return 'mobile';
        }
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }
        return 'desktop';
    }
}

