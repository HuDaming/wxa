<?php

namespace Hudm\Wxa;

use Illuminate\Support\Facades\Facade;

class WechatAssistantFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return WechatAssistant::class;
    }
}