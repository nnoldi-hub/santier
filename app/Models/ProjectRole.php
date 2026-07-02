<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRole extends Model
{
    public const OWNER = 'owner';
    public const CONTRIBUTOR = 'contributor';
    public const VIEWER = 'viewer';

    protected $fillable = [
        'tenant_id',
        'key',
        'name',
        'description',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectUserRole::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user_roles')
            ->withPivot(['project_id', 'tenant_id'])
            ->withTimestamps();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user_roles')
            ->withPivot(['user_id', 'tenant_id'])
            ->withTimestamps();
    }

    /**
     * @return array<int, array{key: string, name: string, description: string}>
     */
    public static function defaults(): array
    {
        return [
            [
                'key' => self::OWNER,
                'name' => 'Owner',
                'description' => 'Control total pe proiect.',
            ],
            [
                'key' => self::CONTRIBUTOR,
                'name' => 'Contributor',
                'description' => 'Poate actualiza proiectul, fara stergere.',
            ],
            [
                'key' => self::VIEWER,
                'name' => 'Viewer',
                'description' => 'Doar citire pe proiect.',
            ],
        ];
    }
}
