<?php

namespace App\Providers;

use App\Models\Product;
use App\Policies\ProductPolicy;
use App\Repositories\Contracts\ProductReadRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductWriteRepositoryInterface;
use App\Repositories\Contracts\UserReadRepositoryInterface;
use App\Repositories\Contracts\UserWriteRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductReadRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductWriteRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(UserReadRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserWriteRepositoryInterface::class, UserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);

        Blade::directive('richText', function (string $expression): string {
            return "<?php echo app(\\App\\Services\\RichTextSanitizer::class)->clean($expression); ?>";
        });

        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols());

        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)->by(
            Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip())
        ));

        RateLimiter::for('password-confirm', fn (Request $request) => Limit::perMinute(5)->by(
            $request->user()?->getAuthIdentifier().'|'.$request->ip()
        ));
    }
}
