<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWechatAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default(\App\Models\WechatAccount::TYPE_COMMON)->comment('账号类型');
            $table->string('account')->unique()->comment('微信账号');
            $table->string('nickname')->comment('微信昵称');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wechat_accounts');
    }
}
