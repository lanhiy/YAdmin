<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;

/** 管理员与角色关联。 */
class SystemAdminRole extends Model
{
    protected ?string $table = 'system_admin_role';

    protected array $fillable = ['admin_id', 'role_id', 'created_at', 'updated_at'];

    protected array $casts = [
        'id' => 'integer',
        'admin_id' => 'integer',
        'role_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(SystemAdmin::class, 'admin_id', 'id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(SystemRole::class, 'role_id', 'id');
    }
}
