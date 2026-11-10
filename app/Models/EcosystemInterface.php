<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcosystemInterface extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'name',
        'icon',
        'route_or_action',
        'action_type',
        'badge_label',
        'order_num',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_num' => 'integer',
    ];

    /**
     * Módulo padre.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(EcosystemModule::class, 'module_id');
    }
}
