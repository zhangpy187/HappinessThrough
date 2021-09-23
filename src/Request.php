<?php

namespace RongChuang\HappinessThrough;

use GuzzleHttp\Client;

class Request
{
    /**
     * 开发地址
     * @var string
     */
    protected $dev = 'https://esbqas.sunac.com.cn:8442/WP_SUNAC/APP_FACESR_SERVICES/Proxy_Services/TA_SALEAPP2/FACESR_E667_clueSyncToFaceSR_PS';
    /**
     * 生产环境地址
     * @var string
     */
    protected $prod = 'https://esb.sunac.com.cn:8443/WP_SUNAC/APP_FACESR_SERVICES/Proxy_Services/TA_SALEAPP2/FACESR_E667_clueSyncToFaceSR_PS';

    /**
     * 启用环境
     * @var string
     */
    protected $flag = 'prod';
    protected $client;
    protected $uri = '';
    protected $headers = [];
    protected $reqsrcsys = 'BS-FACESR-Q';
    protected $reqtarsys = 'BS-SALEAPP2-Q';
    protected $userName = 'ESB_Q_FACESR';
    protected $password = 'facesr@esb';

    public function __construct(string $flag = 'prod')
    {
        $this->flag = $flag;
        $this->uri = $this->dev;
        $this->headers = ["Authorization:Basic " . base64_encode($this->userName . ':' . $this->password), "Content-Type:text/plain"];
        if ($this->flag == 'prod') {
            $this->uri = $this->prod;
            $this->userName = 'ESB_P_FACESR';
            $this->password = 'facesrp@esb';
            $this->headers = ["Authorization:Basic " . base64_encode($this->userName . ':' . $this->password), "Content-Type:text/plain"];
            $this->reqsrcsys = 'BS-FACESR-P';
            $this->reqtarsys = 'BS-SALEAPP2-P';
        }
        $this->client = new Client();

    }

    public function getDealImportDatas(string $idCard, string $pcode)
    {
        $nonce = time() . "." . rand(0000, 9999);
        $time = date("YmdHis", time());
        $paylod = [
            "auth" => [$this->userName, $this->password],
            "json" => [
                "I_REQUEST" => [
                    "REQ_BASEINFO" => [
                        "REQ_TRACE_ID" => $nonce,//发送方的消息唯一标志
                        "REQ_SEND_TIME" => $time,//报文发送时间
                        "REQ_SRC_SYS" => $this->reqsrcsys,//发送方系统标识
                        "REQ_TAR_SYS" => $this->reqtarsys,//接收方系统标识
                        "REQ_SERVER_NAME" => "FACESR_E667_clueSyncToFaceSR",//接收方服务器名称，由ESB方提供
                        "REQ_SYN_FLAG" => "S",//0为非异步1为异步？
                        "REQ_BSN_ID" => 10000001,//业务数据ID？
                        "REQ_RETRY_TIMES" => 1,//重试次数？
                        "REQ_REPEAT_FLAG" => 1,//是否为重复报文？
                        "REQ_REPEAT_CYCLE" => 60,//重复周期？
                        "BIZTRANSACTIONID" => "FACESR_E667_" . $time . "000_5413",//经过ESB平台的必填，调用次服务的系统按照事务ID生成规则自动生成
                        "COUNT" => 100,//发送的数据条数？
                    ],
                    "MESSAGE" => [
                        "COMMENT" => "业务报文体",
                        "REQ_ITEM" => [
                            "PRO_ID" => $pcode,//是否写死？
                            "CARD_TYPE" => 1,//写死
                            "CARD_NO" => base64_encode($idCard),//base64 转义后的证件号码
                        ],
                    ],
                ],
            ],
        ];
        $response = $this->client->post($this->uri, $paylod)->getBody()->getContents();
        $response = json_decode($response, true);
        var_dump($response);
        if (!isset($response['E_RESPONSE']['RSP_BASEINFO']['RSP_STATUS_MSG'])) {
            return ['code' => 201, 'message' => '请求失败'];
        }
        if ($response['E_RESPONSE']['RSP_BASEINFO']['RSP_STATUS_MSG'] != '处理成功'){
            return ['code' => 201, 'message' => '请求失败', 'data' => $response['E_RESPONSE']['RSP_BASEINFO']['RSP_STATUS_MSG']];
        }
        if (empty($response['E_RESPONSE']['MESSAGE']['RSP_ITEM']['APIRESPRESUL_TESB']['DATA'])){
            return [];
        }
        $data = $response['E_RESPONSE']['MESSAGE']['RSP_ITEM']['APIRESPRESUL_TESB']['DATA'];
        foreach ($data as $key=>$val){
            $reords[$key]['order_sn'] = $val['HOUSE_NAME'];
            $reords[$key]['order_status'] = 0;
            $reords[$key]['name'] = $val['CUS_NAME'];
            $reords[$key]['card'] = $idCard;
            $reords[$key]['adviser_name'] = $val['CONSULTANT_NAME'];
            $reords[$key]['signing_time'] = substr($val['TRADE_DATE'], 0, 4) . '-' . substr($val['TRADE_DATE'], 4, 2) . '-' . substr($val['TRADE_DATE'], -2);
            $reords[$key]['offer_buy_time'] = substr($val['RG_DATE'], 0, 4) . '-' . substr($val['RG_DATE'], 4, 2) . '-' . substr($val['RG_DATE'], -2);
            $reords[$key]['customer_status'] = $val['CUS_STATE'];
            $reords[$key]['chips_time'] = '';
        }
        return $reords;
    }
}