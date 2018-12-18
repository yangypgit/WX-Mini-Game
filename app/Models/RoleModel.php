<?php

namespace App\Models;

class RoleModel
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

    // 获取角色信息
    public function get_role_info($where='')
    {
        try
        {
            $tab_ticket = \DB::table('king_role');
            $ret_value = $tab_ticket->where($where)->first();
            return $ret_value;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
