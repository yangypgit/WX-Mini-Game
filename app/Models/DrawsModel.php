<?php

namespace App\Models;

class DrawsModel
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

/*
    // 写数据库
    public function insert_ticket($data=[])
    {
        try
        {
            $tab_ticket = \DB::table('king_draws');
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
            $tab_ticket = \DB::table('king_draws');
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
            $res_ticket = \DB::table('king_draws')->where($where)->increment($filed, $num);
            return $res_ticket;
        } catch (\Exception $exception) {
            return false;
        }
    }
 */

    // 获取奖品列表信息
    public function get_prize_arr()
    {
        try
        {
            $tab_prize = \DB::table('king_draws')->get();
            return $tab_prize;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
