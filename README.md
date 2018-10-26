# pay
集成支付宝、微信常用的支付方式

####使用说明
 > 1、核心文件就是Payment文件夹，其中AliPay.php、WxPay.php是封装的主要文件
 
 > 2、支付发起入口是PayLogic.php
 
 > 3、目前集成的支付方式有：
 * 微信APP支付
 * 微信扫码支付
 * 微信公众号支付
 * 支付宝APP支付
 * 支付宝电脑支付
 
 > 4、支付配置
 * 支付宝支付配置是写在AliPay.php中的$config属性中
 * 微信支付配置是写在WxPay.Config.php文件中
 * 为了适配多账号支付，在入口PayLogic.php类中，加入了setWxPaySetting、setAliPaySetting
 两个方法，目的是为了可以动态的更改需要的配置，具体使用请自行查看
 
 > 5、回调验签AliPay.php、WxPay.php分别对应callback、checkSign方法

 > 6、test.php 是使用的简单demo

        目前所有功能都已经调试通过，只需要自己书写回调处理即可，欢迎大神添砖加瓦