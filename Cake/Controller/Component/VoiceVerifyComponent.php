<?php
/*
 *  Copyright (c) 2014 The CCP project authors. All Rights Reserved.
 *
 *  Use of this source code is governed by a Beijing Speedtong Information Technology Co.,Ltd license
 *  that can be found in the LICENSE file in the root of the web site.
 *
 *   http://www.yuntongxun.com
 *
 *  An additional intellectual property rights grant can be found
 *  in the file PATENTS.  All contributing project authors may
 *  be found in the AUTHORS file in the root of the source tree.
 */
App::uses('REST', 'Controller/Component');

class VoiceVerifyComponent extends Component  { 

    //include_once("./CCPRestSDK.php");

    //主帐号,对应开官网发者主账号下的 ACCOUNT SID
    private $accountSid= '8a216da857dc1f6a0157e617edcf0dda';

    //主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
    private $accountToken= 'fac89cbf7d6b456f931402669885cae8';

    //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
    //在开发调试的时候，可以使用官网自动为您分配的测试Demo的APP ID
    private $appId='8a216da857dc1f6a0157e617ef810de1';

    //请求地址
    //沙盒环境（用于应用开发调试）：sandboxapp.cloopen.com
    //生产环境（用户应用上线使用）：app.cloopen.com
    private $serverIP='app.cloopen.com';


    //请求端口，生产环境和沙盒环境一致
    private $serverPort='8883';

    //REST版本号，在官网文档REST介绍中获得。
    private $softVersion='2013-12-26';


        /**
        * 语音验证码
        * @param verifyCode 验证码内容，为数字和英文字母，不区分大小写，长度4-8位
        * @param playTimes 播放次数，1－3次
        * @param to 接收号码
        * @param displayNum 显示的主叫号码
        * @param respUrl 语音验证码状态通知回调地址，云通讯平台将向该Url地址发送呼叫结果通知
        * @param lang 语言类型。取值en（英文）、zh（中文），默认值zh。
        * @param userData 第三方私有数据
        * @param welcomePrompt  欢迎提示音，在播放验证码语音前播放此内容（语音文件格式为wav）           
        * @param playVerifyCode  语音验证码的内容全部播放此节点下的全部语音文件
        */
      function voiceVerify($verifyCode,$playTimes,$to,$displayNum,$respUrl,$lang,$userData,$welcomePrompt,$playVerifyCode)
      {

            // 初始化REST SDK
            global $accountSid,$accountToken,$appId,$serverIP,$serverPort,$softVersion;

            $rest = new REST($this->serverIP,$this->serverPort,$this->softVersion);
            $rest->setAccount($this->accountSid,$this->accountToken);
            $rest->setAppId($this->appId);

            //调用语音验证码接口
            //echo "Try to make a voiceverify,called is $to <br/>";
            $result = $rest->voiceVerify($verifyCode,$playTimes,$to,$displayNum,$respUrl,$lang,$userData,$welcomePrompt,$playVerifyCode);

             if($result == NULL ) {
                return "error";
                break;
            }

            if($result->statusCode!=0) {
                return "fail";
                //return "error code :" . $result->statusCode . "<br>";
                //return "error msg :" . $result->statusMsg . "<br>";
                //TODO 添加错误处理逻辑
            } else{
                return "success";
                // 获取返回信息
                $voiceVerify = $result->VoiceVerify;
                //echo "callSid:".$voiceVerify->callSid."<br/>";
                //echo "dateCreated:".$voiceVerify->dateCreated."<br/>";
                //TODO 添加成功处理逻辑
            }
    }

    //Demo调用,参数填入正确后，放开注释可以调用
    //voiceVerify("验证码内容","循环播放次数","接收号码","显示的主叫号码","营销外呼状态通知回调地址",'语言类型','第三方私有数据','欢迎提示音','语音验证码的内容');
}
?>
