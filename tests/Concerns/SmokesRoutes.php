<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * Renders every GET route under a name prefix and collects the ones that
 * crash (5xx) or render an Inertia component that has no Vue page.
 */
trait SmokesRoutes
{
    /**
     * @param  array<string, string|int>  $params  route parameter name => value
     * @param  array<int, string>  $skip  route names not to request (downloads, streams, ...)
     * @return array<string, int|string> visited route name => status
     */
    protected function smokeGetRoutes(User $user, string $namePrefix, array $params, array $skip = []): array
    {
        $failures = [];
        $visited = [];

        /** @var RoutingRoute $route */
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! str_starts_with($name, $namePrefix) || ! in_array('GET', $route->methods(), true)) {
                continue;
            }
            if (in_array($name, $skip, true)) {
                continue;
            }

            $routeParams = [];
            foreach ($route->parameterNames() as $param) {
                if (! array_key_exists($param, $params)) {
                    $failures[] = "{$name}: no fixture for parameter {{$param}}";

                    continue 2;
                }
                $routeParams[$param] = $params[$param];
            }

            $this->app['auth']->forgetGuards();
            $response = $this->actingAs($user)->get(route($name, $routeParams));
            $status = $response->getStatusCode();
            $visited[$name] = $status;

            if ($status >= 500) {
                $message = $response->exception?->getMessage() ?? 'HTTP '.$status;
                $failures[] = "{$name}: {$status} ".mb_substr($message, 0, 300);

                continue;
            }

            $page = $response->baseResponse instanceof \Illuminate\Http\Response
                ? ($response->original instanceof \Illuminate\View\View ? ($response->original->getData()['page'] ?? null) : null)
                : null;
            if (is_array($page) && isset($page['component'])) {
                $file = resource_path('js/Pages/'.$page['component'].'.vue');
                if (! is_file($file)) {
                    $failures[] = "{$name}: renders missing page {$page['component']}";
                }
            }
        }

        $this->assertSame([], $failures, "Broken pages:\n".implode("\n", $failures));

        return $visited;
    }
}
