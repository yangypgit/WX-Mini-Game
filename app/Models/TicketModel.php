<?php

namespace App\Models;

class TicketModel
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

    // 获取用户转盘券
    public function get_ticket($where='')
    {
        try
        {
            $tab_ticket = \DB::table('king_ticket');
            $ret_value = $tab_ticket->where($where)->first();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 写数据库
    public function insert_ticket($data=[])
    {
        try
        {
            $tab_ticket = \DB::table('king_ticket');
            $res = $tab_ticket->insert($data);
            return $res;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 更新数据库
    public function update_ticket($where='',$data=[])
    {
        try 
        {
            $tab_ticket = \DB::table('king_ticket');
            $res = $tab_ticket->where($where)->update($data);
            return $res;
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function increment_ticket($where='',$filed='',$num=0)
    {
        try
        {
            $res_ticket = \DB::table('king_ticket')->where($where)->increment($filed, $num);
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
            $res_ticket = \DB::table('king_ticket')->where($where)->decrement($filed, $num);
            return $res_ticket;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
