<?php

namespace App\Models;

use Database\Factories\CpiCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CpiCategory extends Model
{
    /** @use HasFactory<CpiCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'parent_code',
        'mapping_to_app_category_id',
    ];

    /** @return BelongsTo<Category, $this> */
    public function appCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'mapping_to_app_category_id');
    }

    /** @return HasMany<CpiValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(CpiValue::class, 'category_code', 'code');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_code', 'code');
    }
}
