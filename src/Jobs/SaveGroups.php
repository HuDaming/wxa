<?php

namespace Hudm\Wxa\Jobs;

use WechatAssistant;
use Hudm\Wxa\Models\Group;
use Hudm\Wxa\Models\WechatAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SaveGroups implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $account;

    /**
     * Create a new job instance.
     *
     * @param string $account
     * @return void
     */
    public function __construct(string $account)
    {
        $this->account = $account;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /** @var WechatAccount $wxAccount */
        $wxAccount = WechatAccount::query()
            ->with('groups')
            ->whereAccount($this->account)
            ->first();

        if (!$wxAccount) return;

        // 请求助手工具获取用户群列表
        $groups = WechatAssistant::getGroupList($this->account);

        // 查询表中已存在群
        $existsGroups = $wxAccount->groups
            ->pluck('owner_account')
            ->toArray();

        $groupData = [];
        foreach ($groups as $group) {
            if (!in_array($group->wxid, $existsGroups)) {
                $groupData[] = [
                    "owner_account" => $group->wxid,
                    "title" => $group->nickname,
                    "service_account" => $group->robot_wxid
                ];
            }

            // 触发保存群成员任务
            dispatch(new SaveGroupMembers($group->robot_wxid, $group->wxid));
        }

        // 保存群数据
        Group::insert($groupData);
    }
}
