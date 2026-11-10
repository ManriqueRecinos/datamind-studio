<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ecosystem extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'short_name',
        'description',
        'icon',
        'color_preset',
        'badge_text',
        'route_name',
        'order_num',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_num' => 'integer',
    ];

    /**
     * Módulos agrupados dentro del ecosistema.
     */
    public function modules(): HasMany
    {
        return $this->hasMany(EcosystemModule::class)->orderBy('order_num');
    }

    /**
     * Obtiene la primera interfaz operativa (orden 1) del ecosistema.
     */
    public function firstInterface(): ?EcosystemInterface
    {
        $firstModule = $this->modules()->orderBy('order_num')->first();
        if (!$firstModule) {
            return null;
        }

        return $firstModule->interfaces()->orderBy('order_num')->first();
    }

    /**
     * Resuelve el destino inicial (ruta o ancla) de la primera interfaz de este ecosistema.
     */
    public function resolveFirstTargetUrl(): string
    {
        $baseRoute = route($this->route_name);
        $iface = $this->firstInterface();

        if ($iface && $iface->action_type === 'anchor' && str_starts_with($iface->route_or_action, '#')) {
            return $baseRoute . $iface->route_or_action;
        }

        return $baseRoute;
    }
}
