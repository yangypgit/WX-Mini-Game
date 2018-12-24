<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\ArrayModel;
use App\Models\StoreModel;
use App\Models\CheckInTableModel;
use App\Models\InvitationListModel;
use App\Library\CustomPage;

class UserController extends Controller
{
    protected $CustomPage;

    public function __construct()
    {
        $this->CustomPage = new CustomPage();
        $this->UserModel = UserModel::getInstance();
        $this->RoleModel = RoleModel::getInstance();
        $this->ArrayModel = ArrayModel::getInstance();
        $this->StoreModel = StoreModel::getInstance();
        $this->CheckInTableModel = CheckInTableModel::getInstance();
        $this->InvitationListModel = InvitationListModel::getInstance();
    }

    public function test()
    {
            // 初始化签到表
            $data = ['id' => 25, 'day' => 0, 'today' => date("Y-m-d")];
            $ret = $this->CheckInTableModel->insert_info($data);
            if (!$ret)
            {
                $result['data'] = 'Initializes the check-in table error!';
                return $result;
            }
    }

    private function get_date_diff($start_time, $end_time='')
    {
        $end_time = ($end_time == '') ? date("Y-m-d") : $end_time;
        $datetime1 = new \DateTime($start_time);
        $datetime2 = new \DateTime($end_time);
        $interval = $datetime1->diff($datetime2);
        $time = $interval->format('%d');

        return $time;
    }

    /* 登录 */
    public function login(Request $request)
    {
        // 获取openid 昵称 头像
        $openid = $request->input('openid');
        $ret = $this->str_check_param($openid, "openid");
        if ($ret)
        {
            return $ret;
        }
        $name = $request->input('name');
        $ret = $this->str_check_param($name, "name");
        if ($ret)
        {
            return $ret;
        }
        $head = $request->input('head');
        $ret = $this->str_check_param($head, "head");
        if ($ret)
        {
            return $ret;
        }
        $inviter = $request->input('inviter');
        $ret = $this->check_param($inviter, "inviter");
        if ($ret)
        {
            return $ret;
        }

        $time = time();
        $token = md5(md5(uniqid(mt_rand(), true).time()));
        $user_info = $this->UserModel->get_user_info(['openid' => $openid]);
        if (!$user_info)
        {
            // 创建用户
            $arr = ['update_time' => $time, 'openid' => $openid, 'name' => $name,
                'head_portrait' => $head, 'grade' => 1, 'token' => $token, 'output' => 1.5];
            $this->UserModel->insert_user_info($arr);
            // 默认给阵容一个乞丐
            $obj = $this->UserModel->get_user_info(['openid' => $openid]);
            $user_arr = $this->CustomPage->objectToArray($obj);
            $id = $user_arr['id'];
            $role_obj = $this->RoleModel->get_role_info(['grade' => 1]);
            $role_arr = $this->CustomPage->objectToArray($role_obj);
            if (!empty($role_arr))
            {
                $array_arr = ['id' => $id, 'location' => 1, 'grade' => 1, 'output' => $role_arr['output']];
                $this->ArrayModel->insert_array_info($array_arr);
            }
            // 初始化商店
            $store_arr = ['id' => $id, 'grade' => 1, 'gold' => $role_arr['basics_gold'], 'diamond' => $role_arr['basics_diamond']];
            $this->StoreModel->insert_store_info($store_arr);
            // 初始化签到表
            $data = ['id' => $id, 'day' => 0, 'today' => date("Y-m-d")];
            $ret = $this->CheckInTableModel->insert_info($data);
            if (!$ret)
            {
                $result['data'] = 'Initializes the check-in table error!';
                return $result;
            }
        }
        else
        {
            // 更新用户
            $where['openid'] = $openid;
            $arr = ['name' => $name, 'head_portrait' => $head, 'token' => $token];
            $this->UserModel->update_data($where, $arr);
        }

        // 记录邀请者
        if ($inviter != -1)
        {
            $arr_info = $this->InvitationListModel->get_invitation_info(['id' => $inviter, 'openid' => $openid]);
            $arr = $this->CustomPage->objectToArray($arr_info);
            if (empty($arr))
            {
                $inv_info = $this->InvitationListModel->get_max_val(['id' => $inviter], 'number');
                if (empty($inv_info))
                {
                    // 没有数据
                    $number = 1;
                }
                else
                {
                    // 有数据 得到number + 1
                    $number = $inv_info + 1;
                }

                $data = ['id' => $inviter, 'openid' => $openid, 'name' => $name, 'head_portrait' => $head,
                            'button' => 1, 'number' => $number];
                $this->InvitationListModel->insert_info($data);
            }

        }

        // 返回用户表信息
        $obj = $this->UserModel->get_user_info(['openid' => $openid]);
        $result = $this->CustomPage->objectToArray($obj);
        
        return $result;
    }

