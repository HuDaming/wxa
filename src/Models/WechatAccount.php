<?php

namespace Hudm\Wxa\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\WechatAccount
 *
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAccount query()
 * @mixin \Eloquent
 * @property int $id
 * @property string $account 微信账号
 * @property string $nickname 微信昵称
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAccount whereAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAccount whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAccount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAccount whereUserId($value)
 * @property string $type 账号类型
 * @method static \Illuminate\Database\Eloquent\Builder|WechatAccount whereType($value)
 */
class WechatAccount extends Model
{
    const TYPE_COMMON = 'common';
    const TYPE_SERVICE = 'service';

    public static $typeMap = [
        self::TYPE_COMMON => '普通用户',
        self::TYPE_SERVICE => '客服',
    ];

    protected $fillable = ['account', 'nickname', 'type'];

    /**
     * 微信账号对应的用户
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
