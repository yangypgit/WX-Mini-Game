<?php

namespace App\Models;

/*
 *  每日奖励表 和 签到表 放在这同一个类中处理
 */

class CheckInTableModel
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

    // 获取每日奖励表信息
    public function get_daily_bonus($where='')
    {
        try
        {
            $tab_ticket = \DB::table('king_daily_bonus');
            $ret_value = $tab_ticket->where($where)->first();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 获取签到表信息
    public function get_check_info($where='')
    {
        try
        {
            $tab_ticket = \DB::table('king_check-in_table');
            $ret_value = $tab_ticket->where($where)->first();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 插入一条数据
    public function insert_info($data=[])
    {
        try
        {
            $tab_ticket = \DB::table('king_check-in_table');
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
            $tab_ticket = \DB::table('king_check-in_table');
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
            $res_ticket = \DB::table('king_check-in_table')->where($where)->increment($filed, $num);
            return $res_ticket;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
