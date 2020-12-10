<?php

namespace Hudm\Wxa;

use App\Http\Middleware\TrustHosts;
use DB;
use App\Models\User;
use GuzzleHttp\Client;
use Hudm\Wxa\Jobs\SaveGroups;
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
                    'nickname' => $name,
                    'wx_account' => $account
                ]);

                // 新建用户对应的微信账号信息
                $user->wechatAccount()
                    ->create([
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
        dispatch(new SaveGroups($account));

        return 'SUCCESS';
    }

    /**
     * 更新账号的群列表
     *
     * @param string $robotWxid
     * @param bool $isRefresh
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGroupList(string $robotWxid, bool $isRefresh = true)
    {
        $params = [];
        $params['type'] = 205;
        $params['robot_wxid'] = $robotWxid;
        $params['is_refresh'] = $isRefresh;

        return $this->assistantToolResponse($params);
    }

    /**
     * 获取群成员列表
     *
     * @param string $robotWxid
     * @param string $groupWxid
     * @param bool $isRefresh
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGroupMembers(string $robotWxid, string $groupWxid, bool $isRefresh = true)
    {
        $params = [];
        $params['type'] = 206;              // Api数值（可以参考 - api列表demo）
        $params['robot_wxid'] = $robotWxid; // 账户id
        $params['group_wxid'] = $groupWxid; // 群id
        $params['is_refresh'] = $isRefresh; // 是否刷新列表，0 从缓存获取 / 1 刷新并获取

        return $this->assistantToolResponse($params);
    }

    /**
     * 获取群成员资料
     *
     * @param string $robotWxid
     * @param string $groupWxid
     * @param $memberWxid
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getGroupMemberInfo(string $robotWxid, string $groupWxid, $memberWxid)
    {
        $params = [];
        $params['type'] = 207;                // Api数值
        $params['robot_wxid'] = $robotWxid;   // 账户id，取哪个账号的资料
        $params['group_wxid'] = $groupWxid;   // 群id
        $params['member_wxid'] = $memberWxid; // 群成员id

        return $this->assistantToolResponse($params);
    }

    /**
     * 请求微信助手工具返回数据
     *
     * @param array $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function assistantToolResponse(array $params)
    {
        // 构造请求参数
        $query = [];
        $query['query'] = ['data' => json_encode($params)];

        // 请求微信助手工具
        $res = $this->getHttpClient()->post($this->appUrl, $query)->getBody()->getContents();

        // 解析响应数据返回
        $res = json_decode($res, true);

        return json_decode(urldecode($res['data']));
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