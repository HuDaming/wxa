<?php

namespace Hudm\Wxa\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Hudm\Wxa\Models\Group
 *
 * @property int $id
 * @property string $title 群名称
 * @property string $owner_account 群主账号
 * @property string $service_account 所属客服账号
 * @property int $user_id 客服用户ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Hudm\Wxa\Models\WechatAccount $service
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Group newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Group newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Group query()
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereOwnerAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereServiceAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereUserId($value)
 * @mixin \Eloquent
 */
class Group extends Model
{
    protected $fillable = ['owner_account', 'title', 'service_account'];

    /**
     * 客服账号
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(WechatAccount::class, 'service_account', 'account');
    }

    /**
     * 客服
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 群组成员
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function members()
    {
        return $this->belongsToMany(WechatAccount::class, 'members', 'account', 'owner_account');
    }
}
