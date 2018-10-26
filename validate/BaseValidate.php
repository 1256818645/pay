<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018\1\26 0026
 * Time: 16:17
 */

namespace library\validate;

include_once (dirname(dirname(__FILE__)).'/validate/Validate.php');
class BaseValidate extends Validate
{
    /**
     * 统一验证方法
     * @param bool $data 表单数组
     * @param bool $option ture返回多条错误数组 false返回数组第一条错误信息
     * @param null $scene 验证场景
     */
    public function goCheck ( $data, $option = true, $scene = NULL )
    {
        if ( is_string($data) ) {
            $this->jsonOutput(0, '数据格式错误,请检查!');
        }

        if ( is_object($data) ) {
            $data = (array)$data;
        }

        if ( !empty($scene) ) {
            $result = $this->batch()->scene($scene)->check($data);
        } else {
            $result = $this->batch()->check($data);
        }

        if ( !$result ) {
            if ( $option ) {
                $this->jsonOutput(0, $this->error, [ "msg" => $this->error ]);
            } else {
                $this->jsonOutput(0, reset($this->error), [ "msg" => $this->error ]);
            }
        }
    }

    //判断字符串中是否有某个字符
    protected function checkStrpos ( $value, $rule = '' )
    {
        if ( empty($rule) ) {
            return false;
        }
        if ( !strpos($value, '-') ) {
            return false;
        }
        return true;
    }

    #验证手机号码
    protected function checkMobile ( $value )
    {
        $exp = '/^1[3456789]\d{9}$/';
        return ( preg_match($exp, $value) > 0 ) ? true : false;
    }
}