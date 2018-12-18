<?php

namespace App\Http\Controllers;

// use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TicketModel;
use App\Models\DrawsModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Library\CustomPage;

class TurntableController extends Controller
{
    protected $TicketModel;
    protected $CustomPage;

    public function __construct()
    {
        $this->TicketModel = TicketModel::getInstance();
        $this->DrawsModel = DrawsModel::getInstance();
        $this->UserModel = UserModel::getInstance();
        $this->RoleModel = RoleModel::getInstance();
        $this->CustomPage = new CustomPage();
    }

    // 1.验证用户数据（是否有券）
    public function get_user_ticket($id)
    {
        // 读数据库
        $ticket = $this->TicketModel->get_ticket(['id'=>$id]);
        $ticket_arr = $this->CustomPage->objectToArray($ticket);
        $result = $ticket_arr['ticket'];

        return $result;
    }

    /* 1、获取券 */
    public function get_ticket(Request $request)
    {
        $id = $request->input('id');
        // var_dump($id);
        // 验证   
        if (empty($id) || !is_numeric($id))
        {
            $result['data'] = "id is empty or error!";
            $result['flag'] = -1;
            return $result;
        }
        
        $result['ticket'] = $this->get_user_ticket($id);
        return $result;
    }
    
    /* 2、抽奖 */
    // 流程：1.拼装奖项数组，2.计算概率，3.返回中奖情况
    public function get_gift(Request $request)
    {
        $id = $request->input('id');
        // 验签
        
        // 验证   
        if (empty($id) || !is_numeric($id))
        {
            $result['data'] = "id is empty or error!";
            $result['flag'] = -1;
            return $result;
        }
        
        // 1.验证用户数据（是否有券）
        if (!$this->get_user_ticket($id))
        {
            $result['data'] = "ticket is empty!";
            $result['flag'] = -1;
            return $result;
        }

        // 扣费
        $this->fee_deduction_ticket($id);

        // 抽奖
        $prize_tab = $this->DrawsModel->get_prize_arr();
        $prize_arr = $this->CustomPage->objectToArray($prize_tab->toArray());
        // var_dump($prize_arr);

        foreach ($prize_arr as $key => $val)
        {
            // 得到概率数组
            $arr[$val['id']] = $val['weight'];
        }

        // 根据概率获取奖项id
        $rid = $this->get_rand($arr);
        // 给用户加奖励
        $award_type = $prize_arr[$rid - 1]['award_type'];
        if ($award_type == 1)
        {
            // 金币
            $type = $prize_arr[$rid - 1]['value_type'];
            $user_info = $this->UserModel->get_user_info(['id'=>$id]);
            $user_arr = $this->CustomPage->objectToArray($user_info);
            $grade = $user_arr['grade'];
            $num = $this->award_gold($type, $grade);
            // 查看是否有翻倍
            $luck = $user_arr['luck'];
            $result['gold'] = $num * $luck;
            $this->update_luck($id, 1);
            // 给用户加金币
            $this->add_gold($id, $num * $luck);
            $result['type'] = 1;
        }
        elseif ($award_type == 2)
        {
            // 钻石
            $num = $prize_arr[$rid - 1]['value'];
            // 查看是否有翻倍
            $user_info = $this->UserModel->get_user_info(['id'=>$id]);
            $user_arr = $this->CustomPage->objectToArray($user_info);
            $luck = $user_arr['luck'];
            $result['diamond'] = $num * $luck;
            $this->update_luck($id, 1);
            // 给用户加钻石
            $this->add_diamond($id, $num * $luck);
            $result['type'] = 2;
        }
        elseif ($award_type == 3)
        {
            // 宝箱
            $num = $prize_arr[$rid - 1]['value'];
            $result['box'] = $num;
            // 更新翻倍
            // $this->update_luck($id, $num);
            $result['type'] = 3;
        }
        
        $result['id'] = $rid - 1;

        return $result;
        // var_dump($result);
    }

    // 3.计算概率
    public function get_rand($proArr)
    {
        $result = '';
        // 概率数组的总概率精度
        $proSum = array_sum($proArr);
        // 概率数组循环
        foreach ($proArr as $key => $proCur)
        {
            // 返回随机整数
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur)
            {
                $result = $key;
                break;
            }
            else
            {
                $proSum -= $proCur;
            }
        }

