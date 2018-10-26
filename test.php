<?php
/**
 * Created by PhpStorm.
 * User: Josen
 * Date: 2018/10/26
 * Time: 9:48
 */
ini_set('date.timezone', 'Asia/Shanghai');
include_once( dirname(__FILE__) . '/PayLogic.php' );
include_once( dirname(__FILE__) . '/validate/PayValidate.php' );

//使用示例
$pay = new PayLogic();

//异步通知地址
$notifyUrl = "http://www.jiangnannan.cn/notify.php";
$returnUrl = "http://www.baidu.com";
$payData   = [
    'subject'         => '标题',
    'body'            => '这是一个支付宝网页支付',
    'total_amount'    => 0.01,
    'out_trade_no'    => 'DDH003',
    'passback_params' => '回调时我会回来',
];

//$res = $pay->createAliPay('web', $payData, $notifyUrl, $returnUrl);//支付宝网页支付
//echo $res;
$payData = [
    'body'         => '这是一个支付宝网页支付',
    'total_fee'    => 0.01,
    'out_trade_no' => 'DDH007',
    'attach'       => '标题',
    'product_id'   => time(),
    'time_expire'  => date('YmdHis', time() + 600),
];

//$res = $pay->createWxPay('web', $payData, $notifyUrl);//微信扫码支付
//echo $res;