    /* 获取主页面信息 */
    public function get_main_page_info(Request $request)
    {
        $id = $request->input('id');
        $ret = $this->check_param($id, "id");
        if ($ret)
        {
            return $ret;
        }

        $obj = $this->UserModel->get_user_info(['id' => $id]);
        $user_arr = $this->CustomPage->objectToArray($obj);
        if (!empty($user_arr))
        {
            $result['gold'] = $user_arr['gold'];
            $result['diamond'] = $user_arr['diamond'];

            $grade = $user_arr['grade'];
            $role_obj = $this->RoleModel->get_role_info(['grade' => $grade]);
            $role_arr = $this->CustomPage->objectToArray($role_obj);
            if (!empty($role_arr))
            {
                $result['title'] = $role_arr['role_name'];
            }

            // 推荐 LV 金币
            $result['recommend_grade'] = $user_arr['recommend'];
            $store_info = $this->StoreModel->get_role_info(['id' => $id, 'grade' => $user_arr['recommend']]);
            $store_arr = $this->CustomPage->objectToArray($store_info);
            if (!empty($store_arr))
            {
                $result['recommend_gold'] = $store_arr['gold'];
            }
        }

        // 阵容
        $array_obj = $this->ArrayModel->get_array_info(['id' => $id]);
        // $array_arr = $this->CustomPage->objectToArray($array_obj);
        $result['array'] = $this->CustomPage->objectToArray($array_obj->toArray());
        // 是否签到
        $check_info = $this->CheckInTableModel->get_check_info(['id' => $id]);
        $check = $this->CustomPage->objectToArray($check_info);
        if (empty($check))
        {
            $result['data'] = 'Check the sign in error!';
            $result['flag'] = -1;
            return $result;
        }

        $ret = $this->get_date_diff($check['today']);
        if ($ret > 0)
        {
            // on-off 置零
            $where['id'] = $id;
            $ret = $this->CheckInTableModel->update_data($where, ['on-off' => 0]);
            if (!$ret)
            {
                $result['data'] = 'Set the sign in on-off error!';
                $result['flag'] = -1;
                return $result;
            }
            $result['type'] = 0;
        }
        else
        {
            $result['type'] = $check['on-off'];
        }
        
        return $result;
    }

