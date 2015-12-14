<?php
/*********************/
/*                   */
/*  Version : 5.1.0  */
/*  Author  : 唐悠悠 */
/*  Time    : 151203 */
/*                   */
/*********************/
// 输入你的自定义TOKEN
define("TOKEN", "abc");
// 输入APPID
define('APPID','abc');
// 输入密钥
define('AppSecrect','abc');
class weixin
{

    public function __construct(){

    }

    private function _accessToken(){
        require_once('class.cacheFile.php');
        $cache = new cache;
        $access_token = $cache->get('access_token');
        if($access_token === NULL){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".APPID."&secret=".AppSecrect;
            $status = $this->_httpRequest($url);
            $status = json_decode($status,true);//将对象转为数组
            $access_token = $status['access_token'];
            $cache->set('access_token',$access_token,7200);
        }
        return $access_token;
    }

    public function control_run(){
        if (!isset($_GET['echostr'])) {
            $this->responseMsg();
        }else{
            $this->valid();
        }
    }


    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }

    /**
     * 获取永久素材
     */
    public function control_getWeixin(){
        $access_token = $this->_accessToken();
        $url  = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=".$access_token;
        $json = '{"type":"news","offset":"0","count":"20"}';
        $result = $this->_httpRequest($url,$json);
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 获取图片永久素材
     */
    public function getImage($media_id){
        $access_token = $this->_accessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=".$access_token;
        $json = '{"media_id":"'.$media_id.'"}';
        $result = $this->_httpRequest($url,$json);
        $name = substr($media_id, -10);
        $filename = $name.'.jpg';
        // 图片保存地址
        $file = fopen($filename, "w");
        fwrite($file, $result);
        fclose($file);
    }

    /**
     * 发送post请求
     * @return [type] [description]
     */
    private function _httpRequest($url,$data = null){
        $curl = curl_init();
        curl_setopt ($curl, CURLOPT_URL,$url );
        curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER,FALSE );
        curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST,FALSE );
        if(!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }



    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 判断消息类型
     * @return 回复消息内容
     */
    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            switch ($RX_TYPE)
            {
                case "text":
                    $resultStr = $this->receiveText($postObj);
                    break;
                case "event":
                    $resultStr = $this->receiveEvent($postObj);
                    break;
                default:
                    $resultStr = "";
                    break;
            }
            echo $resultStr;
        }else {
            echo "";
            exit;
        }
    }

    /**
     * 响应text消息
     * @param  消息对象
     * @return 回复消息内容
     */
    private function receiveText($object)
    {
        $funcFlag = 0;
        $contentStr = "你发送的内容为：".$object->Content;
        //判断是否为关键词
        $resultStr = $this->keyword($object);
        if(!$resultStr){
          $resultStr = $this->transmitText($object, $contentStr, $funcFlag);
        }   
        return $resultStr;
    }

    /**
     * 判断是否为关键字
     * @param  [type] $object [发送的消息对象]
     * @return [type]         [消息或者false]
     */
    private function keyword($object){
        switch ($object->Content) {
            case '20151101':
                $contentStr[] = array("Title" =>"[图文消息]【微友征婚】错过了最佳恋爱时期，我不想再错过你。", 
                        "Description" =>"看看本期【微友征婚】版块都有哪些俊男靓女上榜了，有看上的就赶紧联系ta吧！", 
                        "PicUrl" =>"http://mmbiz.qpic.cn/mmbiz/licuOmgdyQgvORZAZ5iciaMCQoYhRMTE61aImbCyx46cX3zN2lspm9sybxbQ3416icnGsglvvK1b3UrQKeDI3tJgWA/0?wx_fmt=jpeg", 
                        "Url" =>"http://mp.weixin.qq.com/s?__biz=MzA3Mzc5Mjk2OQ==&mid=400228363&idx=1&sn=c623188cc7557d6151424cd98fa1507a&scene=20#rd");
                break;
            case '20151102':
                $contentStr[] = array("Title" =>"【微友征婚】我爱笑，我想找个人让我一直笑，陪我一起笑。", 
                        "Description" =>"看看本期【微友征婚】版块都有哪些俊男靓女上榜了，有看上的就赶紧联系ta吧！", 
                        "PicUrl" =>"/cgi-bin/getimgdata?1=1&token=618908631&lang=zh_CN&mode=small&source=biz&msgid=400533318&fileId=400130869", 
                        "Url" =>"http://mp.weixin.qq.com/s?__biz=MzA3Mzc5Mjk2OQ==&mid=400130890&idx=1&sn=b49ad2f3c35fe7c51694856c65bdaf0f&scene=20#rd");
                break;
            case '20151103':
                $contentStr[] = array("Title" =>"[图文消息]【微友征婚】我希望你是一个阳光的男孩，我们携手共进。", 
                        "Description" =>"看看本期【微友征婚】版块都有哪些俊男靓女上榜了，有看上的就赶紧联系ta吧！", 
                        "PicUrl" =>"http://mmbiz.qpic.cn/mmbiz/licuOmgdyQgu48Ws7emX2NRabtn5lEgBUyyR4pyAEicFChKOQd1TsFhUbqd9vx5avFlw2X21W4ODb1zFgayzeIWg/0?wx_fmt=jpeg", 
                        "Url" =>"http://mp.weixin.qq.com/s?__biz=MzA3Mzc5Mjk2OQ==&mid=400228244&idx=1&sn=694187024207f2807137f1d1da9e26a5&scene=20#rd");
                break;
            case '20151104':
                $contentStr[] = array("Title" =>"[图文消息]【微友征婚】我已经做好了准备，等着迎接你的到来。", 
                        "Description" =>"看看本期【微友征婚】版块都有哪些俊男靓女上榜了，有看上的就赶紧联系ta吧！", 
                        "PicUrl" =>"http://mmbiz.qpic.cn/mmbiz/licuOmgdyQgvORZAZ5iciaMCQoYhRMTE61aq4icBOCsFVxUJ9ctY7icG7VbU9G3ZJSh31oHT8CLiaHs3aYicIdmheZ1iaQ/0?wx_fmt=jpeg", 
                        "Url" =>"http://mp.weixin.qq.com/s?__biz=MzA3Mzc5Mjk2OQ==&mid=400228278&idx=1&sn=4badc1e1769254ec75126a8ad826501e&scene=20#rd");
                break;
            default:
                return false;
            break;
        }
        $resultStr = $this->transmitNews($object, $contentStr,0);
        return $resultStr;
    }
    
    /**
     * 订阅、取消订阅、点击事件
     */ 
    private function receiveEvent($object)
    {
        $contentStr = "";
        switch ($object->Event)
        {
            case "subscribe":
                $contentStr = "欢迎关注我主良缘,稍后将有更多精彩内容和交友信息为您献上！";
            case "unsubscribe":
                break;
            case "CLICK":
                switch ($object->EventKey)
                {
                    //点击单身部落
                    case "100":
                        $contentStr = '还单身嘛，那是因为你还未遇到我!

对话框回复关键词“年+月+编号”有惊喜！
本期推荐回复：“20151101”、“20151102”、“20151103”、“20151104”；

是可忍，对象被抢走不可忍!再不行动，对象就成了别人家的啦!';
                        break;
                    default:
                        $contentStr[] = array("Title" =>"默认菜单回复", 
                        "Description" =>"您正在使用的是我主良缘服务号", 
                        "PicUrl" =>"http://mmbiz.qpic.cn/mmbiz/licuOmgdyQgsPuicNegWSqicUjvVngpHicticeJqWcicGfhrbUsI62fiaWMoPicKrsqhKIn1FWpNBMicpCmptEyNbr9axIw/0?wx_fmt=jpeg", 
                        "Url" =>"http://mp.weixin.qq.com/s?__biz=MzA3Mzc5Mjk2OQ==&mid=400218424&idx=1&sn=58cb1919f7d587c3c72e547fe5122efd&scene=20#rd");
                        break;
                }
                break;
            default:
                break;      

        }
        if (is_array($contentStr)){
            $resultStr = $this->transmitNews($object, $contentStr);
        }else{
            $resultStr = $this->transmitText($object, $contentStr);
        }
        return $resultStr;
    }

    private function transmitText($object, $content, $funcFlag = 0)
    {
        $textTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
<FuncFlag>%d</FuncFlag>
</xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $funcFlag);
        return $resultStr;
    }

    /**
     * 回复图文消息
     */
    private function transmitNews($object, $arr_item, $funcFlag = 0)
    {
        //首条标题28字，其他标题39字
        if(!is_array($arr_item))
            return;

        $itemTpl = "    <item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
    </item>
";
        $item_str = "";
        foreach ($arr_item as $item)
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']
        );

        $newsTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<Content><![CDATA[]]></Content>
<ArticleCount>%s</ArticleCount>
<Articles>
$item_str</Articles>
<FuncFlag>%s</FuncFlag>
</xml>";

        $resultStr = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($arr_item), $funcFlag);
        return $resultStr;
    }

}
?>