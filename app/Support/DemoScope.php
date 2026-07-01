<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class DemoScope
{
    public static function isDemoUser(?User $user): bool
    {
        return $user !== null
            && $user->email === config('demo.email', 'demo@santier.local');
    }

    public static function applyProjectScope(Builder|QueryBuilder $query, ?User $user): Builder|QueryBuilder
    {
        $query->where('tenant_id', 1);

        if (self::isDemoUser($user)) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }
}