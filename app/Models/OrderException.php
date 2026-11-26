<?php

namespace App\Models;

use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;

class OrderException extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';

    public const BEARER_NONE = 'none';
    public const BEARER_CUSTOMER = 'customer';
    public const BEARER_PARTNER = 'partner';
    public const BEARER_KANGURO = 'kanguro';

    public const TYPES = [
        'partner_no_response' => ['label' => 'Parceiro não responde', 'bearer' => self::BEARER_NONE],
        'partner_refused' => ['label' => 'Parceiro recusou', 'bearer' => self::BEARER_NONE],
        'stockout_after_accept' => ['label' => 'Produto esgotado depois de aceite', 'bearer' => self::BEARER_PARTNER],
        'customer_cancel_before_accept' => ['label' => 'Cliente cancelou antes da aceitação', 'bearer' => self::BEARER_NONE],
        'customer_cancel_after_prep' => ['label' => 'Cliente cancelou com preparação iniciada', 'bearer' => self::BEARER_CUSTOMER],
        'no_courier' => ['label' => 'Nenhum estafeta disponível', 'bearer' => self::BEARER_KANGURO],
        'delayed' => ['label' => 'Pedido atrasado', 'bearer' => self::BEARER_PARTNER],
        'courier_accident' => ['label' => 'Avaria ou acidente do estafeta', 'bearer' => self::BEARER_KANGURO],
        'customer_no_show' => ['label' => 'Cliente não atende ou não aparece', 'bearer' => self::BEARER_CUSTOMER],
        'wrong_address' => ['label' => 'Morada errada ou fora da zona', 'bearer' => self::BEARER_CUSTOMER],
        'incomplete_order' => ['label' => 'Pedido incompleto ou errado', 'bearer' => self::BEARER_PARTNER],
        'damaged_in_transit' => ['label' => 'Produto danificado no transporte', 'bearer' => self::BEARER_KANGURO],
        'cash_refusal' => ['label' => 'Cliente recusa pagar em numerário', 'bearer' => self::BEARER_CUSTOMER],
        'platform_down' => ['label' => 'Aplicação ou internet em baixo', 'bearer' => self::BEARER_KANGURO],
        'other' => ['label' => 'Outra', 'bearer' => self::BEARER_NONE],
    ];

    protected $guarded = [];

    protected $casts = [
        'cost_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    protected $appends = ['type_label', 'cost_bearer_label', 'status_label'];

    public function setPhotoUrlAttribute($value): void
    {
        $this->attributes['photo_url'] = MediaPath::normalize($value);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type]['label'] ?? $this->type;
    }

    public function getCostBearerLabelAttribute(): string
    {
        return match ($this->cost_bearer) {
            self::BEARER_CUSTOMER => 'Cliente',
            self::BEARER_PARTNER => 'Parceiro',
            self::BEARER_KANGURO => 'Kanguro',
            default => 'Ninguém',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::STATUS_RESOLVED ? 'Resolvida' : 'Aberta';
    }

    public static function defaultBearer(string $type): string
    {
        return self::TYPES[$type]['bearer'] ?? self::BEARER_NONE;
    }
}
