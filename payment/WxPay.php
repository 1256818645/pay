<?php
/**
 * Created by PhpStorm.
 * User: Josen
 * Date: 2018/8/20
 * Time: 9:45
 */

namespace payment;


use library\Exception;
use library\payment\wx\AppNotify;

class WxPay
{
    const JSPAY = 'js';
    const APPPAY = 'app';

    private $file = '/wx/src/';

    private $config;

    public function __construct ( $appID = NULL, $merchantID = NULL, $privateKey = NULL, $cert = NULL, $key = NULL, $signType = NULL )
    {
        require_once( dirname(__FILE__) . $this->file . 'WxPay.Api.php' );
        require_once( dirname(__FILE__) . $this->file . 'WxPay.Config.php' );
        $this->config = new \WxPayConfig();
        if ( !empty($appID) ) {
            $this->config->appID = $appID;
        }
        if ( !empty($merchantID) ) {
            $this->config->merchantID = $merchantID;
        }
        if ( !empty($privateKey) ) {
            $this->config->privateKey = $privateKey;
        }
        if ( !empty($signType) ) {
            $this->config->signType = $signType;
        }
        if ( !empty($cert) ) {
            $this->config->cret = $cert;
        }
        if ( !empty($key) ) {
            $this->config->key = $key;
        }
    }

    /**
     * 公众号支付
     * @param $orderData
     * @param $notifyUrl
     * @param $openID
     * @return array
     */
    public function jsPay ( $orderData, $notifyUrl, $openID )
    {
        return $this->makeWxPreOrder('JSAPI', $orderData, $notifyUrl, $openID);
    }

    /**
     * APP支付
     * @param $orderData
     * @param $notifyUrl
     * @return mixed
     */
    public function appPay ( $orderData, $notifyUrl )
    {
        return $this->makeWxPreOrder('APP', $orderData, $notifyUrl);
    }

    public function webPay ( $orderData, $notifyUrl )
    {
        return $this->makeWxPreOrder('NATIVE', $orderData, $notifyUrl);
    }

    // 构建微信支付订单信息
    private function makeWxPreOrder ( $payType, $orderData, $notifyUrl, $openID = NULL )
    {
        $wxOrderData = new \WxPayUnifiedOrder();
        $wxOrderData->SetBody($orderData['body']);
        $wxOrderData->SetAttach(isset($orderData['attach']) ? $orderData['attach'] : '');
        $wxOrderData->SetOut_trade_no($orderData['out_trade_no']);
        $wxOrderData->SetTotal_fee($orderData['total_fee'] * 100);
        $wxOrderData->SetTime_start(date("YmdHis"));
        $wxOrderData->SetTime_expire(date("YmdHis", time() + 600));
        $wxOrderData->SetGoods_tag(isset($orderData['goods_tag']) ? $orderData['goods_tag'] : '');
        $wxOrderData->SetNotify_url($notifyUrl);
        $wxOrderData->SetTrade_type($payType);
        $wxOrderData->SetProduct_id(isset($orderData['product_id']) ? $orderData['product_id'] : '');
        if ( !empty($openID) && $payType == 'JSAPI' ) {
            $wxOrderData->SetOpenid($openID);
        }
        switch ( $payType ) {
            case 'JSAPI' :
                return $this->getPaySignature($wxOrderData, self::JSPAY);
            case 'APP' :
                return $this->getPaySignature($wxOrderData, self::APPPAY);
            case 'NATIVE':
                return \WxPayApi::unifiedOrder($this->config, $wxOrderData);
        }
    }

    //向微信请求订单号并生成签名
    private function getPaySignature ( $wxOrderData, $payType )
    {
        $wxOrder = \WxPayApi::unifiedOrder($this->config, $wxOrderData);

        // 失败时不会返回result_code
        if ( $wxOrder['return_code'] != 'SUCCESS' || $wxOrder['result_code'] != 'SUCCESS' ) {
            throw new Exception('获取预支付订单失败 ' . $wxOrder['return_msg']);
        }

        //存储prepay_id
        $action    = $payType . 'Sign';
        $signature = $this->$action($wxOrder);
        return $signature;
    }

    // 公众号签名
    private function JsSign ( $wxOrder )
    {
        $jsApiPayData = new \WxPayJsApiPay();
        $jsApiPayData->SetAppid($this->config->GetAppId());
        $jsApiPayData->SetTimeStamp((string)time());
        $rand = $this->getNonceStr();
        $jsApiPayData->SetNonceStr($rand);
        $jsApiPayData->SetPackage('prepay_id=' . $wxOrder['prepay_id']);
        $sign              = $jsApiPayData->MakeSign($this->config);
        $rawValues         = $jsApiPayData->GetValues();
        $rawValues['sign'] = $sign;
        unset($rawValues['appId']);
        return $rawValues;
    }

