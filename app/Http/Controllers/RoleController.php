<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoleModel;
use App\Models\UserModel;
use App\Models\ArrayModel;
use App\Models\StoreModel;
use App\Library\CustomPage;

class RoleController extends Controller
{
    protected $CustomPage;

    public function __construct()
    {
        $this->CustomPage = new CustomPage();
        $this->RoleModel = RoleModel::getInstance();
        $this->UserModel = UserModel::getInstance();
        $this->ArrayModel = ArrayModel::getInstance();
        $this->StoreModel = StoreModel::getInstance();
    }

    /* 角色回收 */
    public function recycle_role(Request $request)
    {
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

        $location = $request->input('location');
        if (empty($location) || !is_string($location))
        {
            $result['data'] = "location is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        $result['flag'] = -1;
        // 按角色原价的80% 给金币
        $role_obj = $this->RoleModel->get_role_info(['grade' => $grade]);
        $role_arr = $this->CustomPage->objectToArray($role_obj);
        if (!empty($role_arr))
        {
            $num = $role_arr['basics_gold'] * 0.8;
            $result['gold'] = $num;
            // 给用户加金币
            $user_info = $this->UserModel->get_user_info(['id'=>$id]);
            if (empty($user_info))
            {
                $result['data'] = "The user does not exist!";
                $result['flag'] = -1;
            }
            else
            {
                // 移除角色
                $ret = $this->ArrayModel->del_array_info(['id' => $id, 'location' => $location]);
                if ($ret)
                {
                    $where['id'] = $id;
                    $filed = 'gold';
                    $this->UserModel->increment_data($where, $filed, $num);
                    $result['flag'] = 0;
                }
            }
        }

        return $result;
    }

    /* 合并 */
    public function merge_role(Request $request)
    {
        // 参数验证
        $id = $request->input('id');
        if (empty($id) || !is_numeric($id))
        {
            $result['data'] = "id is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        $current_position = $request->input('current_position');
        if (empty($current_position) || !is_numeric($current_position) || $current_position <= 0)
        {
            $result['data'] = "current_position is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        $grade = 0;
        $role_obj = $this->ArrayModel->get_role_location(['id' => $id, 'location' => $current_position]);
        $role_arr = $this->CustomPage->objectToArray($role_obj);
        if (!empty($role_arr))
        {
            $grade = $role_arr['grade'];
        }
        else
        {
            $result['data'] = "current_position is error!";
            $result['flag'] = -2;
            return $result;
        }

        $target_location = $request->input('target_location');
        if (empty($target_location) || !is_numeric($target_location) || $target_location <= 0 
                || $current_position == $target_location)
        {
            $result['data'] = "target_location is empty or error!";
            $result['flag'] = -1;
            return $result;
        }

        // 判断目标位置是否有角色
        $role_obj = $this->ArrayModel->get_role_location(['id' => $id, 'location' => $target_location]);
        $role_arr = $this->CustomPage->objectToArray($role_obj);
        if (!empty($role_arr))
        {
            // 目标位置有角色
            $ret = $this->interchange_or_merge($id, $current_position, $target_location, $grade, $role_arr['grade']);
            if ($ret)
            {
                $result['array'] = $ret;
                $result['type'] = 1;
            }
            else
            {
                $result['flag'] = -1;
                $result['data'] = 'system error!';
                return $result;
            }
        }
        else 
        {
            // 目标位置为空 直接替换位置
            $where = ['id' => $id, 'location' => $current_position];
            $arr = ['id' => $id, 'location' => $target_location];
            $ret = $this->ArrayModel->update_data($where, $arr);
            if (!$ret)
            {
                return false;
            }

            $ret = $this->location_replace($id, $current_position, $target_location);
            if ($ret)
            {
                $result['array'] = $ret;
                $result['type'] = 2;
            }
            else
            {
                $result['flag'] = -1;
                $result['data'] = 'system error!';
                return $result;
            }
        }

        $obj = $this->UserModel->get_user_info(['id' => $id]);
        $user_arr = $this->CustomPage->objectToArray($obj);
        if (!empty($user_arr))
        {
            $result['gold'] = $user_arr['gold'];
            $result['output'] = $user_arr['output'];

            $grade = $user_arr['grade'];
            $role_obj = $this->RoleModel->get_role_info(['grade' => $grade]);
            $role_arr = $this->CustomPage->objectToArray($role_obj);
            if (!empty($role_arr))
            {
                $result['title'] = $role_arr['role_name'];
            }
        }

        return $result;
    }

    private function get_role_array_info($id, $location)
    {
        $arr = $this->ArrayModel->get_role_location(['id' => $id, 'location' => $location]);
        if ($arr)
        {
            return $arr;
        }
        else
        {
            return false;
        }
    }

    private function interchange_or_merge($id, $current_position, $target_location, $cur_grade, $tar_grade)
    {
        // 判断是合并还是交换位置
        if ($tar_grade != $cur_grade)
        {
            // 交换位置
            $ret = $this->interchange($id, $current_position, $target_location, $tar_grade);
            if ($ret)
            {
                return $ret;
            }
            else
            {
                return false;
            }
        }
        else
        {
            // 合并 判断是否刷新等级
            $ret = $this->role_merge($id, $current_position, $target_location, $tar_grade);
            if ($ret)
            {
                $ret = $this->location_replace($id, $current_position, $target_location);
                if ($ret)
                {
                    return $ret;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }
    }

    private function location_replace($id, $current_position, $target_location)
    {
        // 拼接数据给前端
        $tar_arr = $this->ArrayModel->get_role_location(['id' => $id, 'location' => $target_location]);
        if ($tar_arr)
        {
            $wz1['location'] = (int)$current_position;
            $wz1['grade'] = 0;
            $wz1['output'] = '0';
            $array[] = $wz1;
            $array[] = $tar_arr;

            return $array;
        }
        else
        {
            return false;
        }
    }

    private function interchange($id, $current_position, $target_location, $tar_grade)
    {
        $where = ['id' => $id, 'location' => $current_position];
        $arr = ['id' => $id, 'location' => $target_location];
        $ret = $this->ArrayModel->update_data($where, $arr);
        if (!$ret)
        {
            return false;
        }

        $where = ['id' => $id, 'location' => $target_location, 'grade' => $tar_grade];
        $arr = ['id' => $id, 'location' => $current_position];
        $ret = $this->ArrayModel->update_data($where, $arr);
        if (!$ret)
        {
            return false;
        }

        $array = [];
        $ret1_arr = $this->ArrayModel->get_role_location(['id' => $id, 'location' => $current_position]);
        if ($ret1_arr)
        {
            $array[] = $ret1_arr;
        }
        else
        {
            return false;
        }

        $ret2_arr = $this->ArrayModel->get_role_location(['id' => $id, 'location' => $target_location]);
        if ($ret2_arr)
        {
            $array[] = $ret2_arr;
        }
        else
        {
            return false;
        }

        return $array;
    }

    private function role_merge($id, $current_position, $target_location, $tar_grade)
    {
        // 得到上一级角色
        $role_obj = $this->RoleModel->get_role_info(['grade' => $tar_grade]);
        $role_arr = $this->CustomPage->objectToArray($role_obj);
        if (empty($role_arr))
        {
            return false;
        }

        $fid = $role_arr['fid'];
        // 判断上级是否存在
        $role_obj_2 = $this->RoleModel->get_role_info(['grade' => $fid]);
        $role_arr_2 = $this->CustomPage->objectToArray($role_obj_2);
        if (empty($role_arr_2))
        {
            return false;
        }

        $user_info = $this->UserModel->get_user_info(['id'=>$id]);
        $user_arr = $this->CustomPage->objectToArray($user_info);
        if (empty($user_arr))
        {
            return false;
        }

        // 删除当前位置角色
        $ret = $this->ArrayModel->del_array_info(['id' => $id, 'location' => $current_position]);
        if (!$ret)
        {
            return false;
        }

        // 金币每秒产出更新 结算
        $now_time = time();
        $out_gold = ($now_time - $user_arr['update_time']) * $user_arr['output'];
        $where_gold['id'] = $id;
        $filed = 'gold';
        $ret = $this->UserModel->increment_data($where_gold, $filed, $out_gold);
        if (!$ret)
        {
            return false;
        }

        $output_money = 0;
        $array_obj = $this->ArrayModel->get_array_info(['id' => $id]);
        $ret_val = $this->CustomPage->objectToArray($array_obj->toArray());
        if (!empty($ret_val))
        {
            foreach ($ret_val as $key => $val)
            {
                $output_money += $val['output'];
            }
        }
        else
        {
            return false;
        }

        $filed_time = ['update_time' => $now_time, 'output' => $output_money];
        $ret = $this->UserModel->update_data($where_gold, $filed_time);
        if (!$ret)
        {
            return false;
        }
        
        if ($fid > $user_arr['grade'])
        {
            // 更新段位和每秒产出
            $where_user['id'] = $id;
            $arr_user = ['grade' => $fid];
            $ret = $this->UserModel->update_data($where_user, $arr_user);
            if (!$ret)
            {
                return false;
            }
            // 更新商店
            $basics_gold = $role_arr_2['basics_gold'];
            $basics_diamond = $role_arr_2['basics_diamond'];
            $role_info = ['id' => $id, 'grade' => $fid, 'gold' => $basics_gold, 'diamond' => $basics_diamond];
            $ret = $this->StoreModel->insert_store_info($role_info);
            if (!$ret)
            {
                return false;
            }
        }

        $where = ['id' => $id, 'location' => $target_location];
        $arr = ['grade' => $fid, 'output' => $role_arr_2['output']];
        $ret = $this->ArrayModel->update_data($where, $arr);
        if (!$ret)
        {
            return false;
        }

        return true;
    }
}
