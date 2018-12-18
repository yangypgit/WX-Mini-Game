<?php

namespace App\Models;

class InvitationListModel
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

    // 插入一条数据
    public function insert_info($data=[])
    {
        try
        {
            $tab_ticket = \DB::table('king_invitation_list');
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
            $tab_ticket = \DB::table('king_invitation_list');
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
            $res_ticket = \DB::table('king_invitation_list')->where($where)->increment($filed, $num);
            return $res_ticket;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 获取邀请好友列表
    public function get_invitation_list_tab($where='')
    {
        try
        {
            $tab_ticket = \DB::table('king_invitation_list');
            $ret_value = $tab_ticket->where($where)->get();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 获取邀请好友列表中一条信息
    public function get_invitation_info($where='')
    {
        try
        {
            $tab_ticket = \DB::table('king_invitation_list');
            $ret_value = $tab_ticket->where($where)->first();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }

    // 获取number的最大值
    public function get_max_val($where = '', $param)
    {
        try
        {
            $tab_ticket = \DB::table('king_invitation_list');
            $ret_value = $tab_ticket->where($where)->max($param);
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
