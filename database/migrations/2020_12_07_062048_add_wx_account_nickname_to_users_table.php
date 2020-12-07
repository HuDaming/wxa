<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWxAccountNicknameToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('wx_account')->nullable()->after('remember_token')->comment('微信账号');
            if (!Schema::hasColumn('users', 'nickname')) {
                $table->string('nickname')->nullable()->after('name')->comment('昵称');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wx_account', 'nickname']);
        });
    }
}
