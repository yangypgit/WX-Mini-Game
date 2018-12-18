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

        // 验证当前位置的信息是否真实
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
            // 判断是合并还是交换位置
            if ($role_arr['grade'] != $grade)
            {
                $where = ['id' => $id, 'location' => $current_position];
                $arr = ['id' => $id, 'location' => $target_location];
                $this->ArrayModel->update_data($where, $arr);

                $where = ['id' => $id, 'location' => $target_location, 'grade' => $role_arr['grade']];
                $arr = ['id' => $id, 'location' => $current_position];
                $this->ArrayModel->update_data($where, $arr);
            }
            else
            {
                // 合并 判断是否刷新等级
                $ret = $this->ArrayModel->del_array_info(['id' => $id, 'location' => $current_position]);
                if ($ret)
                {
                    $role_obj = $this->RoleModel->get_role_info(['grade' => $role_arr['grade']]);
                    $role_arr = $this->CustomPage->objectToArray($role_obj);
                    if (!empty($role_arr))
                    {
                        $fid = $role_arr['fid'];
                        $user_info = $this->UserModel->get_user_info(['id'=>$id]);
                        $user_arr = $this->CustomPage->objectToArray($user_info);
                        if (!empty($user_arr))
                        {
                            // 金币产出更新结算
                            $now_time = time();
                            $out_gold = ($now_time - $user_arr['update_time']) * $user_arr['output'];
                            $where_gold['id'] = $id;
                            $filed = 'gold';
                            $this->UserModel->increment_data($where_gold, $filed, $out_gold);
                            $filed_time = ['update_time' => $now_time];
                            $this->UserModel->update_data($where_gold, $filed_time);
                            
                            if ($fid > $user_arr['grade'])
                            {
                                // 更新段位
                                $where_user['id'] = $id;
                                $arr_user = ['grade' => $fid];
                                $this->UserModel->update_data($where_user, $arr_user);
                                // 更新商店
                                $basics_gold = $role_arr['basics_gold'];
                                $basics_diamond = $role_arr['basics_diamond'];
                                $role_info = ['id' => $id, 'grade' => $fid, 'gold' => $basics_gold, 'diamond' => $basics_diamond];
                                $this->StoreModel->insert_store_info($role_info);
                            }

                            $role_obj_2 = $this->RoleModel->get_role_info(['grade' => $fid]);
                            $role_arr_2 = $this->CustomPage->objectToArray($role_obj_2);
                            if (!empty($role_arr_2))
                            {
                                $where = ['id' => $id, 'location' => $target_location];
                                $arr = ['grade' => $fid, 'output' => $role_arr_2['output']];
                                $this->ArrayModel->update_data($where, $arr);
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

                            $where_id['id'] = $id;
                            $arr_out = ['output' => $output_money];
                            $this->UserModel->update_data($where_id, $arr_out);
                        }
                    }
                }
            }
        }
        else 
        {
            // 目标位置为空 直接替换位置
            $where = ['id' => $id, 'location' => $current_position];
            $arr = ['id' => $id, 'location' => $target_location];
            $this->ArrayModel->update_data($where, $arr);
        }

        // 阵容
        $array_obj = $this->ArrayModel->get_array_info(['id' => $id]);
        $result['array'] = $this->CustomPage->objectToArray($array_obj->toArray());

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
}
