<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Enums\TennisPartner;
use App\Models\Concerns\OwnedByUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'activity_date',
    'type',
    'partner',
    'duration_minutes',
])]
class Activity extends Model
{
    use OwnedByUser;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_date' => 'date:Y-m-d',
            'type' => ActivityType::class,
            'partner' => TennisPartner::class,
            'duration_minutes' => 'integer',
        ];
    }

    public function detail(): string
    {
        return match ($this->type) {
            ActivityType::Tennis => $this->partner?->label() ?? '',
            ActivityType::Swimming => $this->duration_minutes.' min',
        };
    }
}
