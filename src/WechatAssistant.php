<?php

namespace Hudm\Wxa;

use DB;
use App\Models\User;
use GuzzleHttp\Client;
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

            return 'SUCCESS';
        }

        // 如果用户不是客服账号，更新账号为客服账号
        if ($wechatAccount->type != WechatAccount::TYPE_SERVICE) {
            $wechatAccount->type = WechatAccount::TYPE_SERVICE;
            $wechatAccount->save();
        }

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