    /* 获取商店信息 */
    public function get_store_info(Request $request)
    {
        //1、金币 2、人物及等级 3、钻石or金币 
        $id = $request->input('id');
        $ret = $this->check_param($id, "id");
        if ($ret)
        {
            return $ret;
        }

        $obj = $this->UserModel->get_user_info(['id' => $id]);
        $user_arr = $this->CustomPage->objectToArray($obj);
        if (!empty($user_arr))
        {
            $result['gold'] = $user_arr['gold'];
            $result['grade'] = $user_arr['grade'];

            $grade = $user_arr['grade'];
            if ($grade  <= 5)
            {
                // 只能买1级
                $ret1 = $this->get_store_buy($id, 1);
                if(!empty($ret1))
                {
                    foreach($ret1 as $key => $value)
                    {
                        $ret1[$key]['type'] = 1; //金币购买
                    }
                }

                $ret2 = $this->get_store_buy($id, '', [2, $grade], 'grade');
                if(!empty($ret2))
                {
                    foreach($ret2 as $key => $value)
                    {
                        $ret2[$key]['type'] = 3; //不能购买
                    }
                }

                // 合并 处理
                $_arr = array_merge($ret1,$ret2);
                $_grades = array_column($_arr,'grade');
                array_multisort($_grades,SORT_ASC,$_arr);

                $result['store'] = $_arr;
            }
            else
            {
                // 段位等级-2 -3用钻石 -4以下用金币
                $ret1 = $this->get_store_buy($id, '', [1, $grade - 4], 'grade');
                if(!empty($ret1))
                {
                    foreach($ret1 as $key => $value)
                    {
                        $ret1[$key]['type'] = 1; //金币购买
                    }
                }

                $ret2 = $this->get_store_buy($id, '', [$grade - 3, $grade - 2], 'grade');
                if(!empty($ret2))
                {
                    foreach($ret2 as $key => $value)
                    {
                        $ret2[$key]['type'] = 2; //钻石购买
                    }
                }

                $ret3 = $this->get_store_buy($id, '', [$grade - 1, $grade], 'grade');
                if(!empty($ret3))
                {
                    foreach($ret3 as $key => $value)
                    {
                        $ret3[$key]['type'] = 3; //不能购买
                    }
                }

                // 合并 处理
                $_arr = array_merge($ret1, $ret2, $ret3);
                $_grades = array_column($_arr, 'grade');
                array_multisort($_grades, SORT_ASC, $_arr);

                $result['store'] = $_arr;
            }
        }

        return $result;
    }

    private function get_store_buy($id, $grade='', $between=[],$filed='')
    {
        $where['id'] = $id;
        if(!empty($grade))
        {
           $where['grade'] = $grade;
        }

        $store_info = $this->StoreModel->get_store_info($where,$between,$filed);
        $store_arr = $this->CustomPage->objectToArray($store_info->toArray());
        if($store_arr)
        {
            return $store_arr;
        }

        return [];
    }

    /* 心跳 */
    public function heartbeat(Request $request)
    {
        $id = $request->input('id');
        $ret = $this->check_param($id, "id");
        if ($ret)
        {
            return $ret;
        }

        $obj = $this->UserModel->get_user_info(['id' => $id]);
        $user_arr = $this->CustomPage->objectToArray($obj);
        if (!empty($user_arr))
        {
            $out_gold = 0;
            $gold = $user_arr['gold'];
            $now_time = time();
            $time_difference = $now_time - $user_arr['update_time'];
            if ($time_difference > 2 * 60 * 60)
            {
                // 离线奖励
                $out_gold = 2 * 60 * 60 * $user_arr['output'];
                $result['type'] = 2;
            }
            else
            {
                // 正常更新金币
                $out_gold = $time_difference * $user_arr['output'];
                $result['type'] = 1;
            }

            $where_gold['id'] = $id;
            $filed = 'gold';
            $this->UserModel->increment_data($where_gold, $filed, $out_gold);
            $filed_time = ['update_time' => $now_time];
            $this->UserModel->update_data($where_gold, $filed_time);

            $result['gold'] = $gold + $out_gold;
            $result['flag'] = 0;
        }
        else
        {
            $result['data'] = 'User information does not exist';
            $result['flag'] = -1;
        }

        return $result;
    }

    /* 开始加速 */
    public function start_speed_up(Request $request)
    {
        $id = $request->input('id');
        $ret = $this->check_param($id, "id");
        if ($ret)
        {
            return $ret;
        }

        $type = $request->input('type');
        $ret = $this->check_param($type, "type");
        if ($ret)
        {
            return $ret;
        }

        $obj = $this->UserModel->get_user_info(['id' => $id]);
        $user_arr = $this->CustomPage->objectToArray($obj);
        if (!empty($user_arr))
        {
            if ($type == 1)
            {
                // 1 表示扣10个钻石
                $where['id'] = $id;
                $filed = 'diamond';
                $this->UserModel->decrement_data($where, $filed, 10);

                $result['diamond'] = $user_arr['diamond'] - 10;
                $result['type'] = 1;
            }
            elseif ($type == 2)
            {
                // 2 表示看广告不扣费
                $result['diamond'] = $user_arr['diamond'];
                $result['type'] = 2;
            }
            else
            {
                $result['data'] = 'type error!';
                $result['flag'] = -1;
                return $result;
            }

            $where['id'] = $id;
            $filed_time = ['speed_up' => time()];
            $this->UserModel->update_data($where, $filed_time);
            $result['data'] = 'start_speed_up ok!';
            $result['flag'] = 0;
        }
        else
        {
            $result['data'] = 'User information does not exist';
            $result['flag'] = -1;
        }

        return $result;
    }

