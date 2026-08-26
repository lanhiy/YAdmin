<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $description
 * @property int $sort
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection|SystemAdmin[] $admins
 * @property-read Collection|SystemMenu[] $menus
 */
class SystemRole extends Model
{
    protected ?string $table = 'system_role';

    protected array $fillable = [
        'name', 'code', 'description', 'sort', 'status', 'created_at', 'updated_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(SystemAdmin::class, 'system_admin_role', 'role_id', 'admin_id')
            ->withTimestamps();
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(SystemMenu::class, 'system_role_menu', 'role_id', 'menu_id')
            ->withTimestamps();
    }

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;
}
