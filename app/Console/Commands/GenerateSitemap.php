<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Direction;
use Illuminate\Console\Command;

/**
 * Sitemap generator command for SEO optimization.
 * 
 * Generates a sitemap.xml file containing all public pages
 * including courses, directions, and static pages.
 * 
 * @package App\Console\Commands
 */
class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     */
    protected $description = 'Generate sitemap.xml for SEO';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseUrl = config('app.url', 'https://edulife.uz');
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        
        // Static pages
        $staticPages = [
            ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => '/courses', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/about', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => '/contact', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => '/faq', 'priority' => '0.6', 'changefreq' => 'weekly'],
            ['url' => '/privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['url' => '/terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];
        
        foreach ($staticPages as $page) {
            $xml .= $this->generateUrl(
                $baseUrl . $page['url'],
                $page['priority'],
                $page['changefreq'],
                now()->format('Y-m-d')
            );
        }
        
        // Directions (Categories)
        $directions = Direction::where('is_active', true)->get();
        foreach ($directions as $direction) {
            $xml .= $this->generateUrl(
                $baseUrl . '/courses/category/' . $direction->slug,
                '0.8',
                'weekly',
                $direction->updated_at->format('Y-m-d')
            );
        }
        
        // Published Courses
        $courses = Course::where('status', 'published')
            ->orderBy('updated_at', 'desc')
            ->get();
            
        foreach ($courses as $course) {
            $xml .= $this->generateUrl(
                $baseUrl . '/courses/' . $course->slug,
                '0.8',
                'weekly',
                $course->updated_at->format('Y-m-d')
            );
        }
        
        $xml .= '</urlset>';
        
        // Write to public folder
        $path = public_path('sitemap.xml');
        file_put_contents($path, $xml);
        
        $this->info('✅ Sitemap generated successfully!');
        $this->info("📍 Location: {$path}");
        $this->info("📊 Total URLs: " . (count($staticPages) + $directions->count() + $courses->count()));
        
        return Command::SUCCESS;
    }
    
    /**
     * Generate a single URL entry for sitemap.
     */
    private function generateUrl(string $loc, string $priority, string $changefreq, string $lastmod): string
    {
        return <<<XML
    <url>
        <loc>{$loc}</loc>
        <lastmod>{$lastmod}</lastmod>
        <changefreq>{$changefreq}</changefreq>
        <priority>{$priority}</priority>
    </url>

XML;
    }
}