    /* 结束加速 */
    public function end_speed_up(Request $request)
    {
        $id = $request->input('id');
        $ret = $this->check_param($id, "id");
        if ($ret)
        {
            return $ret;
        }

        $obj = $this->UserModel->get_user_info(['id' => $id]);
        $user_arr = $this->CustomPage->objectToArray($obj);
        if (!empty($user_arr))
        {
            $out_gold = 0;
            $gold = $user_arr['gold'];
            $time_difference = time() - $user_arr['speed_up'];
            if ($time_difference > 60)
            {
                $out_gold = 60 * $user_arr['output'];
            }
            else
            {
                $out_gold = $time_difference * $user_arr['output'];
            }

            $where['id'] = $id;
            $filed = 'gold';
            $this->UserModel->increment_data($where, $filed, $out_gold);

            // speed_up 置零
            $filed_time = ['speed_up' => 0];
            $this->UserModel->update_data($where, $filed_time);

            $result['gold'] = $gold + $out_gold;
            $result['flag'] = 0;
        }
        else
        {
            $result['data'] = 'User information does not exist';
            $result['flag'] = -1;
        }

        return $result;
    }

    /* 获取每日奖励 */
    public function get_daily_bonus(Request $request)
    {
        $id = $request->input('id');
        $ret = $this->check_param($id, "id");
        if ($ret)
        {
            return $ret;
        }
        
        $result['flag'] = -1;
        // 查签到表
        $check_info = $this->CheckInTableModel->get_check_info(['id' => $id]);
        $check = $this->CustomPage->objectToArray($check_info);
        if (!empty($check))
        {
            // 有签到信息
            $result['day'] = $check['day'] % 7;
            $result['flag'] = 0;
        }
        else
        {
            // 没有签到信息
            $data = ['id' => $id, 'day' => 0, 'today' => date("Y-m-d")];
            $ret = $this->CheckInTableModel->insert_info($data);
            if ($ret)
            {
                $result['day'] = 0;
                $result['flag'] = 0;
            }
        }

        return $result;
    }

    /* 签到 */
    public function check_in(Request $request)
    {
        $id = $request->input('id');
        $ret = $this->check_param($id, "id");
        if ($ret)
        {
            return $ret;
        }

        $type = $request->input('type');
        $ret = $this->check_param($type, "type");
        if ($ret)
        {
            return $ret;
        }

        // 查签到表
        $day = 0;
        $result['flag'] = -1;
        $check_info = $this->CheckInTableModel->get_check_info(['id' => $id]);
        $check = $this->CustomPage->objectToArray($check_info);
        if (empty($check))
        {
            $result['flag'] = -1;
            $result['data'] = 'Please first daily_bonus!';
            return $result;
        }

        if ($check['on-off'] == 0)
        {
            $day = (($check['day'] + 1) % 7 != 0) ? (($check['day'] + 1) % 7) : 7;

            $where['id'] = $id;
            $filed = 'day';
            $this->CheckInTableModel->increment_data($where, 'day', 1);
            $this->CheckInTableModel->update_data($where, ['on-off' => 1]);

            $result['day'] = $day;
            $result['flag'] = 0;
        }
        else
        {
            $result['flag'] = -1;
            $result['data'] = 'You have signed in today!';
            return $result;
        }

        $bonus_info = $this->CheckInTableModel->get_daily_bonus(['day' => $day]);
        $bonus = $this->CustomPage->objectToArray($bonus_info);
        if (!empty($bonus))
        {
            $diamond = $bonus['award'];
            if ($type == 1)
            {
                // 1倍钻石
            }
            elseif ($type == 2)
            {
                // 2倍钻石
                $diamond *= 2;
            }
            else
            {
                $result['data'] = 'type error!';
                $result['flag'] = -1;
                return $result;
            }

            // 给用户加钻石
            $user_info = $this->UserModel->get_user_info(['id'=>$id]);
            if (empty($user_info))
            {
                $result['data'] = "The user does not exist!";
                $result['flag'] = -1;
            }
            else
            {
                $where['id'] = $id;
                $filed = 'diamond';
                $this->UserModel->increment_data($where, $filed, $diamond);

                $result['diamond'] = $diamond;
                $result['flag'] = 0;
            }
        }

        return $result;
    }

