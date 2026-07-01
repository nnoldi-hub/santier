<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(): Response
    {
        $landingViews = AnalyticsEvent::query()->where('event_name', 'landing_view')->count();
        $signups = AnalyticsEvent::query()->where('event_name', 'register_completed')->distinct('user_id')->count('user_id');
        $onboardingCompleted = AnalyticsEvent::query()->where('event_name', 'onboarding_completed')->distinct('user_id')->count('user_id');
        $firstProjectCreated = AnalyticsEvent::query()->where('event_name', 'first_project_created')->distinct('user_id')->count('user_id');
        $trialUpgraded = AnalyticsEvent::query()->where('event_name', 'trial_upgraded')->distinct('user_id')->count('user_id');

        $signupToOnboardingRate = $signups > 0 ? round(($onboardingCompleted / $signups) * 100, 2) : 0;
        $onboardingToProjectRate = $onboardingCompleted > 0 ? round(($firstProjectCreated / $onboardingCompleted) * 100, 2) : 0;
        $signupToUpgradeRate = $signups > 0 ? round(($trialUpgraded / $signups) * 100, 2) : 0;

        return Inertia::render('Analytics/Index', [
            'funnel' => [
                'landing_views' => $landingViews,
                'signups' => $signups,
                'onboarding_completed' => $onboardingCompleted,
                'first_project_created' => $firstProjectCreated,
                'trial_upgraded' => $trialUpgraded,
            ],
            'conversion' => [
                'signup_to_onboarding' => $signupToOnboardingRate,
                'onboarding_to_project' => $onboardingToProjectRate,
                'signup_to_upgrade' => $signupToUpgradeRate,
            ],
            'recentEvents' => AnalyticsEvent::query()
                ->with('user:id,name,email')
                ->latest('event_at')
                ->take(30)
                ->get(['id', 'user_id', 'session_id', 'event_name', 'event_at', 'meta']),
        ]);
    }
}
