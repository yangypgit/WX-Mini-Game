<?php

namespace App\Models;

class ArrayModel
{
    private static $instance = null;

    private function __construct() {}

    private function __clone() {}

    static public function getInstance()
    {
        if (is_null ( self::$instance ) || isset ( self::$instance )) 
        {
            self::$instance = new self ();
        }

        return self::$instance;
    }

    // 获取阵容信息
    public function get_array_info($where='')
    {
        try
        {
            $tab_ticket = \DB::table('king_array');
            // $ret_value = $tab_ticket->where($where)->first();
            $ret_value = $tab_ticket->where($where)->select('location', 'grade', 'output')->get();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 插入一条数据
    public function insert_array_info($data=[])
    {
        try
        {
            $tab_ticket = \DB::table('king_array');
            $res = $tab_ticket->insert($data);
            return $res;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 删除一条数据
    public function del_array_info($where='')
    {
        try
        {
            $tab_ticket = \DB::table('king_array');
            $ret_value = $tab_ticket->where($where)->delete();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 获取角色位置信息
    public function get_role_location($where='')
    {
        try
        {
            $tab_ticket = \DB::table('king_array');
            $ret_value = $tab_ticket->where($where)->select('location', 'grade', 'output')->first();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 更新数据库
    public function update_data($where='',$data=[])
    {
        try 
        {
            $tab_ticket = \DB::table('king_array');
            $res = $tab_ticket->where($where)->update($data);
            return $res;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
