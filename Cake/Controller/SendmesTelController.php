<?php
/**
 * 该方法公共依赖层控制器
 * 在此控制器下新增的方法建议统一加上前缀 com_ 避免和之前的方法冲突
 * @author zm
 */
class SendmesTelController extends Controller {
  /**
     * 发送短信
     * @author zm @date 2018-10-15
     * ---------------------------------------------------------
     * @param $mobile url 手机号
     * @param $content  发送内容
     * ---------------------------------------------------------
     */
  protected function send_message_ex($mobile, $content, $ext = 0, $times = null) {
        if (empty($mobile) || empty($content)) {
            return false;
        }

    //  if (empty($times)) {
    //    $times = 1;
    //  }

        //** 建材分单--修改无线循环发送短信 --> WangY.Q  2018/3/23 10:04
        if ($times===null) {
            $times = 1;
        }

        $phone = $mobile;

        $post['mobile'] = is_array($mobile) ? join(',', $mobile) : $mobile;
        $post['content'] = $content;

        $sms_api = 'http://sms.youjuke.com/sms/send_sms_api_ex';

        if ($ext >= 1) {
            $sms_api = 'http://sms.youjuke.com/sms/send_sms_mkt_api';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sms_api);
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post );

        $output = curl_exec($ch);
        curl_close($ch);

        if (trim($output) == "失败" && $times > 0) {
            $this->send_message_ex($mobile, $content, $ext, $times - 1);
        } else {
          // echo 5555;exit;
          // dump($phone);exit;
            $res = $this->create_mobile_message_logs($phone);
            $result = json_decode($res,true);
            // var_dump($result);exit;
            if($result['res'] != 200){
              return $res;
            }
            return true;
        }

        return false;
    }
  
  /**
     * 电话呼出
     * @author zm @date 2018-10-15
     * ---------------------------------------------------------
     */
    protected function send_call_out_url($hotline, $cno, $pwd, $tel, $otn = null) {
        $hotline = '3003579';
        $api = 'http://api.clink.cn/interface/PreviewOutcall?enterpriseId=' . $hotline . '&cno=' . $cno . '&pwd=' . md5($pwd) . '&customerNumber=' . $tel . '&sync=0';

    
        if (empty($otn)) {
          //判断所依赖的Models是否存在 2018-10-15 start
          $configExist = $this->cake24_check_modelExist($this->PublicTel);
          if(!$configExist){ 
            $output = json_encode(array("res" => 444));
            return $output;
          }else{
            $otn = $this->PublicTel->field("tel_num", ['tel_status' => 1, 'tel_primary' => 1]);
          }
        }

        if (!empty($otn)) {
            $api .= '&clidLeftNumber=' . $otn;
        }
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt ( $ch, CURLOPT_POST, 0 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_NOSIGNAL, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);
        //curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post );

        $output = curl_exec($ch);
        curl_close($ch);
        if (empty($output)) {
            $output = json_encode(array("res" => 900));
        }

        $res = $this->create_mobile_message_logs($tel);
        $result = json_decode($res,true);
        if($result['res'] != 200){
          return $res;
        }

        return $output;
    }


    # baomings 2.0 打电话以及发短信 增加日志
    private function create_mobile_message_logs($mobile = null) {
        $project = $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : '';
        $u_controller = $this->request->params['controller'];
        $u_action = $this->request->params['action'];
        $machine_id = $_SESSION['machine_id']; #设备号
        $uid = $_SESSION['u_id']; #用户id
        $u_ip = $_SERVER["REMOTE_ADDR"];
        $posttime = date("Y-m-d H:i:s");         

        //判断所依赖的Models是否存在 2018-10-15 start
        $mobileExist = $this->cake24_check_modelExist($this->MobileDecryptLogs);
        $baomingExist = $this->cake24_check_modelExist($this->Baomings);
        
        if($mobileExist && $baomingExist){ 
          if(is_array($mobile)){
            foreach ($mobile as $key => $value) {
              $phone[] = $this->U_ENCRYPT($value);
            }
            # 加载MODEL
            if(!empty($phone)) {
              $bmList = $this->Baomings->find('list',array('conditions'=>array('mobile' => $phone),'fields'=>array('mobile','id')));
            }
            foreach ($phone as $k => $v) {
              $data_array[] = array(
                  'project' => $project ? $project : '',
                  'controller' => $u_controller ? $u_controller : '',
                  'action' => $u_action ? $u_action : '',
                  'uid' => $uid ? $uid : '',
                  'baoming_id' => $bmList[$v] ? $bmList[$v] : '',
                  'mobile' => $v ? $v : '',
                  'ip' => $u_ip ? $u_ip : '',
                  'machine_id' => $machine_id ? $machine_id : '',
                  'posttime' => $posttime
              );
            }
            // dump($data_array);exit;
            $res = $this->MobileDecryptLogs->saveAll($data_array); # 保存日志
          }else{
            $phone = $this->U_ENCRYPT($mobile);
            $data_array = array(
                'project' => $project ? $project : '',
                'controller' => $u_controller ? $u_controller : '',
                'action' => $u_action ? $u_action : '',
                'uid' => $uid ? $uid : '',
                'baoming_id' => $baoming_id ? $baoming_id : '',
                'mobile' => $phone ? $phone : '',
                'ip' => $u_ip ? $u_ip : '',
                'machine_id' => $machine_id ? $machine_id : '',
                'posttime' => $posttime
            );
            // dump($data_array);exit;
            $res = $this->MobileDecryptLogs->save($data_array); # 保存日志
          }
          if($res){
            $output = json_encode(array("res" => 200));
          }else{
            $output = json_encode(array("res" => 400));
          }
        }else{
          $output = json_encode(array("res" => 444));#设定返回值444 表示所调用的控制器里没有加载此model  
        }       
        return $output; 
        
    }

  
  /** 
      * @判断当前model是否存在
      * @param $model class 
      * @param $method str 类的具体方法 
      * @param $property str 类的具体属性
      * @return  true / false
      * @author dtz 2017-05-31
      **/
    protected function cake24_check_modelExist($model, $method = 'find', $property = 'useTable'){

        if(empty($model)){ return false; }

        //判断需要判断的类的属性
        if(!empty($property)){

            //检查对象或类是否具有 useTable 属性
            if(property_exists($model, $property)){

                return $this->_check_method_exists($model, $method);
            }else{

                return false;
            }
        }else{

            return $this->_check_method_exists($model, $method);
        }

    }
  
  /** 
      * @判断当前model下指定方法是否存在
      * @param $model class 
      * @param $method str 类的具体方法 
      * @return  true / false
      * @author dtz 2017-05-31
    **/
    protected function _check_method_exists($model, $method){

        if(empty($model)){ return false; }

        //检查对象或类是否具有 $method 方法
        if(!empty($method)){

            if(method_exists($model, $method)){

                return true;
            }else{

                return false;
            }
        }else{

            return false;
        }
    }
  
  
}