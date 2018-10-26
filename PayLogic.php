<?php
/**
 * Created by PhpStorm.
 * User: Josen
 * Date: 2018/8/30
 * Time: 18:00
 */
include_once( dirname(__FILE__) . '/payment/AliPay.php' );
include_once( dirname(__FILE__) . '/payment/WxPay.php' );

use payment\WxPay;
use payment\AliPay;
use library\validate\PayValidate;

class PayLogic
{
    //支付类型
    const PAY_TYPE_APP = 'app';
    const PAY_TYPE_WEB = 'web';
    const PAY_TYPE_JS = 'js';

    private $aliPay;
    private $wxPay;

    /**
     * 设置支付宝支付配置 不调用此方法使用默认配置
     * @param null $appID
     * @param null $privateKey
     * @param null $aliPayPublicKey
     */
    public function setAliPaySetting ( $appID = NULL, $privateKey = NULL, $aliPayPublicKey = NULL )
    {
        $data = [
            'appID'           => $appID,
            'privateKey'      => $privateKey,
            'aliPayPublicKey' => $aliPayPublicKey,
        ];
        //表单验证
        ( new PayValidate() )->goCheck($data, false, 'aliSet');

        $this->aliPay = new AliPay($appID, $privateKey, $aliPayPublicKey);

    }

    /**
     * 发起支付宝支付
     * @param null $payType
     * @param array $orderData
     * @param null $notifyUrl
     * @param null $returnUrl
     * @return mixed|\SimpleXMLElement|string|\提交表单HTML文本
     * @throws \Exception
     */
    public function createAliPay ( $payType = NULL, $orderData = [], $notifyUrl = NULL, $returnUrl = NULL )
    {
        if ( empty($payType) || !in_array($payType, [ self::PAY_TYPE_APP, self::PAY_TYPE_WEB ]) ) {
            $this->jsonOutput(0, '此支付类型暂不支持！');
        }

        $data              = $orderData;
        $data['notifyUrl'] = $notifyUrl;

        //表单验证
        ( new PayValidate() )->goCheck($data, false, 'aliPay');

        if ( empty($this->aliPay) ) {
            $this->aliPay = new AliPay();
        }

        switch ( $payType ) {
            case self::PAY_TYPE_APP:
                $str = $this->aliPay->appPay($orderData, $notifyUrl);
                return $str;
            case self::PAY_TYPE_WEB:
                return $this->aliPay->webPay($orderData, $returnUrl, $notifyUrl);
                break;
        }
    }

    /**
     * 设置微信支付配置 不调用此方法使用默认配置
     * @param null $appID
     * @param null $merchantID 商户号
     * @param null $privateKey 私钥
     * @param null $cert 证书cert路径
     * @param null $key 证书key路径
     */
    public function setWxPaySetting ( $appID = NULL, $merchantID = NULL, $privateKey = NULL, $cert = NULL, $key = NULL )
    {
        $data = [
            'appID'      => $appID,
            'merchantID' => $merchantID,
            'privateKey' => $privateKey,
            'cret'       => $cert,
            'key'        => $key,
        ];
        //表单验证
        ( new PayValidate() )->goCheck($data, false, 'wxSet');

        $this->wxPay = new WxPay($appID, $merchantID, $privateKey, $cert, $key);
    }

    /**
     * 发起微信支付
     * @param null $payType
     * @param array $orderData
     * @param null $notifyUrl
     * @param null $openID
     * @return array|string
     */
    public function createWxPay ( $payType = NULL, $orderData = [], $notifyUrl = NULL, $openID = NULL )
    {
        if ( empty($payType) || !in_array($payType, [ self::PAY_TYPE_APP, self::PAY_TYPE_JS, self::PAY_TYPE_WEB ]) ) {
            $this->jsonOutput(0, '此支付类型暂不支持！');
        }

        $data              = $orderData;
        $data['notifyUrl'] = $notifyUrl;

        //表单验证
        ( new PayValidate() )->goCheck($data, false, 'wxPay');

        if ( empty($this->wxPay) ) {
            $this->wxPay = new WxPay();
        }

        switch ( $payType ) {
            case self::PAY_TYPE_APP:
                return $this->wxPay->appPay($orderData, $notifyUrl);
                break;
            case self::PAY_TYPE_JS:
                if ( empty($openID) ) {
                    $this->jsonOutput(0, 'openID不能为空！');
                }
                return $this->wxPay->jsPay($orderData, $notifyUrl, $openID);
                break;
            case self::PAY_TYPE_WEB:
                if ( !isset($orderData['product_id']) ) {
                    $this->jsonOutput(0, 'product_id不能为空！');
                }
                $res = $this->wxPay->webPay($orderData, $notifyUrl);
                if ( isset($res['result_code']) && $res['result_code'] == 'SUCCESS' && isset($res['return_code']) && $res['return_code'] == 'SUCCESS' ) {
                    $img = $this->getErWeiMa($res['code_url']);
                    return $img;
                } else {
                    $this->jsonOutput(0, $res['err_code_des']);
                }

        }
    }

    public function getErWeiMa ( $payUrl )
    {
        $url = 'http://qr.liantu.com/api.php?text=';
        return $url . urlencode($payUrl);
    }

    public function jsonOutput ( $status, $msg, $data = [] )
    {
        echo json_encode([ 'status' => $status, 'msg' => $msg, 'data' => $data ]);
    }
}