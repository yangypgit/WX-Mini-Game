<?php

namespace App\Models;

class UserModel
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

    // 获取用户信息
    public function get_user_info($where='')
    {
        try
        {
            $tab_ticket = \DB::table('king_user');
            $ret_value = $tab_ticket->where($where)->first();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 插入一条数据
    public function insert_user_info($data=[])
    {
        try
        {
            $tab_ticket = \DB::table('king_user');
            $res = $tab_ticket->insert($data);
            return $res;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 更新数据库
    public function update_data($where='',$data=[])
    {
        try 
        {
            $tab_ticket = \DB::table('king_user');
            $res = $tab_ticket->where($where)->update($data);
            return $res;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 给int类型字段+num
    public function increment_data($where='',$filed='',$num=0)
    {
        try
        {
            $res_ticket = \DB::table('king_user')->where($where)->increment($filed, $num);
            return $res_ticket;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 给int类型字段-num
    public function decrement_data($where='',$filed='',$num=0)
    {
        try
        {
            $res_ticket = \DB::table('king_user')->where($where)->decrement($filed, $num);
            return $res_ticket;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 获取用户列表信息
    public function get_user_tab()
    {
        try
        {
            $tab_prize = \DB::table('king_user')->get();
            return $tab_prize;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
