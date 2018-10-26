<?php
/**
 * Created by PhpStorm.
 * User: Josen
 * Date: 2018/8/16
 * Time: 14:25
 */

namespace payment;


class AliPay
{
    private $file = '/ali/src';

    private $config = [
        //应用ID,您的APPID。
        'app_id'               => "",
        //商户私钥
        'merchant_private_key' => "",
        //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
        'alipay_public_key'    => "",
        //编码格式
        'charset'              => "UTF-8",
        //签名方式
        'sign_type'            => "RSA2",
        //支付宝网关
        'gatewayUrl'           => "https://openapi.alipay.com/gateway.do",
    ];

    public function __construct ( $appID = NULL, $privateKey = NULL, $aliPayPublicKey = NULL )
    {
        if ( !empty($appID) ) {
            $this->config['app_id'] = $appID;
        }

        if ( !empty($privateKey) ) {
            $this->config['merchant_private_key'] = $privateKey;
        }

        if ( !empty($aliPayPublicKey) ) {
            $this->config['alipay_public_key'] = $aliPayPublicKey;
        }
    }

    /**
     * 统一收单下单并支付页面接口
     * @param $aOrderData
     * @throws \Exception
     */
    public function webPay ( $aOrderData, $returnUrl, $notifyUrl )
    {
        /*
        * 微信必填参数
        * $data = [
           'out_trade_no' => '商户订单号',
           'subject' => '标题',
           'total_amount' => '金额',
       ];*/
        require_once dirname(__FILE__) . $this->file . '/pagepay/service/AlipayTradeService.php';
        require_once dirname(__FILE__) . $this->file . '/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php';

        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder();
        $payRequestBuilder->setSubject(trim($aOrderData['subject']));
        $payRequestBuilder->setOutTradeNo(trim($aOrderData['out_trade_no']));
        $payRequestBuilder->setTotalAmount(trim($aOrderData['total_amount']));
        $payRequestBuilder->setPassbackParams(isset($aOrderData['passback_params']) ? urlencode($aOrderData['passback_params']) : '');
        $aop = new \AlipayTradeService($this->config);

        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */
        $response = $aop->pagePay($payRequestBuilder, $returnUrl, $notifyUrl);
        return $response;
    }


    /**
     * app支付
     * @param $aOrderData
     * @param $notifyUrl
     * @return string
     */
    public function appPay ( $aOrderData, $notifyUrl )
    {
        require_once( dirname(__FILE__) . $this->file . '/aop/AopClient.php' );
        require_once( dirname(__FILE__) . $this->file . '/aop/request/AlipayTradeAppPayRequest.php' );

        $aop = new \AopClient();

        //**沙箱测试支付宝开始
        $aop->gatewayUrl = $this->config['gatewayUrl'];

        //实际上线app id需真实的
        $aop->appId              = $this->config['app_id']; //开发者appid
        $aop->rsaPrivateKey      = $this->config['merchant_private_key']; //填写工具生成的商户应用私钥
        $aop->format             = "json";
        $aop->charset            = $this->config['charset'];
        $aop->signType           = $this->config['sign_type'];
        $aop->alipayrsaPublicKey = $this->config['alipay_public_key']; //填写从支付宝开放后台查看的支付宝公钥

        $bizcontent = json_encode([
            'body'                 => isset($aOrderData['body']) ? $aOrderData['body'] : '',
            'subject'              => $aOrderData['subject'],
            'out_trade_no'         => $aOrderData['out_trade_no'],// 此订单号为商户唯一订单号
            'total_amount'         => $aOrderData['total_amount'],// 保留两位小数
            'disable_pay_channels' => isset($aOrderData['disable_pay_channels']) ? $aOrderData['disable_pay_channels'] : '',// 禁用支付方式
            'product_code'         => 'QUICK_MSECURITY_PAY',
            'passback_params'      => isset($aOrderData['passback_params']) ? urlencode($aOrderData['passback_params']) : '',
        ]);

        //**沙箱测试支付宝结束
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();
        //var_dump($request);die;
        //支付宝回调
        $request->setNotifyUrl($notifyUrl);
        $request->setBizContent($bizcontent);

        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
        return $response;
    }

    /**
     * 线下交易查询接口
     * @param $orderNumber
     * @return bool|mixed|\SimpleXMLElement|string|\提交表单HTML文本
     * @throws \Exception
     */
    public function webQuery ( $orderNumber )
    {
        require_once dirname(__FILE__) . $this->file . '/pagepay/service/AlipayTradeService.php';
        require_once dirname(__FILE__) . $this->file . '/pagepay/buildermodel/AlipayTradeQueryContentBuilder.php';

        //构造参数
        $RequestBuilder = new \AlipayTradeQueryContentBuilder();

        //商户订单号，商户网站订单系统中唯一订单号
        $RequestBuilder->setOutTradeNo(trim($orderNumber));

        $aop = new \AlipayTradeService($this->config);

        /**
         * alipay.trade.query (统一收单线下交易查询)
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @return $response 支付宝返回的信息
         */
        $response = $aop->Query($RequestBuilder);

        return $response;
    }

