<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;

/** 角色与菜单/按钮节点关联。 */
class SystemRoleMenu extends Model
{
    protected ?string $table = 'system_role_menu';

    protected array $fillable = ['role_id', 'menu_id', 'created_at', 'updated_at'];

    protected array $casts = [
        'id' => 'integer',
        'role_id' => 'integer',
        'menu_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(SystemRole::class, 'role_id', 'id');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(SystemMenu::class, 'menu_id', 'id');
    }
}
