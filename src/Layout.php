<?php

namespace Kreetancraft\Media;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use RuntimeException;

/**
 * Where this package's screens render, and where "Dashboard" points.
 *
 * The same shape user-management uses, deliberately: two packages that behave
 * differently about the host's layout are two things to remember instead of one.
 *
 * This package ships no layout — its screens render into yours. Getting it wrong
 * used to produce Livewire's MissingLayoutException, which names a view but not
 * the config key that chose it or the layouts you actually have. So: try what is
 * configured, then the conventions, then fail with something worth reading.
 */
class Layout
{
    /**
     * Layout names to try when the configured one does not resolve.
     *
     * `components.layouts.app` is the current Laravel starter-kit convention;
     * `layouts.app` is what older applications use. Trying both means the
     * package works on either without configuration.
     *
     * @var list<string>
     */
    public const CONVENTIONS = [
        'components.layouts.app',
        'layouts.app',
        'components.layouts.admin',
        'layouts.admin',
    ];

    public static function admin(): string
    {
        $configured = config('media.layouts.admin');

        if (is_string($configured) && $configured !== '' && View::exists($configured)) {
            return $configured;
        }

        foreach (self::CONVENTIONS as $candidate) {
            if (View::exists($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException(sprintf(
            'No layout to render into. Set `media.layouts.admin` in config/media.php to one of '
            .'your layout views. Tried: %s. This package ships no layout by design — its screens '
            .'render into yours.',
            implode(', ', array_values(array_unique(array_filter(
                array_merge([$configured], self::CONVENTIONS)
            )))),
        ));
    }

    /**
     * Where the "Dashboard" breadcrumb points.
     *
     * Accepts a route name or a URL, because people reach for both. A route name
     * is preferable — it survives the route moving — but `/admin` has to keep
     * working for anyone who set it that way.
     */
    public static function home(): string
    {
        // `routes.home` matches user-management, and is where this now lives.
        // A config published before the move has no `routes.home`, so it falls
        // through to the old key rather than silently losing the setting.
        $home = (string) (config('media.routes.home') ?? config('media.routes.names.home', '/'));

        if ($home === '') {
            return '/';
        }

        return Route::has($home) ? route($home) : $home;
    }
}