    /**
     * 统一收单交易退款接口
     * @param $OrderData
     * @return bool|mixed|\SimpleXMLElement|string|\提交表单HTML文本
     * @throws \Exception
     */
    public function refund ( $OrderData )
    {
        /*$data = [
            'out_trade_no' => '商户订单号',
            'refund_amount' => '退款金额',
            'refund_reason' => '退款原因 可选',
            'out_request_no' => '退款标识，查询退款需要用',
        ];*/
        require_once dirname(__FILE__) . $this->file . '/pagepay/service/AlipayTradeService.php';
        require_once dirname(__FILE__) . $this->file . '/pagepay/buildermodel/AlipayTradeRefundContentBuilder.php';

        //构造参数
        $RequestBuilder = new \AlipayTradeRefundContentBuilder();

        //商户订单号，商户网站订单系统中唯一订单号
        $RequestBuilder->setOutTradeNo(trim($OrderData['out_trade_no']));
        //支付宝交易号
        //$RequestBuilder->setTradeNo($trade_no);
        //需要退款的金额，该金额不能大于订单金额，必填
        $RequestBuilder->setRefundAmount(trim($OrderData['refund_amount']));
        //退款的原因说明
        $RequestBuilder->setOutRequestNo(trim($OrderData['refund_reason']));
        //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
        $RequestBuilder->setRefundReason(trim($OrderData['out_request_no']));

        $aop = new \AlipayTradeService($this->config);

        /**
         * alipay.trade.refund (统一收单交易退款接口)
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @return $response 支付宝返回的信息
         */
        $response = $aop->Refund($RequestBuilder);
        return $response;
    }

    /**
     * 统一收单交易退款查询接口
     * @param $OrderData
     * @return bool|mixed|\SimpleXMLElement|string|\提交表单HTML文本
     * @throws \Exception
     */
    public function queryFefund ( $OrderData )
    {
        /*$dataFQ = [
            'out_trade_no' => '商户订单号',
            'trade_no' => '支付宝交易号',
            'out_request_no' => '退款时填写的退款标识',
        ];*/
        require_once dirname(__FILE__) . $this->file . '/pagepay/service/AlipayTradeService.php';
        require_once dirname(__FILE__) . $this->file . '/pagepay/buildermodel/AlipayTradeFastpayRefundQueryContentBuilder.php';

        //构造参数
        $RequestBuilder = new \AlipayTradeFastpayRefundQueryContentBuilder();
        //商户订单号，商户网站订单系统中唯一订单号
        $RequestBuilder->setOutTradeNo(trim($OrderData['outTradeNo']));
        //支付宝交易号
        $RequestBuilder->setTradeNo(trim($OrderData['tradeNo']));
        //请求退款接口时，传入的退款请求号，如果在退款请求时未传入，则该值为创建交易时的外部交易号，必填
        $RequestBuilder->setOutRequestNo(trim($OrderData['outRequestNo']));

        $aop = new \AlipayTradeService($this->config);

        /**
         * 退款查询   alipay.trade.fastpay.refund.query (统一收单交易退款查询)
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @return $response 支付宝返回的信息
         */
        $response = $aop->refundQuery($RequestBuilder);
        return $response;
    }

    /**
     * 交易关闭
     * @param $orderNumber 商户订单号
     * @return bool|mixed|\SimpleXMLElement|string|\提交表单HTML文本
     * @throws \Exception
     */
    public function close ( $orderNumber )
    {
        require_once dirname(__FILE__) . $this->file . '/pagepay/service/AlipayTradeService.php';
        require_once dirname(__FILE__) . $this->file . '/pagepay/buildermodel/AlipayTradeCloseContentBuilder.php';

        //构造参数
        $RequestBuilder = new \AlipayTradeCloseContentBuilder();
        //商户订单号，商户网站订单系统中唯一订单号
        $RequestBuilder->setOutTradeNo(trim($orderNumber));

        $aop = new \AlipayTradeService($this->config);

        /**
         * alipay.trade.close (统一收单交易关闭接口)
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @return $response 支付宝返回的信息
         */
        $response = $aop->Close($RequestBuilder);
        return $response;
    }

    /**
     * 验签
     * @param $params
     * @return bool
     */
    public function callback ( $params )
    {
        require_once( dirname(__FILE__) . $this->file . '/aop/AopClient.php' );
        $aop                     = new \AopClient();
        $aop->alipayrsaPublicKey = $this->config['alipay_public_key'];
        $res                     = $aop->rsaCheckV1($params, NULL, $this->config['sign_type']);
        return $res;
    }
}