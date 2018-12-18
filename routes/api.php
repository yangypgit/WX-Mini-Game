<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['middleware' => 'CrossHttpMiddleware'], function()
{
    // test
    Route::get('test', 'TurntableController@test');

    // 抽奖 http://king.com/api/lottery?id=2
    Route::get('lottery', 'TurntableController@get_gift');

    // 获取转盘券 http://king.com/api/ticket?id=2
    Route::get('ticket', 'TurntableController@get_ticket');

    // 获取主页面信息 http://king.com/api/get_info?id=12
    Route::get('get_info', 'UserController@get_main_page_info');

    // 获取商店信息 http://king.com/api/store?id=12
    Route::get('store', 'UserController@get_store_info');

    // 获取每日奖励 http://king.com/api/daily_bonus?id=12
    Route::get('daily_bonus', 'UserController@get_daily_bonus');

    // 获取邀请好友列表 http://king.com/api/invitation_list?id=12
    Route::get('invitation_list', 'UserController@get_invitation_list');

    // 登录 http://king.com/api/login {openid:用户openid, name:昵称, head:头像, inviter:邀请者id 默认值为-1}
    Route::post('login', 'UserController@login');

    // 添加转盘券 http://king.com/api/add_ticket {id}
    Route::post('add_ticket', 'TurntableController@add_ticket');

    // 清空转盘券 http://king.com/api/clear_ticket {id}
    Route::post('clear_ticket', 'TurntableController@clear_ticket');

    // 添加钻石
    // Route::post('diamond', 'TurntableController@add_diamond');

    // 添加金币
    // Route::post('gold', 'TurntableController@add_gold');

    // 购买角色 http://king.com/api/buy_role {id, grade:等级, money:钱的类型 gold or diamond}
    Route::post('buy_role', 'StoreController@buy_role');

    // 推荐购买 http://king.com/api/recommended {id}
    Route::post('recommended', 'StoreController@recommended_buy');

    // 更新宝箱 http://king.com/api/box {id, box:宝箱数量}
    Route::post('box', 'TurntableController@update_box');

    // 角色回收 http://king.com/api/recycle {id , grade:等级, location:位置}
    Route::post('recycle', 'RoleController@recycle_role');

    // 角色合并 http://king.com/api/merge {id , current_position:当前位置, target_location:目标位置}
    Route::post('merge', 'RoleController@merge_role');

    // 心跳 http://king.com/api/heartbeat {id}
    Route::post('heartbeat', 'UserController@heartbeat');

    // 开始加速 http://king.com/api/start_speed_up {id, type: 1 or 2  1表示扣10个钻石 2表示看广告不扣费}
    Route::post('start_speed_up', 'UserController@start_speed_up');

    // 结束加速 http://king.com/api/end_speed_up {id}
    Route::post('end_speed_up', 'UserController@end_speed_up');

    // 签到 http://king.com/api/check_in {id, type: 1 or 2 表示1倍还是2倍}
    Route::post('check_in', 'UserController@check_in');

    // 邀请好友领奖励 http://king.com/api/receive_award {id, number:第几个位置的奖励}
    Route::post('receive_award', 'UserController@receive_award');
});

