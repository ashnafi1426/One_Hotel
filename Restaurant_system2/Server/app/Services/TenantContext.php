<?php

namespace App\Services;

use App\Models\Hotel;

class TenantContext
{
    protected ?Hotel $hotel = null;

    public function getHotel(): ?Hotel
    {
        if ($this->hotel === null) {
            $this->hotel = Hotel::first();
        }
        return $this->hotel;
    }

    public function getHotelId(): ?string
    {
        return $this->getHotel()?->id;
    }

    public static function id(): ?string
    {
        return app(self::class)->getHotelId();
    }

    public static function hotel(): ?Hotel
    {
        return app(self::class)->getHotel();
    }

    public function setHotel(?Hotel $hotel, $membership = null): void
    {
        $this->hotel = $hotel ?? Hotel::first();
    }

    public function setHotelId(?string $hotelId): void
    {
        $this->hotel = Hotel::first();
    }

    public function getMembership(): ?object
    {
        return null;
    }

    public function getCurrentRole(): ?string
    {
        return null;
    }

    public function getCurrentRoleModel(): ?\App\Models\Role
    {
        return null;
    }

    public function hasTenant(): bool
    {
        return true;
    }

    public function clear(): void
    {
        $this->hotel = null;
    }
}
