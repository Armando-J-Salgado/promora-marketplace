<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    public function parentCategory(): BelongsTo {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function childCategories(): HasMany {
        return $this->hasMany(Category::class, 'category_id');
    }

    public function services(): HasMany {
        return $this->hasMany(Service::class);
    }
}
