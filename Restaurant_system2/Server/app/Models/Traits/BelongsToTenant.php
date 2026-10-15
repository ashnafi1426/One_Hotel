<?php

namespace App\Models\Traits;

use App\Models\Hotel;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // No tenant global scope in single-hotel architecture
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id')->withDefault(function () {
            return Hotel::first();
        });
    }

    public function scopeWithoutTenant($query)
    {
        // Inert pass-through in single-hotel architecture
        return $query;
    }
}
