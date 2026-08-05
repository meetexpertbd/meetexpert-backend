<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'code_prefix',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function setCodePrefixAttribute(?string $value): void
    {
        $this->attributes['code_prefix'] = $value === null || $value === ''
            ? $value
            : strtoupper($value);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }

    public function expertDetails(): HasMany
    {
        return $this->hasMany(ExpertDetail::class);
    }
}
