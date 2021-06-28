<?php
namespace app\common\extend\pay;

class Funpay {

    public $name = '乐付支付';
    public $ver = '1.0';

    public function submit($user,$order,$param)
    {
        $pay_type = 1;
        if(!empty($param['paytype'])){
            $pay_type = intval($param['paytype']);
        }
        // 参与签名字段
        $base_data = [
            'pay_memberid'    => trim($GLOBALS['config']['pay']['funpay']['appid']),
            'pay_orderid'     => $order['order_code'],
            'pay_amount'      => $order['order_price'],
            'pay_applydate'   => date('Y-m-d H:i:s'),
            'pay_bankcode'    => [1 => 901, 2 => 904][$pay_type] ?? $pay_type,//1微信 2支付宝
            'pay_notifyurl'   => $GLOBALS['http_type'] . $_SERVER['HTTP_HOST'] . '/payment/notify/pay_type/funpay',
            'pay_callbackurl' => '',
        ];
        $post_data = $base_data + [
            'pay_md5sign'     => '',
            'pay_attach'      => '',
            'pay_productname' => 'vip',
            'pay_productnum'  => 1,
            'pay_productdesc' => '',
            'pay_producturl'  => '',
        ];
        ksort($base_data);
        $base_data = array_filter($base_data);
        $sign_string = urldecode(http_build_query($base_data)) . '&key=' . trim($GLOBALS['config']['pay']['funpay']['appkey']);
        $post_data['pay_md5sign'] = strtoupper(md5($sign_string));
        $gateway_url = 'https://gateway.fun-pays.com/Pay/index';
        // 构造表单返回
        $response = '<html><head></head><body><form id="Form1" name="Form1" method="post" action="' . $gateway_url . '">';
        foreach ($post_data as $key => $value) {
            $response .= ('<input type="hidden" name="' . $key . '" value="' . $value . '" />');
        }
        $response .= '</form><script>document.Form1.submit();</script></body></html>';
        @header('Content-type: text/html; charset=utf-8');
        echo $response;

    }

    public function notify()
    {
        $params = $_POST;

        $order_code = $params['orderid'] ?? $_REQUEST['orderid'];
        $where_order = ['order_code' => $order_code];
        $order = model('Order')->infoData($where_order);
        if ($order['code'] != 1) {
            exit('error: order not exists. order node: ' . $order_code);
        }
        $remote_sign = $params['sign'];
        unset($params['sign'], $params['attach']);
        ksort($params);
        $params = array_filter($params);
        $GLOBALS['config']['pay'] = config('maccms.pay');
        $merchant_key = trim($GLOBALS['config']['pay']['funpay']['appkey']);
        $sign_string = urldecode(http_build_query($params)) . '&key=' . $merchant_key;
        if (strtoupper(md5($sign_string)) != $remote_sign) {
            echo "数据验证失败";
            return;
        }
        $res = model('Order')->notify($order_code, 'funpay');
        if ($res['code'] > 1) {
            echo 'fail2';
        } else {
            echo 'success';
        }
    }
}
