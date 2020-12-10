<?php

namespace Hudm\Wxa\Commands;

use Hudm\Wxa\Models\WechatAccount;
use Illuminate\Console\Command;

class SyncMemberInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'MBR:sync {s} {g} {m}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步群组成员详细资料';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $options = $this->arguments();
        $memberWxid = $options['m'];

        // 调用工具接口
        $res = \WechatAssistant::getGroupMemberInfo($options['s'], $options['g'], $memberWxid);

        // 构造成员详情
        $data = [];
        if (!empty($res->nickname)) $data['nickname'] = $res->nickname;
        if (!empty($res->headimgurl)) $data['avatar'] = $res->headimgurl;

        // 更新成员详情
        WechatAccount::whereAccount($memberWxid)->update($data);

        return 0;
    }
}