    // APP签名
    private function appSign ( $wxOrder )
    {
        $time         = (string)time();
        $jsApiPayData = new \WxPayJsApiPay();
        $jsApiPayData->SetAppid($this->config->GetAppId());
        $jsApiPayData->SetTimeStamp($time);
        $rand = $this->getNonceStr();
        $jsApiPayData->SetNonceStr($rand);
        $jsApiPayData->SetPackage('Sign=WXPay');

        //二次签名
        $params = [
            'appid'     => $this->config->GetAppId(),
            'noncestr'  => $rand,
            'package'   => 'Sign=WXPay',
            'partnerid' => $this->config->GetMerchantId(),
            'timestamp' => $time,
            'prepayid'  => $wxOrder['prepay_id'],
        ];
        $sign   = $this->getSign($params);

        $rawValues              = $jsApiPayData->GetValues();
        $rawValues['sign']      = $sign;
        $rawValues['partnerid'] = $this->config->GetMerchantId();
        $rawValues['prepayid']  = $wxOrder['prepay_id'];
        return $rawValues;
    }

    /**
     * 获取参数签名；
     * @param  Array  要传递的参数数组
     * @return String 通过计算得到的签名；
     */
    private function getSign ( $params )
    {
        ksort($params);        //将参数数组按照参数名ASCII码从小到大排序

        $newArr = [];
        foreach ( $params as $key => $item ) {
            if ( !empty($item) ) {         //剔除参数值为空的参数
                $newArr[] = $key . '=' . $item;     // 整合新的参数数组
            }
        }

        $stringA = implode("&", $newArr);         //使用 & 符号连接参数

        $stringSignTemp = $stringA . "&key=" . $this->config->GetKey();        //拼接key

        //MD5加密或者HMAC-SHA256
        if ( $this->config->GetSignType() == "MD5" ) {
            $stringSignTemp = md5($stringSignTemp);
        } else if ( $this->config->GetSignType() == "HMAC-SHA256" ) {
            $stringSignTemp = hash_hmac("sha256", $stringSignTemp, $this->config->GetKey());
        }

        // key是在商户平台API安全里自己设置的
        $sign = strtoupper($stringSignTemp);      //将所有字符转换为大写
        return $sign;
    }


    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public function getNonceStr ( $length = 32 )
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str   = "";
        for ( $i = 0; $i < $length; $i++ ) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 查询订单
     * @param $out_trade_no
     * @return \成功时返回，其他抛异常
     * @throws Exception
     */
    public function getOrder ( $out_trade_no )
    {
        $wxOrderData = new \WxPayOrderQuery();

        $wxOrderData->SetOut_trade_no(trim($out_trade_no));

        $wxOrder = \WxPayApi::orderQuery($this->config, $wxOrderData);

        // 失败时不会返回result_code
        if ( $wxOrder['return_code'] != 'SUCCESS' || $wxOrder['result_code'] != 'SUCCESS' ) {
            throw new Exception('查询订单失败');
        }
        return $wxOrder;
    }

    /**
     * 申请退款
     * @param $refundData
     * @return \成功时返回，其他抛异常
     * @throws Exception
     */
    public function refund ( $refundData )
    {
        $wxRefundData = new \WxPayRefund();
        $wxRefundData->SetOut_trade_no(trim($refundData['out_trade_no']));
        $wxRefundData->SetOut_refund_no(trim($refundData['out_refund_no']));
        $wxRefundData->SetTotal_fee($refundData['total_fee'] * 100);
        $wxRefundData->SetRefund_fee($refundData['refund_fee'] * 100);
        $wxRefundData->SetOp_user_id($this->config->GetMerchantId());

        $res = \WxPayApi::refund($this->config, $wxRefundData);
        // 失败时不会返回result_code
        if ( $res['return_code'] != 'SUCCESS' || $res['result_code'] != 'SUCCESS' ) {
            throw new Exception('退款异常');
        }
        return $res;
    }

    /**
     * 退款查询
     * @param $out_trade_no
     * @return \成功时返回，其他抛异常
     * @throws Exception
     */
    public function queryRefund ( $out_trade_no )
    {
        $refundData = new \WxPayRefundQuery();
        $refundData->SetOut_trade_no(trim($out_trade_no));

        $res = \WxPayApi::refundQuery($this->config, $refundData);
        // 失败时不会返回result_code
        if ( $res['return_code'] != 'SUCCESS' || $res['result_code'] != 'SUCCESS' ) {
            throw new Exception('退款查询异常');
        }
        return $res;
    }

    /**
     * 关闭交易订单
     * @param $out_trade_no
     * @return \成功时返回，其他抛异常
     * @throws Exception
     */
    public function close ( $out_trade_no )
    {
        $closeData = new \WxPayCloseOrder();
        $closeData->SetOut_trade_no(trim($out_trade_no));

        $res = \WxPayApi::closeOrder($this->config, $closeData);
        // 失败时不会返回result_code
        if ( $res['return_code'] != 'SUCCESS' || $res['result_code'] != 'SUCCESS' ) {
            throw new Exception('关闭订单异常');
        }
        return $res;
    }

    /**
     * 验签
     * @return string
     */
    public function checkSign ( $payType )
    {
        $notify = new \library\payment\wx\extend\Notify();
        if ( $payType == 'APP' ) {
            $openPay = new self(WX_APP_APPID, WX_APP_MERCHANTID, WX_APP_PRIVATEKEY);
            $res     = $notify->checkCallback($openPay->config);
        } else {
            $res = $notify->checkCallback($this->config);
        }
        return $res;
    }
}