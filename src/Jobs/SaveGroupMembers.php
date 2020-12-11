<?php

namespace Hudm\Wxa\Jobs;

use DB;
use App\Models\User;
use Hudm\Wxa\Models\WechatAccount;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SaveGroupMembers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    protected $robotWxid;

    /**
     * @var string
     */
    protected $groupWxid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $robotWxid, string $groupWxid)
    {
        $this->robotWxid = $robotWxid;
        $this->groupWxid = $groupWxid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = \WechatAssistant::getGroupMembers($this->robotWxid, $this->groupWxid);

        // 查询已存在账户
        $existsAccounts = WechatAccount::whereIn('account', Arr::pluck($data, 'wxid'))
            ->pluck('account')
            ->toArray();

        // 如果已存在账户不为空，在列表中清除
        $newData = [];
        if (!empty($existsAccounts)) {
            $newData = Arr::where($data, function ($value) use ($existsAccounts) {
                return !in_array($value->wxid, $existsAccounts);
            });
        }

        $accounts = $users = $members = [];

        // 遍历新用户
        foreach ($newData as $item) {
            $users[] = ['name' => $item->nickname, 'nickname' => $item->nickname, 'wx_account' => $item->wxid];
            $accounts[] = ['account' => $item->wxid, 'nickname' => $item->nickname, 'type' => WechatAccount::TYPE_COMMON];
        }

        // 遍历群成员
        foreach ($data as $item) {
            $members[] = ['owner_account' => $this->groupWxid, 'account' => $item->wxid];
            // 5 分钟后同步成员详细资料
            dispatch(new SyncGroupMemberInfo($this->robotWxid, $this->groupWxid, $item->wxid))->delay(now()->addMinutes(10));
        }

        // 写入数据
        DB::transaction(function () use ($users, $accounts, $members) {
            User::insert($users); // 写用户
            WechatAccount::insert($accounts); // 写微信账户
            DB::table('members')->insert($members); // 写群成员
        });


    }
}
