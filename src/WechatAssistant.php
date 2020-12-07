<?php

namespace Hudm\Wxa;

use DB;
use App\Models\User;
use GuzzleHttp\Client;
use Hudm\Wxa\Models\Group;
use Illuminate\Http\Request;
use Hudm\Wxa\Models\WechatAccount;
use Illuminate\Support\Arr;

class WechatAssistant
{
    /**
     * 助手工具服务url
     *
     * @var string
     */
    protected $appUrl;

    /**
     * @var array
     */
    protected $guzzleOptions = [];

    public function __construct(string $appUrl)
    {
        $this->appUrl = $appUrl;
    }

    /**
     * 获取登录账号并保存
     *
     * @param Request $request
     * @return string
     */
    public function loginAccountInfoSave(Request $request)
    {
        $msg = json_decode($request->input('msg'), true);
        $account = $msg['wxid'];
        $name = $msg['nickname'];
        // 查询微信账号记录
        $wechatAccount = WechatAccount::whereAccount($account)->first();
        // 账号不存在
        if (!$wechatAccount) {
            // 开启事务处理
            DB::transaction(function () use ($name, $account) {
                // 新建用户
                $user = User::create([
                    'name' => $name,
                    'nickname' => $name
                ]);
                // 新建用户对应的微信账号信息
                $user->wechatAccount()->create([
                    'account' => $account,
                    'nickname' => $name,
                    'type' => WechatAccount::TYPE_SERVICE
                ]);
            });
        } else {
            // 如果用户不是客服账号，更新账号为客服账号
            if ($wechatAccount->type != WechatAccount::TYPE_SERVICE)
                $wechatAccount->update(['type' => WechatAccount::TYPE_SERVICE]);
        }

        // 触发获取账户微信群列表事件
        $this->getGroupList($account);

        return 'SUCCESS';
    }

    /**
     * 更新账号的群列表
     *
     * @param string $account
     * @param bool $isRefresh
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGroupList(string $account = '', bool $isRefresh = false)
    {
        /** @var WechatAccount $account */
        $account = WechatAccount::with('groups')->whereAccount($account)->first();
        // 群号
        $groupNos = $account->groups->pluck('owner_account')->toArray();

        // 如果账户不存在
        if (!$account) return false;
        // 请求工具获取用户群列表
        $params = ['type' => 205, 'robot_wxid' => $account, 'is_refresh' => $isRefresh];
        $query = ['data' => json_encode($params)];
        $res = $this->getHttpClient()
            ->post($this->appUrl, ['query' => $query])
            ->getBody()
            ->getContents();
        $res = json_decode($res, true);
        $data = json_decode(urldecode($res['data']));

        // 遍历数据
        $groups = [];
        foreach ($data as $item) {
            if (!in_array($item->wxid, $groupNos)) {
                $groups[] = [
                    "owner_account" => $item->wxid,
                    "title" => $item->nickname,
                    "service_account" => $item->robot_wxid,
                    "user_id" => $account->user_id,
                ];
            }

            // 触发获取群用户列表事件
            $this->getGroupMembers($item->robot_wxid, $item->wxid, true);
        }
        // 保存群数据
        Group::insert($groups);


        return 'SUCCESS';
    }

    /**
     * @param string $serviceAccount
     * @param string $groupAccount
     * @param bool $isRefresh
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGroupMembers(string $serviceAccount, string $groupAccount, bool $isRefresh = false)
    {
        $params = [
            'type' => 206,                      // Api数值（可以参考 - api列表demo）
            'robot_wxid' => $serviceAccount,    // 账户id
            'group_wxid' => $groupAccount,      // 群id
            'is_refresh' => $isRefresh,         // 是否刷新列表，0 从缓存获取 / 1 刷新并获取
        ];

        $res = $this->getHttpClient()
            ->post($this->appUrl, [
                'query' => ['data' => json_encode($params)]
            ])
            ->getBody()
            ->getContents();
        $res = json_decode($res, true);
        $data = json_decode(urldecode($res['data']));

        // 查询已存在账户
        $existsAccounts = WechatAccount::whereIn('account', Arr::pluck($data, 'wxid'))
            ->pluck('account')->toArray();

        // 如果已存在账户不为空，在列表中清除
        if (!empty($existsAccounts)) {
            $data = Arr::where($data, function ($value) use ($existsAccounts) {
                return !in_array($value->wxid, $existsAccounts);
            });
        }


        // 遍历群用户
        $accounts = $users = $members = [];
        foreach ($data as $item) {
            $users[] = ['name' => $item->nickname, 'nickname' => $item->nickname, 'wx_account' => $item->wxid];
            $accounts[] = ['account' => $item->wxid, 'nickname' => $item->nickname, 'type' => WechatAccount::TYPE_COMMON];
            $members[] = ['owner_account' => $groupAccount, 'account' => $item->wxid];
        }

        // 写入数据
        DB::transaction(function () use ($users, $accounts, $members) {
            User::insert($users); // 写用户
            WechatAccount::insert($accounts); // 写微信账户
            DB::table('members')->insert($members); // 写群成员
        });

        return 'SUCCESS';
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    /**
     * @param array $options
     */
    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }
}