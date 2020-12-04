<?php

namespace Hudm\Wxa;

use DB;
use App\Models\User;
use GuzzleHttp\Client;
use Hudm\Wxa\Models\Group;
use Illuminate\Http\Request;
use Hudm\Wxa\Models\WechatAccount;

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
    protected function getGroupList(string $account = '', bool $isRefresh = false)
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
        }
        // 保存群数据
        Group::insert($groups);
        // 触发获取群用户列表事件

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