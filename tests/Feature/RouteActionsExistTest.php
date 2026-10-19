<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every registered route must point to a controller method that exists
 * (resource routes used to register create/show/edit actions nobody wrote).
 */
class RouteActionsExistTest extends TestCase
{
    /** Route-name prefixes that are fully checked. */
    private const CHECKED_PREFIXES = ['teacher.', 'admin.'];

    public function test_every_route_action_exists(): void
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! collect(self::CHECKED_PREFIXES)->contains(fn ($prefix) => str_starts_with($name, $prefix))) {
                continue;
            }

            $action = $route->getActionName();
            if ($action === 'Closure' || ! str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action);
            if (! class_exists($class) || ! method_exists($class, $method)) {
                $missing[] = "{$name} -> {$action}";
            }
        }

        $this->assertSame([], $missing, "Routes without a controller method:\n".implode("\n", $missing));
    }
}
