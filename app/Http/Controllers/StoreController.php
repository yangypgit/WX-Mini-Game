<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\ArrayModel;
use App\Models\StoreModel;
use App\Library\CustomPage;

class StoreController extends Controller
{
    protected $CustomPage;

    public function __construct()
    {
        $this->CustomPage = new CustomPage();
        $this->UserModel = UserModel::getInstance();
        $this->RoleModel = RoleModel::getInstance();
        $this->ArrayModel = ArrayModel::getInstance();
        $this->StoreModel = StoreModel::getInstance();
    }

    /* 购买角色 */
    public function buy_role(Request $request)
    {
        // 参数验证
        $id = $request->input('id');
        if (empty($id) || !is_numeric($id))
        {
            $result['data'] = "id is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        $grade = $request->input('grade');
        if (empty($grade) || !is_numeric($grade))
        {
            $result['data'] = "grade is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        $money = $request->input('money');
        if (empty($money) || !is_string($money))
        {
            $result['data'] = "money is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        // 获取用户表中的数据
        $obj = $this->UserModel->get_user_info(['id' => $id]);
        $user_arr = $this->CustomPage->objectToArray($obj);
        if (empty($user_arr))
        {
            $result['data'] = "User information does not exist!";
            $result['flag'] = -1;
            return $result;
        }

        // 获取商店中角色信息
        $store_info = $this->StoreModel->get_role_info(['id' => $id, 'grade' => $grade]);
        $store_arr = $this->CustomPage->objectToArray($store_info);
        if (empty($store_arr))
        {
            $result['data'] = "id or grade is error!";
            $result['flag'] = -1;
            return $result;
        }

        // 先判断是否有位置
        $data = [];
        $array_obj = $this->ArrayModel->get_array_info(['id' => $id]);
        $ret_val = $this->CustomPage->objectToArray($array_obj->toArray());
        if (!empty($ret_val))
        {
            if (count($ret_val) < 12)
            {
                $data = array_column($ret_val, 'location');
                sort($data);
            }
            else
            {
                $result['flag'] = 2;
                $result['data'] = 'location is no empty!';
                return $result;
            }
        }
        else
        {
            $result['data'] = "get_array_info() error!";
            $result['flag'] = -1;
            return $result;
        }

        // 判断是金币还是钻石
        if ($money == 'gold')
        {
            // 是金币
            if ($grade > $user_arr['grade'] - 4 && $grade > 5)
            {
                $result['data'] = "grade is error!";
                $result['flag'] = -1;
                return $result;
            }

            if ($user_arr['gold'] - $store_arr['gold'] >= 0)
            {
                // 扣费 
                $where['id'] = $id;
                $filed = 'gold';
                $num = $store_arr['gold'];
                $this->UserModel->decrement_data($where,$filed,$num);
            }
            else 
            {
                $result['data'] = '金币不够！';
                $result['flag'] = 3;
                return $result;
            }

            // 角色涨价
            $where = ['id' => $id, 'grade' => $grade];
            $filed = 'gold';
            $num = $store_arr['gold'] * 0.8;
            $this->StoreModel->increment_data($where,$filed,$num);
            $result['role_gold'] = $num + $store_arr['gold'];
            $result['type'] = 1;
        }
        elseif ($money == 'diamond')
        {
            // 是钻石
            if ($grade > $user_arr['grade'] - 2 || $grade <= $user_arr['grade'] - 4)
            {
                $result['data'] = "grade is error!";
                $result['flag'] = -1;
                return $result;
            }

            if ($user_arr['diamond'] - $store_arr['diamond'] >= 0)
            {
                // 扣费 
                $where['id'] = $id;
                $filed = 'diamond';
                $num = $store_arr['diamond'];
                $ret = $this->UserModel->decrement_data($where,$filed,$num);
            }
            else 
            {
                $result['data'] = '钻石不够！';
                $result['flag'] = 4;
                return $result;
            }

            // 角色涨价
            $where = ['id' => $id, 'grade' => $grade];
            $filed = 'diamond';
            $num = $store_arr['diamond'] * 0.8;
            $this->StoreModel->increment_data($where,$filed,$num);
            $result['role_diamond'] = $num + $store_arr['diamond'];
            $result['type'] = 2;
        }
        else
        {
            $result['data'] = 'money type error！';
            $result['flag'] = -1;
            return $result;
        }

        // 在阵容里面插入一个角色 
        $ret = $this->insert_role($id, $data, $grade);
        if (!$ret)
        {
            $result['data'] = 'insert_role() error！';
            $result['flag'] = -1;
            return $result;
        }
        // 获取新插入角色的位置信息 和 最新金币信息
        $ret = $this->get_user_new_info($id, $ret);
        if (!$ret)
        {
            $result['data'] = 'get_user_new_info() error！';
            $result['flag'] = -1;
            return $result;
        }

        $result = array_merge($result, $ret);
        // $result = $ret;

        $result['flag'] = 0;
        return $result;
    }

    private function insert_role($id, $data, $grade)
    {
        // 插入新角色 结算 更新每秒产出
        $wz = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        $ret_wz = array_diff($wz, $data);
        sort($ret_wz);
        $role_obj = $this->RoleModel->get_role_info(['grade' => $grade]);
        $role_arr = $this->CustomPage->objectToArray($role_obj);
        if (empty($role_arr))
        {
            return false;
        }

        $output = $role_arr['output'];
        $arr = ['id' => $id, 'location' => $ret_wz[0], 'grade' => $grade, 'output' => $output];
        $ret = $this->ArrayModel->insert_array_info($arr);
        if (!$ret)
        {
            return false;
        }

        // 金币结算 
        $user_info = $this->UserModel->get_user_info(['id'=>$id]);
        $user_arr = $this->CustomPage->objectToArray($user_info);
        if (empty($user_arr))
        {
            return false;
        }
        $now_time = time();
        $out_gold = ($now_time - $user_arr['update_time']) * $user_arr['output'];
        $where_gold['id'] = $id;
        $filed = 'gold';
        $ret = $this->UserModel->increment_data($where_gold, $filed, $out_gold);
        if (!$ret)
        {
            return false;
        }

        // 更新每秒总产出 和 时间
        $output_money = $user_arr['output'] + $output;
        $filed_time = ['update_time' => $now_time, 'output' => $output_money];
        $ret = $this->UserModel->update_data($where_gold, $filed_time);
        if (!$ret)
        {
            return false;
        }

        return $ret_wz[0];
    }

    /* 推荐购买 */
    public function recommended_buy(Request $request)
    {
        $id = $request->input('id');
        if (empty($id) || !is_numeric($id))
        {
            $result['data'] = "id is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        // 获取用户表中的数据
        $obj = $this->UserModel->get_user_info(['id' => $id]);
        $user_arr = $this->CustomPage->objectToArray($obj);
        if (empty($user_arr))
        {
            $result['data'] = "User information does not exist!";
            $result['flag'] = -1;
            return $result;
        }

        $now_gold = 0;
        $recommended = $user_arr['recommend'];
        $store_info = $this->StoreModel->get_role_info(['id' => $id, 'grade' => $recommended]);
        $store_arr = $this->CustomPage->objectToArray($store_info);
        if (empty($store_arr))
        {
            $result['data'] = "get_role_info() error!";
            $result['flag'] = -1;
            return $result;
        }
        $now_gold = $store_arr['gold'];

        // 先判断是否有位置
        $data = [];
        $array_obj = $this->ArrayModel->get_array_info(['id' => $id]);
        $ret_val = $this->CustomPage->objectToArray($array_obj->toArray());
        if (!empty($ret_val))
        {
            if (count($ret_val) < 12)
            {
                $data = array_column($ret_val, 'location');
                sort($data);
            }
            else
            {
                $result['flag'] = 2;
                $result['data'] = 'location is no empty!';
                return $result;
            }
        }

        if ($user_arr['gold'] - $now_gold >= 0)
        {
            // 扣费 
            $where['id'] = $id;
            $filed = 'gold';
            $num = $now_gold;
            $this->UserModel->decrement_data($where,$filed,$num);
        }
        else 
        {
            $result['data'] = '金币不够！';
            $result['flag'] = 3;
            return $result;
        }

        // 角色涨价
        $where = ['id' => $id, 'grade' => $recommended];
        $filed = 'gold';
        $num = $now_gold * 0.8;
        $this->StoreModel->increment_data($where,$filed,$num);
        // $result['gold'] = $num + $now_gold;

        // 在阵容里面插入一个角色 
        $ret = $this->insert_role($id, $data, $recommended);
        if (!$ret)
        {
            $result['data'] = 'insert_role() error！';
            $result['flag'] = -1;
            return $result;
        }

        // 返回新插入角色的位置信息 
        $ret = $this->get_user_new_info($id, $ret);
        if (!$ret)
        {
            $result['data'] = 'get_user_new_info() error！';
            $result['flag'] = -1;
            return $result;
        }

        // $result = array_merge($result, $ret);
        $result = $ret;

        // 设置下次推荐
        $temp_grade = ($user_arr['grade'] - 4 > 0) ? ($user_arr['grade'] - 4) : 1;
        $user_store_info = $this->StoreModel->get_store_info(['id' => $id], [1, $temp_grade], 'grade');
        $user_store_arr = $this->CustomPage->objectToArray($user_store_info->toArray());
        if (!empty($user_store_arr))
        {
            $recommend = 1;
            $_grades = array_column($user_store_arr, 'grade');
            array_multisort($_grades, SORT_DESC, $user_store_arr);
            foreach($user_store_arr as $key => $value)
            {
                if ($value['gold'] < $user_arr['gold'])
                {
                    $result['recommend'] = $value['grade'];
                    $result['role_gold'] = $value['gold'];
                    break;
                }

                if ($value['grade'] == 1)
                {
                    $result['recommend'] = $value['grade'];
                    $result['role_gold'] = $value['gold'];
                }
            }

            $where_user['id'] = $id;
            $arr_user = ['recommend' => $recommend];
            $this->UserModel->update_data($where_user, $arr_user);
        }

        $result['flag'] = 0;
        return $result;
    }

    private function get_user_new_info($id, $param)
    {
        $tar_arr = $this->ArrayModel->get_role_location(['id' => $id, 'location' => $param]);
        if (!$tar_arr)
        {
            return $false;
        }
        $result['array'] = $tar_arr;

        // 获取用户最新金币 和每秒产出
        $obj = $this->UserModel->get_user_info(['id' => $id]);
        $user_arr = $this->CustomPage->objectToArray($obj);
        if (empty($user_arr))
        {
            $result['data'] = 'get_user_info() error！';
            $result['flag'] = -1;
            return $result;
        }
        $result['gold'] = $user_arr['gold'];
        $result['output'] = $user_arr['output'];

        return $result;
    }
}