    private function check_param($param, $str)
    {
        if (empty($param) || !is_numeric($param))
        {
            $result['data'] = $str . " is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        return false;
    }

    private function str_check_param($param, $str)
    {
        if (empty($param) || !is_string($param))
        {
            $result['data'] = $str . " is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        return false;
    }

    /* 获取邀请好友列表 */
    public function get_invitation_list(Request $request)
    {
        $id = $request->input('id');
        $ret = $this->check_param($id, "id");
        if ($ret)
        {
            return $ret;
        }

        $result['flag'] = -1;
        $arr_info = $this->InvitationListModel->get_invitation_list_tab(['id' => $id]);
        $arr = $this->CustomPage->objectToArray($arr_info->toArray());
        if (!empty($arr))
        {
            $result['array'] = $arr;
            $result['flag'] = 0;
        }
        else
        {
            $result['array'] = [];
            $result['flag'] = 0;
        }

        return $result;
    }

    /* 邀请好友领奖励 */
    public function receive_award(Request $request)
    {
        $id = $request->input('id');
        $ret = $this->check_param($id, "id");
        if ($ret)
        {
            return $ret;
        }

        $number = $request->input('number');
        $ret = $this->check_param($number, "number");
        if ($ret)
        {
            return $ret;
        }

        $result['flag'] = -1;

        // 给用户加钻石
        $diamond = 200;
        $user_info = $this->UserModel->get_user_info(['id'=>$id]);
        $user_arr = $this->CustomPage->objectToArray($user_info);
        if (empty($user_arr))
        {
            $result['data'] = "The user does not exist!";
            $result['flag'] = -1;
        }
        else
        {
            $arr_info = $this->InvitationListModel->get_invitation_info(['id' => $id, 'number' => $number]);
            $arr = $this->CustomPage->objectToArray($arr_info);
            if (!empty($arr) && $arr['button'] != 0)
            {
                $where['id'] = $id;
                $filed = 'diamond';
                $this->UserModel->increment_data($where, $filed, $diamond);

                $diamond = $user_arr['diamond'] + 200;
                $result['diamond'] = $diamond;
                $result['flag'] = 0;

                $where = ['number' => $number];
                $data = ['button' => 0];
                $ret = $this->InvitationListModel->update_data($where, $data);
            }
            else
            {
                $result['data'] = 'No reward to get!';
                $result['flag'] = -1;
            }

        }

        return $result;
    }

    /* 分享加金币 */
    public function add_gold(Request $request)
    {
        $id = $request->input('id');
        $ret = $this->check_param($id, "id");
        if ($ret)
        {
            return $ret;
        }

        // 加金币
        $result['flag'] = -1;
        $obj = $this->UserModel->get_user_info(['id'=>$id]);
        $user_arr = $this->CustomPage->objectToArray($obj);
        if (empty($user_arr))
        {
            $result['data'] = "The user does not exist!";
        }
        else
        {
            $gold = 0;
            $ret1 = $this->StoreModel->get_role_info(['id' => $id, 'grade' => $user_arr['grade']]);
            $arr = $this->CustomPage->objectToArray($ret1);
            if(!empty($arr))
            {
                $gold = $arr['gold'];
            }
            else
            {
                $result['data'] = 'system get role info error!';
                return $result;
            }

            $where['id'] = $id;
            $filed = 'gold';
            $ret = $this->UserModel->increment_data($where, $filed, $gold);
            if ($ret)
            {
                // 返回加了多少金币
                $result['gold'] = $gold;
                $result['flag'] = 0;
            }
            else
            {
                $result['data'] = 'system error!';
            }
        }

        return $result;
    }
}
