<?php

namespace PHPZlc\MarkGitDoc;

class Unit
{
    public static function setClassParams($class, $params)
    {
        if(!empty($params)){
            foreach ($params as $key => $param){
                if($param !== '' || $param !== null) {
                    $class->$key = $param;
                }
            }
        }
    }

    public static function objectToArray($class)
    {
        $array = [];
        foreach (get_object_vars($class) as $key => $value) {
            if(is_object($value)) {
                $array[$key] = self::objectToArray($value);
            }else{
                $array[$key] = $value;
            }
        }

        return $array;
    }

    public static function removeRemoveString($substring,$str){
        if (substr($str,-strlen($substring))===$substring){
            $str = substr($str, 0, strlen($str)-strlen($substring));
        }
        
        return $str;
    }
}