<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureAdmin($request);

        $defaults = config('platform.defaults', []);

        return Inertia::render('Admin/Index', [
            'plans' => config('pricing.plans', []),
            'settings' => AppSetting::allWithDefaults($defaults),
            'users' => User::query()
                ->orderByDesc('created_at')
                ->get([
                    'id',
                    'name',
                    'email',
                    'billing_plan',
                    'billing_trial_ends_at',
                    'onboarding_completed_at',
                    'created_at',
                ]),
            'metrics' => [
                'users_total' => User::query()->count(),
                'users_paid' => User::query()->whereIn('billing_plan', ['starter', 'pro', 'enterprise'])->count(),
                'users_on_trial' => User::query()->whereNotNull('billing_trial_ends_at')->whereDate('billing_trial_ends_at', '>=', now()->toDateString())->count(),
                'monthly_mrr_estimate' => $this->estimateMonthlyRevenue(),
                'admin_count' => User::query()
                    ->where('is_superadmin', true)
                    ->orWhereIn('email', config('platform.admin_emails', []))
                    ->count(),
            ],
        ]);
    }

    public function updateSubscription(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'billing_plan' => ['required', 'in:free,starter,pro,enterprise'],
            'billing_trial_ends_at' => ['nullable', 'date'],
            'onboarding_completed' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'billing_plan' => $validated['billing_plan'],
            'billing_trial_ends_at' => $validated['billing_trial_ends_at'] ?? null,
            'onboarding_completed_at' => !empty($validated['onboarding_completed']) ? ($user->onboarding_completed_at ?? now()) : null,
        ]);

        return back()->with('success', 'Abonamentul contului a fost actualizat.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'company_name' => ['required', 'string', 'max:120'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'support_email' => ['required', 'email', 'max:255'],
            'sales_email' => ['required', 'email', 'max:255'],
            'landing_video_url' => [
                'nullable',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->isAllowedLandingVideoUrl($value)) {
                        $fail('Linkul video trebuie sa fie YouTube sau URL direct catre fisier video (.mp4, .webm, .ogg).');
                    }
                },
            ],
            'landing_video_file' => ['nullable', 'file', 'mimes:mp4,webm,ogg,mov', 'max:102400'],
            'social_facebook_url' => ['nullable', 'url', 'max:500'],
            'social_instagram_url' => ['nullable', 'url', 'max:500'],
            'social_linkedin_url' => ['nullable', 'url', 'max:500'],
            'social_tiktok_url' => ['nullable', 'url', 'max:500'],
            'social_youtube_url' => ['nullable', 'url', 'max:500'],
            'document_logo_url' => ['nullable', 'url', 'max:500'],
            'document_logo_file' => ['nullable', 'image', 'max:2048'],
            'document_brand_color' => ['nullable', 'string', 'max:32'],
            'trial_days' => ['required', 'integer', 'min:1', 'max:90'],
            'public_signup_enabled' => ['nullable', 'boolean'],
            'demo_mode_enabled' => ['nullable', 'boolean'],
        ]);

        $documentLogoUrl = $validated['document_logo_url'] ?? '';
        $landingVideoUrl = trim((string) ($validated['landing_video_url'] ?? ''));

        if ($request->hasFile('document_logo_file')) {
            $path = $request->file('document_logo_file')->store('branding', 'public');
            $documentLogoUrl = Storage::url($path);
        }

        if ($request->hasFile('landing_video_file')) {
            $path = $request->file('landing_video_file')->store('landing-videos', 'public');
            $landingVideoUrl = Storage::url($path);
        }

        AppSetting::setValues([
            'app_name' => $validated['app_name'],
            'company_name' => $validated['company_name'],
            'company_phone' => $validated['company_phone'] ?? '',
            'company_address' => $validated['company_address'] ?? '',
            'support_email' => $validated['support_email'],
            'sales_email' => $validated['sales_email'],
            'landing_video_url' => $landingVideoUrl,
            'social_facebook_url' => $validated['social_facebook_url'] ?? '',
            'social_instagram_url' => $validated['social_instagram_url'] ?? '',
            'social_linkedin_url' => $validated['social_linkedin_url'] ?? '',
            'social_tiktok_url' => $validated['social_tiktok_url'] ?? '',
            'social_youtube_url' => $validated['social_youtube_url'] ?? '',
            'document_logo_url' => $documentLogoUrl,
            'document_brand_color' => $validated['document_brand_color'] ?? '#f97316',
            'trial_days' => $validated['trial_days'],
            'public_signup_enabled' => (bool) ($validated['public_signup_enabled'] ?? false),
            'demo_mode_enabled' => (bool) ($validated['demo_mode_enabled'] ?? false),
        ]);

        return back()->with('success', 'Setarile globale au fost salvate.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($this->isAdmin($request->user()), 403);
    }

    private function isAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ((bool) ($user->is_superadmin ?? false)) {
            return true;
        }

        return in_array(strtolower($user->email), array_map('strtolower', config('platform.admin_emails', [])), true);
    }

    private function estimateMonthlyRevenue(): int
    {
        $plans = config('pricing.plans', []);

        return (int) User::query()->get(['billing_plan'])->sum(function (User $user) use ($plans) {
            return (int) ($plans[$user->billing_plan]['price'] ?? 0);
        });
    }

    private function isAllowedLandingVideoUrl(mixed $value): bool
    {
        $urlValue = trim((string) ($value ?? ''));

        if ($urlValue === '') {
            return true;
        }

        if (str_starts_with($urlValue, '/')) {
            return $this->isDirectVideoPath($urlValue);
        }

        $parts = parse_url($urlValue);

        if (! is_array($parts) || empty($parts['host'])) {
            return false;
        }

        $host = strtolower((string) $parts['host']);
        $allowedHosts = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com'];

        if (in_array($host, $allowedHosts, true)) {
            return true;
        }

        $path = strtolower((string) ($parts['path'] ?? ''));

        return $this->isDirectVideoPath($path);
    }

    private function isDirectVideoPath(string $path): bool
    {
        return (bool) preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $path);
    }
}