<?php
/**
 * Created by PhpStorm.
 * User: 周军
 * Date: 2018/8/10
 * Time: 13:42
 */

namespace library\validate;

include_once (dirname(dirname(__FILE__)).'/validate/BaseValidate.php');
class PayValidate extends BaseValidate
{
    protected $rule = [
        'appID'           => 'require',
        'privateKey'      => 'require',
        'aliPayPublicKey' => 'require',
        'merchantID'      => 'require',
        'cert'            => 'require',
        'key'             => 'require',
        'subject'         => 'require',
        'out_trade_no'    => 'require',
        'total_amount'    => 'require',
        'notifyUrl'       => 'require',
        'body'            => 'require',
        'total_fee'       => 'require',
        'openID'          => 'require',

    ];

    protected $message = [
        'appID.require'           => 'appID不能为空',
        'privateKey.require'      => '商户私钥不能为空',
        'aliPayPublicKey.require' => '支付宝公钥不能为空',
        'merchantID.require'      => '微信商户号不能为空',
        'cert.require'            => '微信证书cert路径不能为空',
        'key.require'             => '微信证书key路径不能为空',
        'subject.require'         => '订单标题不能为空',
        'out_trade_no.require'    => '订单号不能为空',
        'total_amount.require'    => '订单金额不能为空',
        'notifyUrl.require'       => '异步回调地址不能为空',
        'body.require'            => '订单描述不能为空',
        'total_fee.require'       => '订单金额不能为空',
        'openID.require'          => 'openID不能为空',


    ];

    protected $scene = [
        'aliSet' => [ 'appID', 'privateKey', 'aliPayPublicKey' ],
        'aliPay' => [ 'subject', 'out_trade_no', 'total_amount', 'notifyUrl' ],
        //'wxSet'  => [ 'appID', 'privateKey', 'merchantID', 'cert', 'key' ],
        'wxSet'  => [ 'appID', 'privateKey', 'merchantID' ],
        'wxPay'  => [ 'body', 'out_trade_no', 'total_fee' ],
    ];
}
