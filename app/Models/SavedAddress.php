<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedAddress extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function labelDisplay(): string
    {
        return match (mb_strtolower($this->label)) {
            'casa' => 'Casa',
            'trabalho' => 'Trabalho',
            'outro' => 'Outro',
            default => $this->label,
        };
    }

    public function line(): string
    {
        $parts = array_filter([
            $this->address,
            $this->neighborhood,
            $this->city,
        ]);

        return implode(', ', $parts);
    }

    public function makeDefault(): void
    {
        static::query()
            ->where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'label_display' => $this->labelDisplay(),
            'address' => $this->address,
            'city' => $this->city,
            'neighborhood' => $this->neighborhood,
            'reference' => $this->reference,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_default' => (bool) $this->is_default,
            'line' => $this->line(),
        ];
    }
}
