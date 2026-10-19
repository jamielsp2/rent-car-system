<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A named set of admin modules a staff user may access. */
#[Fillable(['name', 'modules'])]
class Role extends Model
{
    protected $table = 'staff_roles';

    /** Admin modules a role can grant. */
    public const MODULES = [
        'reservas' => 'Reservas (resumen, calendario, entregas)',
        'vehiculos' => 'Vehículos',
        'clientes' => 'Clientes',
        'catalogos' => 'Catálogos (marcas, tipos, impuestos, servicios…)',
        'contenido' => 'Contenido web (blog, preguntas, testimonios)',
        'reportes' => 'Reportes',
        'configuracion' => 'Configuración del sitio, contrato y base de datos',
        'usuarios' => 'Usuarios y roles',
    ];

    protected function casts(): array
    {
        return ['modules' => 'array'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
