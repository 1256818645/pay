<?php
/**
 * Created by PhpStorm.
 * User: Josen
 * Date: 2018/9/4
 * Time: 11:43
 */

namespace library\payment\wx\extend;

require_once( dirname(dirname(__FILE__)) . '/src/WxPay.Notify.php' );

class Notify extends \WxPayNotify
{
    public function checkCallback ( $config )
    {
        return $this->Handle($config,false);
    }

    /**
     * @param WxPayNotifyResults $objData 回调解释出的参数
     * @param WxPayConfigInterface $config
     * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
     * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
     */
    public function NotifyProcess ( $objData, $config, &$msg )
    {
        /*$data = $objData->GetValues();

        //TODO 1、进行参数校验
        if ( !array_key_exists("openid", $data) || !array_key_exists("product_id", $data) ) {
            //$msg = "回调数据异常";
            return false;
        }

        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $result = WxPayNotifyResults::Init($config, $xml);*/

        //TODO 2、进行签名验证
        $checkResult = $objData->CheckSign($config);
        if ( $checkResult == false ) {
            //签名错误
            return false;
        }
        return true;
    }
}