<?php

namespace App\Models;

class StoreModel
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

    // 获取用户商店信息
    public function get_store_info($where='',$between=[],$filed='')
    {
        try
        {
            $tab_ticket = \DB::table('king_store');
            $sql = $tab_ticket->where($where);
            if(!empty($between) && !empty($filed))
            {
                $sql = $sql->whereBetween($filed,$between);
            }
            $ret_value = $sql->select('grade', 'gold', 'diamond')->get();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 获取角色对应的金币和钻石
    public function get_role_info($where='')
    {
        try
        {
            $tab_ticket = \DB::table('king_store');
            $ret_value = $tab_ticket->where($where)->select('gold', 'diamond')->first();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 插入一条数据
    public function insert_store_info($data=[])
    {
        try
        {
            $tab_ticket = \DB::table('king_store');
            $res = $tab_ticket->insert($data);
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
            $res_ticket = \DB::table('king_store')->where($where)->increment($filed, $num);
            return $res_ticket;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