        unset($proArr);
        return $result;
    }

    /* 加转盘券 */
    public function add_ticket(Request $request)
    {
        $id = $request->input('id');
        if (empty($id) || !is_numeric($id))
        {
            $result['data'] = "id is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        $ticket = $this->TicketModel->get_ticket(['id'=>$id]);
        if (empty($ticket))
        {
            $arr = ['id' => $id, 'ticket' => 5];
            $ret = $this->TicketModel->insert_ticket($arr);
        }
        else
        {
            $where['id'] = $id;
            $filed = 'ticket';
            $num = 5;
            $ret = $this->TicketModel->increment_ticket($where,$filed,$num);
        }

        $result['flag'] = -1;

        if($ret)
        {
            $result['ticket'] = $this->get_user_ticket($id);
            $result['flag'] = 0;
        }

        return $result;
    }

    /* 转盘券清零 */
    public function clear_ticket(Request $request)
    {
        $id = $request->input('id');
        if (empty($id) || !is_numeric($id))
        {
            $result['data'] = "id is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        $ticket = $this->TicketModel->get_ticket(['id'=>$id]);
        if (empty($ticket))
        {
            $arr = ['id' => $id, 'ticket' => 0];
            $ret = $this->TicketModel->insert_ticket($arr);
        }
        else
        {
            $where['id'] = $id;
            $arr = ['id' => $id, 'ticket' => 0];
            $ret = $this->TicketModel->update_ticket($where, $arr);
        }

        $result['flag'] = -1;

        if($ret)
        {
            $result['ticket'] = $this->get_user_ticket($id);
            $result['flag'] = 0;
        }

        return $result;
    }

    /* 转盘券费用扣除 */
    public function fee_deduction_ticket($id)
    {
        $ticket = $this->TicketModel->get_ticket(['id'=>$id]);
        if (!empty($ticket))
        {
            $where['id'] = $id;
            $filed = 'ticket';
            $num = 1;
            $ret = $this->TicketModel->decrement_data($where,$filed,$num);
        }
    }

    /* 加钻石 */
    // public function add_diamond(Request $request)
    private function add_diamond($id, $diamond)
    {
/*
        // 验签
        
        // 验证   
        $id = $request->input('id');
        if (empty($id) || !is_numeric($id))
        {
            $result['data'] = "id is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        $diamond = $request->input('diamond');
        if (empty($diamond) || !is_numeric($diamond))
        {
            $result['data'] = "diamond is empty or error!";
            $result['flag'] = -1;
            return $result;
        }
 */

        // 加钻石
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
            $ret = $this->UserModel->increment_data($where,$filed,$diamond);
        }

        $result['flag'] = -1;

        if($ret)
        {
            $user_info = $this->UserModel->get_user_info(['id'=>$id]);
            $ticket_arr = $this->CustomPage->objectToArray($user_info);
            $result['diamond'] = $ticket_arr['diamond'];
            $result['flag'] = 0;
        }

        return $result;
    }

    /* 加金币 */
    // public function add_gold(Request $request)
    private function add_gold($id, $gold)
    {
        // 验签
        
/*
        // 验证   
        $id = $request->input('id');
        if (empty($id) || !is_numeric($id))
        {
            $result['data'] = "id is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        $gold = $request->input('gold');
        if (empty($gold) || !is_numeric($gold))
        {
            $result['data'] = "gold is empty or error!";
            $result['flag'] = -1;
            return $result;
        }
 */

        // 加金币
        $user_info = $this->UserModel->get_user_info(['id'=>$id]);
        if (empty($user_info))
        {
            $result['data'] = "The user does not exist!";
            $result['flag'] = -1;
        }
        else
        {
            $where['id'] = $id;
            $filed = 'gold';
            $ret = $this->UserModel->increment_data($where,$filed,$gold);
        }

        $result['flag'] = -1;

        if($ret)
        {
            $user_info = $this->UserModel->get_user_info(['id'=>$id]);
            $ticket_arr = $this->CustomPage->objectToArray($user_info);
            $result['gold'] = $ticket_arr['gold'];
            $result['flag'] = 0;
        }

        return $result;
    }

    private function award_gold($value, $grade)
    {
        // 金币
        $role_info = $this->RoleModel->get_role_info(['grade'=>$grade]);
        $role_arr = $this->CustomPage->objectToArray($role_info);
        $num = $role_arr['basics_gold'];
        if ($value == 1)
        {
            // 少量 原价的30%
            return $num * 0.3;
        }
        elseif ($value == 2)
        {
            // 中量 原价的50%
            return $num * 0.5;
        }
        elseif ($value == 3)
        {
            // 大量 原价的80%
            return $num * 0.8;
        }
        elseif ($value == 4)
        {
            // 海量 原价的120%
            return $num * 1.2;
        }
    }

    private function update_luck($id, $num)
    {
        // 更新翻倍
        $where['id'] = $id;
        $arr = ['id' => $id, 'luck' => $num];
        $this->UserModel->update_data($where, $arr);
    }

    /* 更新宝箱 */
    public function update_box(Request $request)
    {
        // 验证   
        $id = $request->input('id');
        if (empty($id) || !is_numeric($id))
        {
            $result['data'] = "id is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        $box = $request->input('box');
        if (empty($box) || !is_numeric($box) || $box > 10)
        {
            $result['data'] = "box is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        // 更新翻倍
        $this->update_luck($id, $box);

        $result['flag'] = 0;
        return $result;
    }
}
