<?php
/**
 * 该方法公共依赖层控制器
 * 在此控制器下新增的方法建议统一加上前缀 cake24_ 避免和之前的方法冲突
 * @author dutongzhen
 */
class BaseController extends Controller {

    /**
     * 报名数据来源跟踪
     * @author 120291704@qq.com @date 2018-05-08
     * ---------------------------------------------------------
     * @param $domain_url url 当前页面的主域名
     * @param $http_type  Str 主域名类型http或者https
     * @注：将获取的推广渠道来源保存在Cookie中
     * ---------------------------------------------------------
     * @更新于 2018-05-15
     */
    protected function _cake24_set_unionKey($domain_url, $http_type = 'https://'){

        if( !strpos($domain_url, $http_type) ){

            $check_url = $http_type.$domain_url;
        }

        if(filter_var($check_url, FILTER_VALIDATE_URL)){

            $this->Cookie->domain = $domain_url;
        }else{

            $this->Cookie->domain = PRIMARY_DOMAIN;
        }

        // Cookie
        $this->Cookie->name = 'youjuke';
        $this->Cookie->path = '/';
        $this->Cookie->httpOnly = true;

        //营销部门渠道推广标记
        $putin = 0;
        $child = 1;
        $get   = $this->_YJK->reqCheck($_GET);
        if( isset($get['putin']) && !empty($get['putin']) ){

            $putin = $get['putin'];

        }elseif( isset($get['bdjj']) && !empty($get['bdjj']) ){

            $putin = $get['bdjj'];
        }

        if( isset($get['child']) && !empty($get['child']) ){ $child = $get['child']; }

        $unionKey = $this->Cookie->read('unionKey');
        if (!empty($putin) && !empty($child) && (empty($unionKey) || strcasecmp($unionKey, $putin . '_' . $child))) {
            $this->Cookie->write('unionKey', $putin . '_' . $child, false, 86400);
        }

        //记录是否是广告推广过来的用户
        $this->cake24_set_unionOrigin($putin);
    }


    /*
     *@数据返回类型
     *@par $arr 需要返回的数据（以数组的形式）
     *@par $return_type true /  false
     *@更新于 2017-04-24
     */
    private function bm_returns($arr, $return_type) {
        
        unset($return);
        $return = json_encode($arr);
        if($return_type){

            return $return;
        }
        die($return);
    }

    /**
    * @记录是否是广告推广过来的用户
    * @param $putin  String 渠道来源
    * @param $is_app Bool 是否是app过来的
    * @author 120291704@qq.com
    * @date 2018-08-07
    **/
    protected function cake24_set_unionOrigin($putin, $is_app = false){
        
        if(!$_POST || !empty($is_app)){
            if( !strpos($_SERVER['QUERY_STRING'], '.png') && !strpos($_SERVER['QUERY_STRING'], '.jpg') && !strpos($_SERVER['QUERY_STRING'], '.gif') && !strpos($_SERVER['QUERY_STRING'], '.css') && !strpos($_SERVER['QUERY_STRING'], '.js') ){
                
                $is_origin = 1;//广告
                if( empty($putin) ){
                    
                    $is_origin = 2;//非广告
                    if(isset($_SERVER['HTTP_REFERER'])){

                        if( strpos($_SERVER['HTTP_REFERER'], 'youjuke') && (strpos($_SERVER['HTTP_REFERER'], 'putin') || strpos($_SERVER['HTTP_REFERER'], 'bdjj')) ){
                            $is_origin = 1;//广告
                        }
                    }
                    
                }
                
                $this->Cookie->write('unionOrigin', $is_origin, false, 86400);
            }
            
        }

        unset($is_origin, $putin, $is_app);
    }


    /**
     * 记录今日头条广告主跟踪参数
     * @author 120291704@qq.com 
     * @date   2018-04-18
     * @更新于 2018-05-15
     */
    protected function cake24_set_campaign(){

        if( isset($this->request->query['campaign']) && !$this->request->query['campaign'] ){

            $this->Cookie->delete('campaign');return ;
        }

        $params = $this->_check_toolGlobalExist($this->request->query);

        if(!isset($params['campaign'])){ return ; }
        $campaign = $params['campaign'];

        //判断参数是否只包含数字字符的字符串
        if( ctype_digit($campaign) ){

            $this->Cookie->write('campaign', $campaign, false, 86400);
        }else{

            $this->Cookie->delete('campaign');
        }

        unset($params, $campaign);

    }

    /*
     *@更新服务咨询数据信息
     *@par $baoming_id 报名id
     *@author dtz 2017-03-28
     *注：如果已报名，未报名服务，这里不作处理；如果已报名和服务，对SERVICE表处理；如果仅新报名，按原有逻辑处理
     *处理逻辑为将baoming_service表中out_time改为当前一次的报名时间，out_type改为3，out_csid=0
     *@author dtz 2017-03-21
     *@更新于 2017-03-28
     */
    private function _cake24_check_service($baoming_id){

        if(!empty($baoming_id)){

            $serviceExist = $this->cake24_check_modelExist($this->BaomingService);
            if(!$serviceExist){ return ; }
            
            $conditions = ['baoming_id' => $baoming_id];
            
            $serviceData = $this->BaomingService->find('first', [

                'conditions' => $conditions
            ]);

            if($serviceData['BaomingService']['out_type'] == 0){

                $up_service_data = [

                    'out_csid' => 0,
                    'out_type' => 3,
                    'out_time' => time()
                ];

                $this->BaomingService->updateAll($up_service_data, $conditions);
            }

        }
    }

    /**
     * 服务咨询报名入口
     * @author 120291704@qq.com @date 2017-03-21
     * ---------------------------------------------------------
     * @param $return bool false / true
     * @注:曾经在服务咨询（zx_fuwu = 1）报过名的，并且out_type = 
       0的，除了zx_tuijian改为1以外，将baoming_service
       表中out_time改为当前一次的报名时间，out_type改为3，out_csid=0
     * @注：报名接口添加判断所有依赖的Models是否存在的容错机制，避免致命错误
     * ---------------------------------------------------------
     * @更新于 2017-08-02
     */
    protected function cake24_commit($return = false){

        /**************判断所有依赖的Models是否存在 2017-08-02 start*****************/
        $need_model_arr  = ['BaomingService', 'GenzongCsfp'];
        $checkmodelExist = $this->cake24_check_modelArrExist($need_model_arr);
        if(!$checkmodelExist['status']){

            $this->bm_returns( array('code' => 10401, 'str' => $checkmodelExist['errorMsg'], 'data' => '必须依赖的models有：'.implode(',', $need_model_arr)), false );
        }
        /**************判断所有依赖的Models是否存在 2017-08-02 end*****************/

        unset($service_data);
        $baoming_id = $this->cake24_commit2(true, false, false, true);//服务咨询
        if(!empty($baoming_id)){

            //判断服务咨询报名是否存在
            $service_exist = $this->BaomingService->find('first', [

                'conditions' => ['baoming_id' => $baoming_id]
            ]);

            if(!$service_exist){
                $service_data = ['baoming_id' => $baoming_id, 'last_plate' => 0, 'create_time' => time()];

                //判断当前报名是否分配过客服（如果分配过需要baoming_service.is_fp_genzong 为1）
                $genzong_con = ['baoming_id' => $baoming_id, 'type' => 1, 'f_cs !=' => NULL];
                $genzong_exist = $this->GenzongCsfp->find('count', [ 'conditions' => $genzong_con ]);
                if($genzong_exist){

                    $service_data['is_fp_genzong'] = 1;
                }


                if($this->BaomingService->save($service_data)){

                    //更新服务咨询数据信息
                    //$this->_cake24_check_service($baoming_id);

                    //报名成功
                    if($return){

                        return $baoming_id;
                    }else{

                        $this->bm_returns( array('code' => 10200, 'str' => '报名成功！'), false );
                    }
                    
                }else{

                    //报名错误
                    $this->bm_returns( array('code' => 10400, 'str' => '报名失败，请重试！'), false );
                }
            }else{

                
                $this->bm_returns( array('code' => 10400, 'str' => '您已报名！'), false );
            }
        }else{

            //报名错误
            $this->bm_returns( array('code' => 10400, 'str' => '报名失败，请重试！'), false );
        }
    }

    /**
     * cake24项目下新增报名数据录入接口
     * @author 120291704@qq.com @create date 2015-11-16
     * --------------------------------------------------------------------------------
     * @param $is_zhuangxiu  true 其他装修报名 false  建材馆报名
     * @param $return_type 返回类型 （APP端如果想要获取报名失败后的详细信息则为 true）
     * @param $service false（不是服务咨询报名），  true （是服务咨询报名）
     * @注：app 过来的报名 $REQUEST['find_cookies'] = 1
     * @注：考虑到网站的报名渠道来源COOKIE数据信息是httponly属性，js无法获取
     *      所以针对静态页的报名渠道来源只能单独保存（$_COOKIE['js_unionKey']）
     *      并且通过静态页报名过来的页面统一标记 $REQUEST['static_page'] = 1 其余为 0
     * @注：报名接口添加判断所有依赖的Models是否存在的容错机制，避免致命错误 (20170803)
     * @注：报名接口添加判断是否加载类 ToolGlobal
     * --------------------------------------------------------------------------------
     * @更新于 2018-10-08
     */
    protected function cake24_commit2($return = false, $is_zhuangxiu = true, $return_type = false, $service = false) {

        //判断是否加载类 ToolGlobal
        $REQUEST = $this->_check_toolGlobalExist($_POST);
        

        /**************判断所有依赖的Models是否存在 2017-08-02 start*****************/
        $need_model_arr  = $this->_cake24_bm_basic_model;
        $checkmodelExist = $this->cake24_check_modelArrExist($need_model_arr);
        if(!$checkmodelExist['status']){

            return $this->bm_returns( ['code' => 10401, 'str' => $checkmodelExist['errorMsg'], 'data' => '必须依赖的models有：'.implode(',', $need_model_arr)], $return_type );
        }
        /**************判断所有依赖的Models是否存在 2017-08-02 end*****************/

        $data    = [];
        $require = ['name', 'mobile'];
        
        foreach ($require as $value) {
            
            if(empty($REQUEST[$value])){//判断关键数据是否为空，为空则返回错误

                $str = $value == 'name' ? '姓名':'手机号';

                return $this->bm_returns( ['code' => 10402, 'str' => $str.'不能为空！'], $return_type);
            }

            $func = 'cake24_ck' . $value;
            if($value == 'mobile') {
                
                //U_ENCRYPT 作用是对手机进行加密处理
                $foo = !$data[$value] = $this->U_ENCRYPT($this->$func($REQUEST[$value]));
            }else{
                
                //对姓名进行过滤，去除掉特殊字符和如果姓名为空则标注为NONAME
                $foo = !$data[$value] = $this->$func($REQUEST[$value]);
            }
            if (empty($REQUEST[$value]) || !method_exists($this, $func) || $foo) {
                
                return $this->bm_returns( ['code' => 10403, 'str' => '提交错误，请不要填写特殊字符！'], $return_type );
            }
        }

        //对字段数据进行判断
        $fields = ['area', 'budget', 'loupan', 'roomtype', 'zxstyle', 'zx_fangshi', 'address', 'zxtype', 'utm_page', 'sex', 'decoration_type', 'district_id', 'c_preferences', 'zhuangixu_status','c_experience'];
        $keyArr = ['area' => '面积', 'budget' => '预算', 'loupan' => '楼盘', 'utm_page' => '报名页面来源', 'sex' => '性别', 'district_id' => '区域'];
        
        foreach ($REQUEST as $key => $value) {
            
            //传递过来的数据不在判断数组中则跳过
            if ( !in_array($key, $fields) ){
                continue;
            }

            //传递过来的数据在判断数组中则判断格式是否正确并且不能为空
            $func = 'cake24_ck' . $key;
            if (!method_exists($this, $func) || !$data[$key] = $this->$func($value)) {
                // continue;
                return $this->bm_returns( ['code' => 10404, 'str' => $keyArr[$key].' 数据格式错误！', 'data' => ['error field' => $key]], $return_type );
            }
        }

        $ips = $this->cake24_getRealIp();

        //屏蔽掉局域网、外测和系统配置ip
        //true 当前ip需要屏蔽（不需要限制报名次数） ， false 当前ip不需要屏蔽（需要限制报名次数）
        $ignore = $this->cake24_ignore_ip($ips);

        if( !$ignore ){//不需要屏蔽的ip,但不能重复报名超过10次

            $client_ip_count = $this->Baomings->find('count',array(
                    'conditions'=>array(
                        'client_ip'=>$ips,
                        'DATE_FORMAT(addtime,\'%Y-%m-%d\')'=>date('Y-m-d')
                    )
                )
            );

            //同一个ip下报名次数不能超过10次
            if( $client_ip_count >= 10 ){
                
                return $this->bm_returns( ['code' => 10405, 'str' => '报名次数过多，请稍后再试！'], $return_type );
            }
        }
        

        $data['bm_laiyuan']  = 'baoming';
        $data['zx_jiaju']    = isset($REQUEST['zx_jiaju']) ? (int)$REQUEST['zx_jiaju'] : 0;
        $data['addtime']     = date('Y-m-d H:i:s');
        $data['mes_bak']     = 1;
        $data['province_id'] = 9;
        $data['city_id']     = 72;
        $data['client_ip']   = $ips;
        $data['bm_flag']     = isset($REQUEST['bm_flag']) ? (int)$REQUEST['bm_flag'] : 0;
        $data['bm_bak']      = isset($REQUEST['bm_bak']) ? trim($REQUEST['bm_bak']) : '';

        //$REQUEST['static_page'] 不为空则说明是静态页过来的报名，且报名渠道来源为 $_COOKIE['js_unionKey']
        $unionKey_cookie     = empty($REQUEST['static_page']) ? $this->Cookie->read('unionKey') : $_COOKIE['js_unionKey'];
        $union = array_key_exists('unionKey', $REQUEST) ? $REQUEST['unionKey'] : $unionKey_cookie;
        $name = 'BM';
        if (!empty($union)) {
            
            list($name, $child) = explode('_', $union, 2);
            $name  = strtoupper($name);

            //对推广渠道过滤特殊字符
            $name  = $this->cake24_replace_text( $name );
            $child = $this->cake24_replace_text( $child );

            $data['from_union']   = $name;
            $data['from_child']   = $child;
            $data['newest_child'] = $child;
            $this->loadModel("SemSources");
            $bm_laiyuan = $this->SemSources->field( 'j_name' ,array('l_name'=>$name,'state'=>0) );
            if( $bm_laiyuan ){
                
                if( $name == 'LT' ){
                    
                    $data['from_child'] = str_replace('+', '|', $child);
                }

                $data['bm_laiyuan']     = $bm_laiyuan;
            }else{
                
                $data['bm_laiyuan']     = 'sem1';
            }             
        }

        $data['campaign'] = !empty($this->Cookie->read('campaign')) ? $this->Cookie->read('campaign') : '';

        if( in_array($name, ['BDXK2','BDXK']) ){
            $data['is_origin']  = 0;
        }else{
            
            //$REQUEST['static_page'] 不为空则说明是静态页过来的报名，且报名渠道来源为 $_COOKIE['js_unionKey']
            $unionOrigin_cookie = empty($REQUEST['static_page']) ? $this->Cookie->read('unionOrigin') : $_COOKIE['js_unionOrigin'];

            //记录是否是广告推广过来的用户
            //app和第三方接口 过来的报名 统一添加参数 $REQUEST['find_cookies'] = 1
            if( isset($REQUEST['find_cookies']) && ($REQUEST['find_cookies'] == 1) ){

                $data['is_origin'] = 0;

            }else{

                $data['is_origin'] = ($unionOrigin_cookie == 1) ? 0 : 1;
            }
            
            //自然流量（seo）报名来源记录
            if( $data['is_origin']  == 1 ){
                $data['bm_laiyuan'] = 'baoming';
                $data['from_union'] = '';
                $data['from_child'] = '1';
            }
        }

        //按断客户端是否都是手机
        if ($this->request->is('mobile')) {
            $data['detail_ly'] = 'mobile';
        } else {
            $data['detail_ly'] = 'pc';
        }
        

        $baomingModel = $this->Baomings->find('first', array(
                'conditions' => array('Baomings.mobile' => $data['mobile']), 
                'fields'     => array('Baomings.id', 'Baomings.zx_tuijian','Baomings.zx_jiaju','BaomingsPlus.periods', 'Baomings.zx_fuwu', 'Baomings.is_conect', 'Baomings.yixiang', 'Baomings.is_fp', 'Baomings.mes_bak', 'Baomings.delay', 'Baomings.status', 'Baomings.status_trace', 'Baomings.invalid', 'Baomings.decoration', 'Baomings.is_qy','Baomings.can_not_go','Baomings.reject_allot_status','Baomings.reject_reallot_status','Baomings.ownercs'), 
                'order'      => array('Baomings.id desc')
            )
        );

        if(!$is_zhuangxiu && !$service){//建材馆报名非服务咨询
            
            $data['zx_tuijian'] = 0;
            $data['zx_jiaju']   = 1;

        }elseif($is_zhuangxiu && !$service){//装修非服务咨询
            $data['zx_tuijian'] = 1;
            $data['zx_jiaju']   = 0;

        }elseif ($service) {//服务咨询
            $data['zx_tuijian'] = 0;
            $data['zx_jiaju']   = 0;
            $data['zx_fuwu']    = 1;
        }

        //判断手机号是否在白名单列表
        $in_whitelist  = $this->_cake24_check_mobile_whitelist($REQUEST['mobile']);
        if($in_whitelist){

            //白名单手机号报名进来的数据不进入分单流程
            $data['is_fp']  = 2;
        }

        //判断当前报名页面是否是大金中央空调的页面，如果是则标注is_top=1（该处为临时处理）
        if($this->_check_bmIsTop($data['utm_page'])){

            $data['is_top'] = 1;
        }

        $cake24_sjtest = $this->JcSetPeriod->field('periods',array('is_set'=>1),'JcSetPeriod.id desc');

        //没有数据添加  否则重置或修改
        if( !$baomingModel || $in_whitelist ){

            if (!$this->Baomings->save($data)) {

                return $this->bm_returns( ['code' => 10406, 'str' => '错误提交！'], $return_type );
            }

            $this->U_SAVE_BM($this->Baomings->id, $REQUEST['mobile']);
            $this->BaomingsPlus->updateAll(array('periods'=>"'".$cake24_sjtest."'",'material_addtime'=>time()),array('BaomingsPlus.baoming_id'=>$this->Baomings->id));

            $Baomings_id = $this->Baomings->id;

            $this->_cake24_festival_push($REQUEST['mobile']);//节假日期间短信推送
            $this->_cake24_marketing_push($REQUEST['mobile']);//营销推送短信
            $this->_cake24_load_baoming_log($this->Baomings->id, $data);//添加报名日志记录
            
        }else{
            
            $Baomings_id = $baomingModel['Baomings']['id'];
            
            //是建材特卖馆报名并且非服务咨询报名
            if(!$is_zhuangxiu && !$service){
                
                //不是同期的添加数据  否则修改参数   (取消期数报名)
                if($baomingModel['BaomingsPlus']['periods'] != $cake24_sjtest){
                    
                    $bm_updata = array(
                        'zx_jiaju'     => 1,
                        'utm_page'     => "'".$data['utm_page']."'",
                        'newest_child' => "'".$data['newest_child']."'",
                        'is_origin'    => $data['is_origin']
                    );
                    $this->Baomings->updateAll( $bm_updata ,array('Baomings.id'=>$Baomings_id));
                    //20170406 是建材报名 重置无建材活动需求字段BaomingPlus.unjchd 改为 0  连
                    /***建材报名 重置无建材活动需求字段BaomingPlus.jc_fp_read 改为 1 dtz 20170809***/
                    $this->BaomingsPlus->updateAll(array('periods'=>"'".$cake24_sjtest."'",'material_addtime'=>time(),'unjchd' => 0, 'jc_fp_read' => 1),array('BaomingsPlus.baoming_id'=>$Baomings_id));
                }else{
                    
                    //报名数据之前 zx_jiaju不等于1的
                    if($baomingModel['Baomings']['zx_jiaju'] != 1){
                        
                        $arrs['zx_jiaju']     = 1;
                        $arrs['utm_page']     = "'".$data['utm_page']."'";
                        $arrs['newest_child'] = "'".$data['newest_child']."'";
                        $arrs['is_origin']    = $data['is_origin'];
                        $this->Baomings->updateAll($arrs,array('Baomings.id'=>$Baomings_id));
                        $this->BaomingsPlus->updateAll(array('material_addtime'=>time()),array('baoming_id'=>$Baomings_id));  
                    }else{
                        
                        $arrs['utm_page'] = "'".$data['utm_page']."'";
                        $arrs['newest_child'] = "'".$data['newest_child']."'";
                        $arrs['is_origin'] = $data['is_origin'];
                        $this->Baomings->updateAll($arrs,array('Baomings.id'=>$Baomings_id));
                        
                        return $this->bm_returns( ['code' => 10407, 'str' => '您已报名！'], $return_type );
                    }    
                }    
                
            //属于装修业务重置逻辑
            }elseif($is_zhuangxiu && !$service){
                
                //更新服务咨询数据信息
                $this->_cake24_check_service($Baomings_id);

                //判断当前报名两月内是否还在工程部内，如果在则不能让该单子进入分单流程，两月后则可进入
                $check_in_gcb = $this->_check_baoming_ingcb($Baomings_id);

                if($check_in_gcb){//可以进入分单流程

                    //判断是否是有装修报名数据 调重置方法 否则更新
                    if($baomingModel['Baomings']['zx_tuijian'] == 1){

                        $json_decode = json_decode($this->_cake24_check_bm_reset($baomingModel),true);
                        $Baomings_id = $json_decode['baoming_id'];
                    }else{
                        
                        $arrs['zx_tuijian'] = 1;
                    }

                    $arrs['utm_page']     = "'".$data['utm_page']."'";
                    $arrs['newest_child'] = "'".$data['newest_child']."'";
                    $arrs['is_origin']    = $data['is_origin'];

                    if($this->_check_bmIsTop($data['utm_page'])){

                        $arrs['is_top'] = 1;
                    }

                    $this->Baomings->updateAll($arrs,array('Baomings.id'=>$Baomings_id));

                }

            }elseif($service) {//服务咨询报名重置
                
                $arrs['zx_fuwu']      = 1;
                $arrs['utm_page']     = "'".$data['utm_page']."'";
                $arrs['newest_child'] = "'".$data['newest_child']."'";
                $arrs['is_origin']    = $data['is_origin'];
                $this->Baomings->updateAll($arrs,array('Baomings.id'=>$Baomings_id));
            }

            $this->_cake24_load_baoming_log($Baomings_id, $data, 2);//添加报名日志记录
        }

        //记录具体页面的报名按钮（或 弹窗等）来源
        if(isset($REQUEST['bm_source'])){

            $this->_cake24_loadBaomingSource($Baomings_id, $data['utm_page'], $REQUEST['bm_source']);
        }
        
        
        # 全站弹窗
        // setcookie('pop_youjuke', 1, time() + 30 * 24 * 60 * 60, '/', '.'.PRIMARY_DOMAIN);

        if($return){ return $Baomings_id; }
        return $this->bm_returns( ['code' => 10200, 'str' => '提交成功！', 'data' => ['id' => $Baomings_id]], $return_type );
    }

    /** 
     * @判断当前报名页面是否是置顶报名
     * @判断当前报名页面是否是大金中央空调的页面，如果是则标注is_top=1（该处为临时处理）
     * @param  $utm_page   str    页面来源信息
     * @author 120291704@qq.com 2018-09-03
     **/
    private function _check_bmIsTop($utm_page){

        if(empty($utm_page)){ return false; }
        if(in_array($utm_page, ['M_中央空调6折'])){

            return true;
        }else{

            return false;
        }
    }


    /** 
     * @记录具体页面的报名按钮（或 弹窗等）来源
     * @param  $baoming_id number 报名ID
     * @param  $channel    str    项目来源
     * @param  $utm_page   str    页面来源信息
     * @param  $source str 来源信息
     * @author 120291704@qq.com 2018-04-09
     **/
    protected function _cake24_loadBaomingSource( $baoming_id, $utm_page, $source ){

        $matches = [];
        $source  = $this->cake24_replace_text($source);
        
        if( empty($source) || empty($utm_page) ){ return ; }
        if( !is_numeric($baoming_id) || empty($baoming_id)){ return ; }

        $source  = $utm_page.'-'.$source;

        //获取当前所处的项目
        $server_name    = explode('.', $_SERVER['SERVER_NAME']);
        $project = $this->_cake24_getchannelname[$server_name[1]][$server_name[0]];
        if( empty($project) ){ return ; }

        $source  = $project.'-'.$source;

        $matches = explode( '-', $source );
        list($channel, $page, $type, $name) = $this->Sources->setSources($matches[0], $matches[1], $matches[2], $matches[3]);

        $source_data = [

            'channel_id' => $channel,
            'page_id'    => $page,
            'type_id'    => $type,
            'name_id'    => $name,
            'baoming_id' => $baoming_id,
            'addtime'    => date('Y-m-d H:i:s')
        ];

        try {
            $this->BaomingSources->save($source_data);
        } catch (Exception $error) {
            
            return false;
        }
    }

    /** 
      * @节假日短信内容推送
      * @param  $mobile number 手机号
      * @param  $start_date str 营销短信开始日期
      * @param  $end_date   str 营销短信结束日期
      * @author 120291704@qq.com 2018-01-18
      **/
    protected function _cake24_festival_push($mobile, $start_date = '2018-02-01 11:00:00', $end_date = '2018-02-22' ){

        return ; //非节假日（暂时关闭）
        if(!$this->cake24_check_mobile($mobile)){ return false; }

        if( (time() < strtotime($start_date)) || (time() > strtotime($end_date)) ){ return false; }

        $data['mobile']  = $mobile;
        $data['content'] = '优居客春节于2月2日-2月21日放假，过年期间报名，客服将在年后给您回电，届时您可咨询装修相关的任何问题！';

        $this->cake24_send_message($data, true);

    }

    /** 
      * @营销推广统一发送机制
      * @param  $mobile number 手机号
      * @param  $end_date   str 营销短信结束日期
      * @author 120291704@qq.com 2017-12-29
      **/
    protected function _cake24_marketing_push($mobile, $end_date = '2018-01-29' ){

        return ; //已结束（暂时关闭）
        if(!$this->cake24_check_mobile($mobile)){ return false; }

        if( time() > strtotime($end_date) ){ return false; }

        $data['mobile']  = $mobile;
        $data['content'] = '【优居客客服中心】年终大促！参加装修团购享最高24%返利，相当于10万装修款优惠了2.4万！活动时间12.25-1.28，错过再等一年！上海800余家公司随意挑选！稍后客服将来电告知您如何参团，请注意接听。详询4009206688 TD退订';

        $this->cake24_send_message($data, true);

    }


    /** 
      * @判断手机号是否在报名手机号白名单中
      * @param  $mobile number 手机号
      * @return bool false / true
      * @author 120291704@qq.com 2017-10-30
      **/
    protected function _cake24_check_mobile_whitelist($mobile){

        if(!constant('PHONE_WHITELIST')){ return false; }

        if(!$this->cake24_check_mobile($mobile)){ return false; }

        $whiteListArr = explode(',', constant('PHONE_WHITELIST'));

        if(in_array($mobile, $whiteListArr)){

            return true;
        }else{

            return false;
        }

    }


    /**
    * 装修公司公共基本信息抓取逻辑
    * @author 120170714@qq.com
    * @param  params                Array   限制抓取个数
    * @param  params['limit']       int   限制抓取个数
    * @param  params['firm_idArr']  Array 抓取指定装修公司id的数据
    * @param  params['fields']      Array 显示具体的数据字段
    * @param  params['refresh']     Bool  是否需要一直获取实时数据 (false 不需要 true 需要)
    * @param  overTime              int
    * @param  redisObject           object
    * @return firm_data             Array       
    * @date 2018-03-27
    */
    protected function cake24_getDecorateCompanyData($params = [], $overTime = 25, $redisObject = ''){

        if(empty($params)){ return ; }
        if(empty($params['refresh'])){ $params['refresh'] = false; }
        if(!is_numeric($params['limit']) || empty($params['limit'])){ return ; }

        unset($firm_data);
        unset($return_data);

        $limit  = $params['limit'];
        if( (!empty($params['fields'])) && is_array($params['fields']) ){

            $fields = $params['fields'];
        }else{

            $fields = ['Firms.id', 'Firms.title', 'Firms.jc_title', 'Firms.firm_logo', 'Firms.biz_class'];
        }

        $key    = 'GET_FIRMDATA_'.$limit;

        if(empty($redisObject) || !is_object($redisObject)){ $redisObject = $this->Redis; }

        //判断redis calss 是否存在
        $redisExist = $this->cake24_check_modelExist($redisObject, 'get', NULL);
        if(!$redisExist){ return ; }

        if($params['refresh']){ $redisObject->del($key); }
        
        if ($redisObject->get($key)) {
            $firm_data = $redisObject->get($key);

            if(empty($firm_data)){ return ; }
            return json_decode($firm_data, true);

        }else{

            $this->loadModel('Firms');
            //判断当前Model是否存在
            $isExist = $this->cake24_check_modelExist($this->Firms);

            if($isExist){

                //优先显示核心商家，依次为A B C
                $conditions        = [
                    '`Firms`.`status`'       => 1,  //1-已激活
                    '`Firms`.`firm_logo` !=' => ['', '0'], //必须要有公司logo
                    '`Firms`.`biz_class` !=' => 0   //为核心商家 A B C
                ];

                if(!empty($params['firm_idArr'])){

                    $conditions = array_merge($conditions, ['`Firms`.`id`' => $params['firm_idArr']]);
                }

                $firm_data  = $this->Firms->find('all', [
                    'conditions'   => $conditions,
                    'fields'       => $fields,
                    'order'        => ['`Firms`.`biz_class` ASC'],
                    'limit'        => $limit
                ]);
                $this->_cake24_disconnectMysql();

                $firm_dataCnt = count($firm_data);
                if( $firm_dataCnt < $limit ){//核心商家数量不足，非核心商家补充

                    $conditions      = [
                        '`Firms`.`status`' => 1,        //1-已激活
                        '`Firms`.`firm_logo` !=' => ['', '0'], //必须要有公司logo
                        '`Firms`.`biz_class`' => 0      //非核心商家
                    ];

                    if(!empty($params['firm_idArr'])){

                        $conditions = array_merge($conditions, ['`Firms`.`id`' => $params['firm_idArr']]);
                    }

                    $firm_data_add   = $this->Firms->find('all', [
                        'conditions' => $conditions,
                        'fields'     => $fields,
                        'limit'      => $limit - $firm_dataCnt
                    ]);
                    $this->_cake24_disconnectMysql();

                    if(!$firm_dataCnt){//核心商家数据为空的情况

                        $return_data = $firm_data_add;
                    }else{

                        $return_data = array_merge($firm_data, $firm_data_add);
                    }

                    if(!empty($return_data)){

                        $redisObject->setex($key, $overTime, json_encode($return_data));
                    }

                    return $return_data;

                }else{

                    if(!empty($firm_data)){

                        $redisObject->setex($key, $overTime, json_encode($firm_data));
                    }

                    return $firm_data;
                }
            }
        }
    }


    //姓名（名称）格式判断
    private function cake24_ckname($value) {
        $value = $this->cake24_replace_specialChar($value);//过滤掉特殊字符
        if ( isset($value) && !empty($value) ) {
            
            //考虑到用户可能把姓名也直接写成手机号，为此添加一层判断，避免手机号泄露
            return $this->_cake24_formatUsername($value);
        }else{

            return 'NONAME';//过滤掉特殊字符后，如果为空则赋值默认的值
        }
    }

    //手机号格式判断
    private function cake24_ckmobile($value) {
        if ($this->cake24_check_mobile($value)) {
            return $value;
        }
        return false;
    }

    //报名面积格式判断
    private function cake24_ckarea($value) {
        if (preg_match('/^\d{1,4}$/', $value)) {
            return $value;
        }
        return false;
    }

    //报名预算格式判断
    private function cake24_ckbudget($value) {
        if (preg_match('/^\d{1,4}(\.\d{1,4})?$/', $value)) {
            return $value;
        }
        return false;
    }

    //报名地址格式判断（去除掉特殊字符）
    private function cake24_ckaddress($value) {
        if(strlen(strip_tags($value)) > 255){

            return false;
        }
        return strip_tags($value);
    }

    //报名楼盘格式判断（去除掉特殊字符）
    private function cake24_ckloupan($value) {
        if(strlen(strip_tags($value)) > 50){

            return false;
        }
        return strip_tags($value);
    }

    //报名户型格式判断
    private function cake24_ckroomtype($value) {
        return is_numeric($value) ? $value : false;
    }

    //装修风格
    private function cake24_ckzxstyle($value) {
        return is_numeric($value) ? $value : false;
    }

    //装修方式
    private function cake24_ckzx_fangshi($value) {
        if(strlen(strip_tags($value)) > 20){

            return false;
        }
        return strip_tags($value);
    }

    //备注
    private function cake24_ckbm_bak($value) {
        if(strlen(strip_tags($value)) > 1000){

            return false;
        }
        return strip_tags($value);
    }
    //业主装修类型，0->未知，1->全新装修，2->局部装修
    private function cake24_ckzxtype($value) {
        return is_numeric($value) ? $value : false;
    }

    //报名页面来源格式判断
    private function cake24_ckutm_page($value) {
        
        if(strlen($this->cake24_replace_text($value)) > 255){

            return false;
        }
        return trim(strip_tags($value));
    }

    //报名性别判断
    private function cake24_cksex($value) {
        return in_array($value, array(1, 2)) ? $value : 2;
    }

    //装修类型（Decoration Type） 0未知 1毛坯 2二次装修
    private function cake24_ckdecoration_type($value) {
        return is_numeric($value) ? $value : false;
    }

    //报名区域判断
    private function cake24_ckdistrict_id($value) {
        if( !empty($value) && is_numeric($value) ){

            return $value;
        }else{

            return 717;
        }
    }

    //报名装修偏好：1:偏重价格，2:偏重设计
    private function cake24_ckc_preferences($value) {
        return is_numeric($value) ? $value : false;
    }

    //报名装修状态  1还没买房  2-已买房，未没拿到钥匙   3-准备装修  4-已订装修公司  5-已订装修了
    private function cake24_ckzhuangixu_status($value) {
        return is_numeric($value) ? $value : false;
    }

    //装修经历：1:第一次装修，2:之前装修过
    private function cake24_ckc_experience($value) {
        return is_numeric($value) ? $value : false;
    }

    //替换敏感字符
    protected function cake24_replace_text($text)
    {
        $text = str_replace('\\', '', $text);
        $text = preg_replace('/\/|\"|\'|[><()=;#!@#$%^&*?.+|]|(and)|(or)/i', '', $text);
        return $text;
    }

    /*
    *去除掉特殊字符（新）
    *date 2017-01-05
    */
    protected function cake24_replace_specialChar($strParam){
        
        return preg_replace("/[^\x{4e00}-\x{9fa5}a-zA-Z0-9]/iu",'',$strParam);
    }

    //获取省信息
    protected function cake24_getProvinces($cityid = 0){
      
        if(!$cityid){
            
            $getCity = $this->cake24_getCity( $this->cake24_getRealIp() );
            $city = in_array($getCity['city'], array('上海','无锡')) ? $getCity['city']  : '上海';
            $conditions = array('Cities.name'=>$city);
        }else{
            
            $conditions = array('Cities.id'=>$cityid);
        }
        $Cities = $this->Cities->find('all',array('conditions'=>$conditions));
        return $Cities[0];
    }

    protected function cake24_getCity($ip){
        return false;
        //$url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
        $url="http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=".$ip;
        $ip = $this->cake24_curl_get($url);
        $ip = json_decode($ip,true);
        if($ip['ret'] == -1)
        {
            return false;
        }
        return $ip;
        
    }

    //跨域获取信息
    public function cake24_curl_get($url){
        
        $to=2;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $to);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }

    /**
    * 获取用户真实IP（该方法已废止）
    * date 2018-04-09
    */
    public function cake24_getIP() {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        else
            if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
                $ip = getenv("HTTP_X_FORWARDED_FOR");
            else
                if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
                    $ip = getenv("REMOTE_ADDR");
                else
                    if (isset ($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
                        $ip = $_SERVER['REMOTE_ADDR'];
                    else
                        $ip = "unknown";
        return ($ip);
    }

    //获取用户真实IP
    public function cake24_getRealIp() {
        if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
            $ip = getenv("REMOTE_ADDR");
        } else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
            $xF = explode(',', getenv("HTTP_X_FORWARDED_FOR"));
            $ip = end($xF);
        } else if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } else {
            $ip = "unknown";
        }
        return $ip;
    }


    /** 
      * @发送账户短信 （添加营销短信）
      * @param $data array 包含手机号，短信内容等
      * @param $market_sms bool flase 非营销短信 / true 营销短信
      * @param $times int 第几次发送
      * @author dtz 2017-08-17
      **/
    public function cake24_send_message($data, $market_sms = false, $times = 1){
        
        if (empty($data['mobile']) || empty($data['content'])) {
            return false;
        }

        if (empty($times)) {
            $times = 1;
        }

        if(!$market_sms){

            $sms_api = 'http://sms.youjuke.com/sms/send_sms_api';
        }else{

            $sms_api = 'http://sms.youjuke.com/sms/send_sms_mkt_api';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sms_api);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $output = curl_exec($ch);
        curl_close($ch);

        if (trim($output) == "失败" && $times <= 2) {
            
            $this->cake24_send_message($data, $market_sms, $times+1);
        } else {
            //return true;
            return '成功';
        }

        //return false;
        return '失败';
    }

    //通过手机号查询手机号码的归属地信息 (已失效)
    public function cake24_phonesearch2($mobile){

        $ch = curl_init();
        $url = 'http://apis.baidu.com/chazhao/mobilesearch/phonesearch?phone='.$mobile;
        $header = array(
            'apikey: c3aadd615ae4b6bfcbf8e4afc503fab7',
        );
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);

        return json_decode($res, TRUE);
    }

    //通过手机号查询手机号码的归属地信息
    public function cake24_phonesearch($mobile){

        $data['error'] = 1;//1 ? 0 : 1;
        $data['message'] = '接口已废弃';
        $data['data'] = '';
        return $data;
        
        $url = 'http://sj.apidata.cn/?mobile='.$mobile;
        $ch = curl_init();
        // 执行HTTP请求
        curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch); 

        curl_close($ch);
        $res = json_decode($res, TRUE);
        $data['error'] = $res['status'] == 1 ? 0 : 1;
        $data['message'] = $res['message'];
        $data['data'] = $res['data'];
        return $data;
    }

    //通过ip查询ip的归属地信息
    public function cake24_ipsearch($ip){

        //判断是否是合法的ip
        if(!filter_var($ip, FILTER_VALIDATE_IP)){return NULL;}
        
        $ch = curl_init();
        $url = 'http://apis.baidu.com/apistore/iplookupservice/iplookup?ip='.$ip;
        $header = array(
            'apikey: c3aadd615ae4b6bfcbf8e4afc503fab7',
        );
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);

        return json_decode($res, TRUE);
    }

    /*
     * 判断业主报名的地址，目前公司自支持上海和江苏地区
     * 如果报名的来源地址为非上海和江苏的 则录入报名里 zx_tuijian =0 反之则为 1
     */

    public function cake24_getbaoming_area($mobile, $ip=''){

        //获取报名信息的ip地址
        if(empty($ip)){$ip = $this->cake24_getRealIp();}

        $ipAddress = $this->cake24_ipsearch($ip);
        // $phoneAddress = $this->cake24_phonesearch($mobile);

        $ipresult = in_array($ipAddress['province'], array('上海', '江苏'));
        $mobileresult = true; //in_array($phoneAddress['data']['province'], array('上海', '江苏'));

        if($ipresult || $mobileresult){


            return 1;
        }else{

            return 0;
        }

    }

    //通过经纬度获取具体的地理位置
    public function cake24_getLocation($x, $y){

        $ch = curl_init();
        $url = 'http://apis.baidu.com/3023/geo/address?l='.$x.','.$y;
        
        $header = array(
            'apikey: c3aadd615ae4b6bfcbf8e4afc503fab7',
        );
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);

        $addressData = json_decode($res, true);
        $admName = $addressData['addrList'][0]['admName'];
        $locationArr = explode(',', $admName);
        return $locationArr;

    }

    /*
    * 通过经纬度获取具体的地理位置(新)
    * $x,$y 经纬度
    * date 2016-10-24
    */
    public function cake24_getAddress($x, $y){

        $ch = curl_init();
        $url = 'http://api.haoservice.com/api/getLocationinfor?latlng='.$x.','.$y.'&type=2&key=9fba1a9b3c804d8680125836b560ea64';
        
        
        // 执行HTTP请求
        curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);

        $res_arr = json_decode($res, true);
        $addressData = $res_arr['result'];

        return $addressData;

    }

    /*
    * 获取广告信息
    * $c_id 广告所属分类id
    * $fields 要获取的字段信息
    */
    public function cake24_GetAdve($c_id,$fields=array(),$intro='',$model='',$limit='')
    {   
        if( !$c_id )
        {
            return false;
        }
        $this->loadModel("ItvAttribute");
        $this->loadModel("FirmItvDetails");
        $this->loadModel("Firms");
        if( $model )
        {
            $this->ItvAttribute->useDbConfig = $this->FirmItvDetails->useDbConfig = $this->Firms->useDbConfig = 'youjk';
        }
        $image_url = 'http://index.img.youjuke.com';
        $times = date('Y-m-d');
        $fields = array_merge($fields,array('itv_balance','total_cost','firm_id','is_itv','end_time'));
        $ItvAttribute = $this->ItvAttribute->findAsFirms('all',array(
                'conditions'=>array(
                    'status'=>1,
                    'delete'=>0,
                    'c_id' => $c_id,
                    'OR' => array(
                        array('is_itv'=>1,'start_time <='=>$times,'end_time >='=>$times),
                        array('is_itv'=>2)
                    )
                ),
                'fields' => $fields,
                'order' => array('order asc'),
                'limit' => $limit
            )
        );
        $data = array();
        foreach ($ItvAttribute as $key => $val) 
        {
            $val_key = $val['ItvAttribute'];
            //广告扣费
            if( $val_key['is_itv'] == 1 )
            {
                $this->FirmItvDetails->clear();
                //广告费未支付的扣除费用
                $is_exists = $this->FirmItvDetails->find('count',array(
                        'conditions'=>array('firm_id'=>$val_key['firm_id'],'itv_id'=>$val_key['id'],'renew_time'=>$val_key['end_time'])
                    )
                );
                if( !$is_exists )
                {
                    //余额充足扣除广告费并且显示 否则跳出不显示
                    if( $val_key['itv_balance'] >= $val_key['total_cost'] )
                    {
                        $dataSource = $this->FirmItvDetails->getDataSource();
                        $dataSource->begin();
                        $fee_time = date('Y-m-d H:i:s');
                        $det_data['firm_id'] = $val_key['firm_id'];
                        $det_data['itv_id'] = $val_key['id'];
                        $det_data['note'] = $intro.'-广告扣费';
                        $det_data['type'] = 2;
                        $det_data['amount'] = '-'.$val_key['total_cost'];
                        $det_data['add_time'] = $fee_time;
                        $det_data['renew_time'] = $val_key['end_time'];
                        if( !$this->FirmItvDetails->save( $det_data ) )
                        {
                            $dataSource->rollback();
                        }
                        else
                        {
                            $this->Firms->updateAll( array('itv_balance'=>'`itv_balance`-'.$val_key['total_cost']) , array('Firms.id'=>$val_key['firm_id']) );
                            $dataSource->commit();
                        }
                    }
                    else
                    {
                        continue;
                    }
                }
                
            }
            $c_id = $val_key['c_id'];
            $img = $image_url.'/'.$val_key['c_id'].'/';
            $val_key['image'] = $img.$val_key['image'];
            $val_key['firm_logo'] = $img.$val_key['firm_logo'];
            $data[ $c_id ][] = $val_key;
        }
        return $data;
    }


    /*
    * 根据面积 计算几室几厅
    */
    protected function cake24_baojia_budget($total_area, $info = false, $return_house_type = false )
    {
        $woshi = array();
        $keting = array();
        $chufang = array();
        $weishengjian = array();
        $yangtai = array();
        /*
          建筑面积*0.8=实际总面积
          60平米以下：1室1厅（厨房卫生间阳台固定4平米。卧室/厅=1/1）
          61-90：2室1厅（厨房卫生间阳台固定6平米。卧室1/卧室2/厅=3/2/3）
          91-110：3室1厅（厨房卫生间阳台固定8平米。卧室1/卧室2/卧室3/厅=3/2/2/3）
          111-130：3室2厅（厨房卫生间阳台固定8平米。卧室1/卧室2/卧室3/厅1/厅2=3/2/2/3/2）
          131-150：3室2厅2卫（厨房阳台固定面积8平米，卫生间6平米。卧室1/卧室2/卧室3/厅1/厅2=3/2/2/3/2）
          151-180：4室2厅1厨2卫1阳（厨房10平米，卫生间6平米，阳台8平米。卧室1/卧室2/卧室3/卧室4/厅1/厅2=3/2/2/2/3/2）
         */
        $real_area = $total_area * 0.8;
        if ($total_area > 0 && $total_area < 60) {
            $woshi["woshi_61"] = round(($real_area - 4 * 3) / 2, 1);
            $keting['keting_21'] = round(($real_area - 4 * 3) / 2, 1);
            $chufang['chufang_11'] = 4;
            $weishengjian['weishengjian_31'] = 4;
            $yangtai['yangtai_31'] = 4;
        } else if ($total_area >= 60 && $total_area < 90) {
            $woshi["woshi_61"] = round(($real_area - 6 * 3) * 3 / 8, 1);
            $woshi["woshi_62"] = round(($real_area - 6 * 3) * 2 / 8, 1);
            $keting['keting_21'] = round(($real_area - 6 * 3) * 3 / 8, 1);
            $chufang['chufang_11'] = 6;
            $weishengjian['weishengjian_31'] = 6;
            $yangtai['yangtai_31'] = 6;
        } else if ($total_area >= 90 && $total_area < 110) {
            $woshi["woshi_61"] = round(($real_area - 6 * 3) * 3 / 10, 1);
            $woshi["woshi_62"] = round(($real_area - 6 * 3) * 2 / 10, 1);
            $woshi["woshi_63"] = round(($real_area - 6 * 3) * 2 / 10, 1);
            $keting['keting_21'] = round(($real_area - 6 * 3) * 3 / 10, 1);
            $chufang['chufang_11'] = 8;
            $weishengjian['weishengjian_31'] = 8;
            $yangtai['yangtai_31'] = 8;
        } else if ($total_area >= 110 && $total_area < 130) {
            $woshi["woshi_61"] = round(($real_area - 6 * 3) * 3 / 12, 1);
            $woshi["woshi_62"] = round(($real_area - 6 * 3) * 2 / 12, 1);
            $woshi["woshi_63"] = round(($real_area - 6 * 3) * 2 / 12, 1);
            $keting['keting_21'] = round(($real_area - 6 * 3) * 3 / 12, 1);
            $keting['keting_22'] = round(($real_area - 6 * 3) * 2 / 12, 1);
            $chufang['chufang_11'] = 8;
            $weishengjian['weishengjian_31'] = 8;
            $yangtai['yangtai_31'] = 8;
        } else if ($total_area >= 130 && $total_area < 150) {
            $woshi["woshi_61"] = round(($real_area - 22) * 3 / 12, 1);
            $woshi["woshi_62"] = round(($real_area - 22) * 2 / 12, 1);
            $woshi["woshi_63"] = round(($real_area - 22) * 2 / 12, 1);
            $keting['keting_21'] = round(($real_area - 22) * 3 / 12, 1);
            $keting['keting_22'] = round(($real_area - 22) * 2 / 12, 1);
            $chufang['chufang_11'] = 8;
            $weishengjian['weishengjian_31'] = 6;
            $yangtai['yangtai_31'] = 8;
        } else if ($total_area >= 150) {
            $woshi["woshi_61"] = round(($real_area - 24) * 3 / 14, 1);
            $woshi["woshi_62"] = round(($real_area - 24) * 2 / 14, 1);
            $woshi["woshi_63"] = round(($real_area - 24) * 2 / 14, 1);
            $woshi["woshi_64"] = round(($real_area - 24) * 2 / 14, 1);
            $keting['keting_21'] = round(($real_area - 24) * 3 / 14, 1);
            $keting['keting_22'] = round(($real_area - 24) * 2 / 14, 1);
            $chufang['chufang_11'] = 10;
            $weishengjian['weishengjian_31'] = 6;
            $yangtai['yangtai_31'] = 8;
        }

        $budget = $woshi;
        $budget = array_merge($budget,$keting,$chufang,$weishengjian,$yangtai);
        $jine = $this->cake24_show_zxbj( $budget );
        if( $info ){

            return $jine;
        }

        if($return_house_type){

            $house_type = [

                'total_price'  => round($jine['total_price'], 2),
                'woshi'        => $woshi,
                'keting'       => $keting,
                'chufang'      => $chufang,
                'weishengjian' => $weishengjian,
                'yangtai'      => $yangtai,
            ];

            return $house_type;
        }
        return round($jine['total_price'], 2);
    }

    protected function cake24_show_zxbj( $budget=array() ){

        $this->layout = false;
        //房屋基础信息
        $i_p = 1; //总倍数
        //客厅  2
        $arr_keting = array();
        if (!empty($budget['keting_21'])) {
            $arr_keting[] = $budget['keting_21'];
        }
        if (!empty($budget['keting_22'])) {
            $arr_keting[] = $budget['keting_22'];
        }
        $c_kt = count($arr_keting);
        //$keting['keting_21'] = $budget['keting_21'];
        //$keting['keting_22'] = $budget['keting_22'];

        //卧室  6
        $arr_woshi = array();
        if (!empty($budget['woshi_61'])) {
            $arr_woshi[] = $budget['woshi_61'];
        }
        if (!empty($budget['woshi_62'])) {
            $arr_woshi[] = $budget['woshi_62'];
        }
        if (!empty($budget['woshi_63'])) {
            $arr_woshi[] = $budget['woshi_63'];
        }
        if (!empty($budget['woshi_64'])) {
            $arr_woshi[] = $budget['woshi_64'];
        }
        if (!empty($budget['woshi_65'])) {
            $arr_woshi[] = $budget['woshi_65'];
        }
        if (!empty($budget['woshi_66'])) {
            $arr_woshi[] = $budget['woshi_66'];
        }
        $c_ws = count($arr_woshi);

        //公用卫生间   3
        $arr_weishengjian = array();
        if (!empty($budget['weishengjian_31'])) {
            $arr_weishengjian[] = $budget['weishengjian_31'];
        }
        if (!empty($budget['weishengjian_32'])) {
            $arr_weishengjian[] = $budget['weishengjian_32'];
        }
        if (!empty($budget['weishengjian_33'])) {
            $arr_weishengjian[] = $budget['weishengjian_33'];
        }
        $c_wsj = count($arr_weishengjian);


        //厨房   1
        $arr_chufang = array();
        if (!empty($budget['chufang_11'])) {
            $arr_chufang[] = $budget['chufang_11'];
        }
        $c_cf = count($arr_chufang);

        //厅阳台   3
        $arr_yangtai = array();
        if (!empty($budget['yangtai_31'])) {
            $arr_yangtai[] = $budget['yangtai_31'];
        }
        if (!empty($budget['yangtai_32'])) {
            $arr_yangtai[] = $budget['yangtai_32'];
        }
        if (!empty($budget['yangtai_33'])) {
            $arr_yangtai[] = $budget['yangtai_33'];
        }
        $c_yt = count($arr_yangtai);

        //总面积
        $arr_areas = array_merge($arr_keting, $arr_woshi, $arr_weishengjian, $arr_chufang, $arr_yangtai);
        $areas = array_sum($arr_areas);
        //总价
        $total_price = 0;

        //家装基础施工报价清单
        //客厅工程
        $kt_sy = 0; //js索引初始值
        //$kt_total = 0;//客厅总价
        foreach ($arr_keting as $k => $val) {
            $arr_kt[$k]['sy'] = $kt_sy + $k; //js索引
            //墙面铲除墙面腻子层 qm_nzh
            $arr_kt[$k]['gcl']['qm_nzh'] = $val * 2.67;
            $arr_kt[$k]['gcj']['qm_nzh'] = $arr_kt[$k]['gcl']['qm_nzh'] * 7 * $i_p;
            $arr_kt[$k]['price'] += $arr_kt[$k]['gcj']['qm_nzh'];
            //墙面滚胶 qm_gj
            $arr_kt[$k]['gcl']['qm_gj'] = $val * 2.67;
            $arr_kt[$k]['gcj']['qm_gj'] = $arr_kt[$k]['gcl']['qm_gj'] * 6 * $i_p;
            $arr_kt[$k]['price'] += $arr_kt[$k]['gcj']['qm_gj'];
            //墙面批腻子 qm_pnz
            $arr_kt[$k]['gcl']['qm_pnz'] = $val * 2.67;
            $arr_kt[$k]['gcj']['qm_pnz'] = $arr_kt[$k]['gcl']['qm_pnz'] * 19 * $i_p;
            $arr_kt[$k]['price'] += $arr_kt[$k]['gcj']['qm_pnz'];
            //墙面手刷乳胶漆 qm_rjq（多乐士金装五合一，一底两面）
            $arr_kt[$k]['gcl']['qm_rjq'] = $val * 2.67;
            $arr_kt[$k]['gcj']['qm_rjq'] = $arr_kt[$k]['gcl']['qm_rjq'] * 17 * $i_p;
            $arr_kt[$k]['price'] += $arr_kt[$k]['gcj']['qm_rjq'];
            //顶面普通木龙骨石膏板吊顶 dm_sgb
            $arr_kt[$k]['gcl']['dm_sgb'] = $val * 0.4;
            $arr_kt[$k]['gcj']['dm_sgb'] = $arr_kt[$k]['gcl']['dm_sgb'] * 130 * $i_p;
            $arr_kt[$k]['price'] += $arr_kt[$k]['gcj']['dm_sgb'];
            //顶面铲除顶面腻子层 dm_nzh
            $arr_kt[$k]['gcl']['dm_nzh'] = $val * 1.33;
            $arr_kt[$k]['gcj']['dm_nzh'] = $arr_kt[$k]['gcl']['dm_nzh'] * 7 * $i_p;
            $arr_kt[$k]['price'] += $arr_kt[$k]['gcj']['dm_nzh'];
            //顶面滚胶 dm_gj
            $arr_kt[$k]['gcl']['dm_gj'] = $val * 1.33;
            $arr_kt[$k]['gcj']['dm_gj'] = $arr_kt[$k]['gcl']['dm_gj'] * 6 * $i_p;
            $arr_kt[$k]['price'] += $arr_kt[$k]['gcj']['dm_gj'];
            //顶面批腻子 dm_pnz
            $arr_kt[$k]['gcl']['dm_pnz'] = $val * 1.33;
            $arr_kt[$k]['gcj']['dm_pnz'] = $arr_kt[$k]['gcl']['dm_pnz'] * 19 * $i_p;
            $arr_kt[$k]['price'] += $arr_kt[$k]['gcj']['dm_pnz'];
            //顶面手刷乳胶漆 dm_rjq（金装五合一，一底两面）
            $arr_kt[$k]['gcl']['dm_rjq'] = $val * 1.33;
            $arr_kt[$k]['gcj']['dm_rjq'] = $arr_kt[$k]['gcl']['dm_rjq'] * 17 * $i_p;
            $arr_kt[$k]['price'] += $arr_kt[$k]['gcj']['dm_rjq'];

            $total_price += $arr_kt[$k]['price'];
        }
        

        //卧室工程
        $ws_sy = $c_kt;
        //$ws_total = 0;//卧室总价
        foreach ($arr_woshi as $k => $val) {
            $arr_ws[$k]['sy'] = $ws_sy + $k; //js索引
            //墙面铲除墙面腻子层 qm_nzh
            $arr_ws[$k]['gcl']['qm_nzh'] = $val * 2.5;
            $arr_ws[$k]['gcj']['qm_nzh'] = $arr_ws[$k]['gcl']['qm_nzh'] * 7 * $i_p;
            $arr_ws[$k]['price'] += $arr_ws[$k]['gcj']['qm_nzh'];
            //墙面滚胶 qm_gj
            $arr_ws[$k]['gcl']['qm_gj'] = $val * 2.5;
            $arr_ws[$k]['gcj']['qm_gj'] = $arr_ws[$k]['gcl']['qm_gj'] * 6 * $i_p;
            $arr_ws[$k]['price'] += $arr_ws[$k]['gcj']['qm_gj'];
            //墙面批腻子 qm_pnz
            $arr_ws[$k]['gcl']['qm_pnz'] = $val * 2.5;
            $arr_ws[$k]['gcj']['qm_pnz'] = $arr_ws[$k]['gcl']['qm_pnz'] * 19 * $i_p;
            $arr_ws[$k]['price'] += $arr_ws[$k]['gcj']['qm_pnz'];
            //墙面手刷乳胶漆 qm_nzc
            $arr_ws[$k]['gcl']['qm_nzc'] = $val * 2.5;
            $arr_ws[$k]['gcj']['qm_nzc'] = $arr_ws[$k]['gcl']['qm_nzc'] * 17 * $i_p;
            $arr_ws[$k]['price'] += $arr_ws[$k]['gcj']['qm_nzc'];
            //顶面铲除顶面腻子层 dm_nzc
            $arr_ws[$k]['gcl']['dm_nzc'] = $val * 1;
            $arr_ws[$k]['gcj']['dm_nzc'] = $arr_ws[$k]['gcl']['dm_nzc'] * 7 * $i_p;
            $arr_ws[$k]['price'] += $arr_ws[$k]['gcj']['dm_nzc'];
            //顶面滚胶 dm_gj
            $arr_ws[$k]['gcl']['dm_gj'] = $val * 1;
            $arr_ws[$k]['gcj']['dm_gj'] = $arr_ws[$k]['gcl']['dm_gj'] * 6 * $i_p;
            $arr_ws[$k]['price'] += $arr_ws[$k]['gcj']['dm_gj'];
            //顶面批腻子 dm_pnz
            $arr_ws[$k]['gcl']['dm_pnz'] = $val * 1;
            $arr_ws[$k]['gcj']['dm_pnz'] = $arr_ws[$k]['gcl']['dm_pnz'] * 19 * $i_p;
            $arr_ws[$k]['price'] += $arr_ws[$k]['gcj']['dm_pnz'];
            //顶面手刷乳胶漆 dm_rjq
            $arr_ws[$k]['gcl']['dm_rjq'] = $val * 1;
            $arr_ws[$k]['gcj']['dm_rjq'] = $arr_ws[$k]['gcl']['dm_rjq'] * 17 * $i_p;
            $arr_ws[$k]['price'] += $arr_ws[$k]['gcj']['dm_rjq'];
            //门套打底（宽度≤200mm） mt_dd
            $arr_ws[$k]['gcl']['mt_dd'] = $val * 0.5;
            $arr_ws[$k]['gcj']['mt_dd'] = $arr_ws[$k]['gcl']['mt_dd'] * 43 * $i_p;
            $arr_ws[$k]['price'] += $arr_ws[$k]['gcj']['mt_dd'];
            //大理石窗台板(宽度≤600-900MM) dls_ctb
            $arr_ws[$k]['gcl']['dls_ctb'] = $val * 0.2;
            $arr_ws[$k]['gcj']['dls_ctb'] = $arr_ws[$k]['gcl']['dls_ctb'] * 230 * $i_p;
            $arr_ws[$k]['price'] += $arr_ws[$k]['gcj']['dls_ctb'];

            $total_price += $arr_ws[$k]['price'];
        }
        //---卧室1
        
        //公用卫生间工程
        $wsj_sy = $c_kt + $c_ws;
        //$wsj_total = 0;//卫生间总价
        foreach ($arr_weishengjian as $k => $val) {
            $arr_wsj[$k]['sy'] = $wsj_sy + $k; //js索引
            //地面做二遍防水处理 dm_fscl
            $arr_wsj[$k]['gcl']['dm_fscl'] = $val * 4;
            $arr_wsj[$k]['gcj']['dm_fscl'] = $arr_wsj[$k]['gcl']['dm_fscl'] * 55 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['dm_fscl'];
            //铺贴地砖不拼花（含填缝） pt_dzb
            $arr_wsj[$k]['gcl']['pt_dzb'] = $val * 1;
            $arr_wsj[$k]['gcj']['pt_dzb'] = $arr_wsj[$k]['gcl']['pt_dzb'] * 77 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['pt_dzb'];
            //地面找平（30mm内） dm_zp
            $arr_wsj[$k]['gcl']['dm_zp'] = $val * 1;
            $arr_wsj[$k]['gcj']['dm_zp'] = $arr_wsj[$k]['gcl']['dm_zp'] * 60 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['dm_zp'];
            //淋浴房挡水槛（浅色，含磨边） lyf_dsk
            $arr_wsj[$k]['gcl']['lyf_dsk'] = $val * 0.38;
            $arr_wsj[$k]['gcj']['lyf_dsk'] = $arr_wsj[$k]['gcl']['lyf_dsk'] * 150 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['lyf_dsk'];
            //大理石门槛石宽度≤240MM dls_mks
            $arr_wsj[$k]['gcl']['dls_mks'] = $val * 0.25;
            $arr_wsj[$k]['gcj']['dls_mks'] = $arr_wsj[$k]['gcl']['dls_mks'] * 115 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['dls_mks'];
            //铺贴墙砖不拼花（含填缝） pt_qzb
            $arr_wsj[$k]['gcl']['pt_qzb'] = $val * 3.8;
            $arr_wsj[$k]['gcj']['pt_qzb'] = $arr_wsj[$k]['gcl']['pt_qzb'] * 73 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['pt_qzb'];
            //墙面磁砖倒角加工费 qm_czdj
            $arr_wsj[$k]['gcl']['qm_czdj'] = $val * 3;
            $arr_wsj[$k]['gcj']['qm_czdj'] = $arr_wsj[$k]['gcl']['qm_czdj'] * 20 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['qm_czdj'];
            //门套打底（宽度≤200mm） mt_dd
            $arr_wsj[$k]['gcl']['mt_dd'] = $val * 1.25;
            $arr_wsj[$k]['gcj']['mt_dd'] = $arr_wsj[$k]['gcl']['mt_dd'] * 43 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['mt_dd'];
            //地漏及安装 dl_az
            $arr_wsj[$k]['gcl']['dl_az'] = $val * 0.5;
            $arr_wsj[$k]['gcj']['dl_az'] = $arr_wsj[$k]['gcl']['dl_az'] * 88 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['dl_az'];
            //安装成品台盆柜（含面盆、面盆龙头） az_tpg
            $arr_wsj[$k]['gcl']['az_tpg'] = $val * 0.25;
            $arr_wsj[$k]['gcj']['az_tpg'] = $arr_wsj[$k]['gcl']['az_tpg'] * 80 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['az_tpg'];
            //冷热水龙头安装（面盆/浴缸/淋浴/洗衣机） lr_slt
            $arr_wsj[$k]['gcl']['lr_slt'] = $val * 0.25;
            $arr_wsj[$k]['gcj']['lr_slt'] = $arr_wsj[$k]['gcl']['lr_slt'] * 80 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['lr_slt'];
            //浴室浴配件安装（毛巾杆、浴巾架等） ys_ypj
            $arr_wsj[$k]['gcl']['ys_ypj'] = $val * 0.25;
            $arr_wsj[$k]['gcj']['ys_ypj'] = $arr_wsj[$k]['gcl']['ys_ypj'] * 100 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['ys_ypj'];
            //三角阀+软管（套） sjf_rg
            $arr_wsj[$k]['gcl']['sjf_rg'] = $val * 0.75;
            $arr_wsj[$k]['gcj']['sjf_rg'] = $arr_wsj[$k]['gcl']['sjf_rg'] * 60 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['sjf_rg'];
            //安装马桶 az_mt
            $arr_wsj[$k]['gcl']['az_mt'] = $val * 0.25;
            $arr_wsj[$k]['gcj']['az_mt'] = $arr_wsj[$k]['gcl']['az_mt'] * 75 * $i_p;
            $arr_wsj[$k]['price'] += $arr_wsj[$k]['gcj']['az_mt'];

            $total_price += $arr_wsj[$k]['price'];
        }
        
        //厨房工程 总:1
        $cf_sy = $c_kt + $c_ws + $c_wsj;
        //$cf_total = 0;//卧室总价
        foreach ($arr_chufang as $k => $val) {
            $arr_cf[$k]['sy'] = $cf_sy + $k; //js索引
            //铺贴地砖不拼花（含填缝） pt_dz
            $arr_cf[$k]['gcl']['pt_dz'] = $val * 1;
            $arr_cf[$k]['gcj']['pt_dz'] = $arr_cf[$k]['gcl']['pt_dz'] * 77 * $i_p;
            $arr_cf[$k]['price'] += $arr_cf[$k]['gcj']['pt_dz'];
            //地面找平（30mm内） dm_zp
            $arr_cf[$k]['gcl']['dm_zp'] = $val * 1;
            $arr_cf[$k]['gcj']['dm_zp'] = $arr_cf[$k]['gcl']['dm_zp'] * 60 * $i_p;
            $arr_cf[$k]['price'] += $arr_cf[$k]['gcj']['dm_zp'];
            //大理石门槛石宽度≤240MM dls_mk
            $arr_cf[$k]['gcl']['dls_mk'] = $val * 0.2;
            $arr_cf[$k]['gcj']['dls_mk'] = $arr_cf[$k]['gcl']['dls_mk'] * 115 * $i_p;
            $arr_cf[$k]['price'] += $arr_cf[$k]['gcj']['dls_mk'];
            //铺贴墙砖不拼花（含填缝） pt_qz
            $arr_cf[$k]['gcl']['pt_qz'] = $val * 3.8;
            $arr_cf[$k]['gcj']['pt_qz'] = $arr_cf[$k]['gcl']['pt_qz'] * 73 * $i_p;
            $arr_cf[$k]['price'] += $arr_cf[$k]['gcj']['pt_qz'];
            //磁砖倒角加工费 cz_djjgf
            $arr_cf[$k]['gcl']['cz_djjgf'] = $val * 2.4;
            $arr_cf[$k]['gcj']['cz_djjgf'] = $arr_cf[$k]['gcl']['cz_djjgf'] * 20 * $i_p;
            $arr_cf[$k]['price'] += $arr_cf[$k]['gcj']['cz_djjgf'];
            //门套打底（宽度≤200mm） mt_dd
            $arr_cf[$k]['gcl']['mt_dd'] = $val * 1;
            $arr_cf[$k]['gcj']['mt_dd'] = $arr_cf[$k]['gcl']['mt_dd'] * 43 * $i_p;
            $arr_cf[$k]['price'] += $arr_cf[$k]['gcj']['mt_dd'];
            //厨房水槽安装（含冷热水龙头安装） cf_scaz
            $arr_cf[$k]['gcl']['cf_scaz'] = $val * 0.2;
            $arr_cf[$k]['gcj']['cf_scaz'] = $arr_cf[$k]['gcl']['cf_scaz'] * 45 * $i_p;
            $arr_cf[$k]['price'] += $arr_cf[$k]['gcj']['cf_scaz'];
            //三角阀+软管（套） sjf_rg
            $arr_cf[$k]['gcl']['sjf_rg'] = $val * 0.8;
            $arr_cf[$k]['gcj']['sjf_rg'] = $arr_cf[$k]['gcl']['sjf_rg'] * 60 * $i_p;
            $arr_cf[$k]['price'] += $arr_cf[$k]['gcj']['sjf_rg'];

            $total_price += $arr_cf[$k]['price'];
        }
        
        //厅阳台工程
        $yt_sy = $c_kt + $c_ws + $c_wsj + $c_cf;
        $yt_total = 0; //厅阳台总价
        foreach ($arr_yangtai as $k => $val) {
            $arr_yt[$k]['sy'] = $yt_sy + $k; //js索引
            //铺贴地砖不拼花（含填缝，单边≥200mm） pt_dz
            $arr_yt[$k]['gcl']['pt_dz'] = $val * 1;
            $arr_yt[$k]['gcj']['pt_dz'] = $arr_yt[$k]['gcl']['pt_dz'] * 77 * $i_p;
            $arr_yt[$k]['price'] += $arr_yt[$k]['gcj']['pt_dz'];
            //地面找平（30mm内） dm_zp
            $arr_yt[$k]['gcl']['dm_zp'] = $val * 1;
            $arr_yt[$k]['gcj']['dm_zp'] = $arr_yt[$k]['gcl']['dm_zp'] * 60 * $i_p;
            $arr_yt[$k]['price'] += $arr_yt[$k]['gcj']['dm_zp'];
            //大理石门槛石宽度≤240MM dls_mk
            $arr_yt[$k]['gcl']['dls_mk'] = $val * 0.63;
            $arr_yt[$k]['gcj']['dls_mk'] = $arr_yt[$k]['gcl']['dls_mk'] * 115 * $i_p;
            $arr_yt[$k]['price'] += $arr_yt[$k]['gcj']['dls_mk'];
            //铺贴墙砖不拼花（含填缝，单边≥200mm） pt_qz
            $arr_yt[$k]['gcl']['pt_qz'] = $val * 2;
            $arr_yt[$k]['gcj']['pt_qz'] = $arr_yt[$k]['gcl']['pt_qz'] * 73 * $i_p;
            $arr_yt[$k]['price'] += $arr_yt[$k]['gcj']['pt_qz'];
            //顶面批腻子 dm_pnz
            $arr_yt[$k]['gcl']['dm_pnz'] = $val * 1;
            $arr_yt[$k]['gcj']['dm_pnz'] = $arr_yt[$k]['gcl']['dm_pnz'] * 19 * $i_p;
            $arr_yt[$k]['price'] += $arr_yt[$k]['gcj']['dm_pnz'];
            //顶面手刷乳胶漆（金装五合一，一底两面） dm_rjq
            $arr_yt[$k]['gcl']['dm_rjq'] = $val * 1;
            $arr_yt[$k]['gcj']['dm_rjq'] = $arr_yt[$k]['gcl']['dm_rjq'] * 17 * $i_p;
            $arr_yt[$k]['price'] += $arr_yt[$k]['gcj']['dm_rjq'];

            $total_price += $arr_yt[$k]['price'];
        }
        
        //水电及其安装
        $sd_sy = $c_kt + $c_ws + $c_wsj + $c_cf + $c_yt;
        $total_area = $areas;
        $shuidian_price = 0;
        $shuidian_gcl = array();
        $shuidian_gcj = array();

        //电话线 sd_dhx
        $shuidian_gcl['sd_dhx'] = $total_area * 0.25;
        $shuidian_gcj['sd_dhx'] = $shuidian_gcl['sd_dhx'] * 4 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_dhx'];
        //网络线 sd_wlx
        $shuidian_gcl['sd_wlx'] = $total_area * 0.75;
        $shuidian_gcj['sd_wlx'] = $shuidian_gcl['sd_wlx'] * 5.3 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_wlx'];
        //弱电全套开关/插座（地插价格另计） sd_rdkg
        $shuidian_gcl['sd_rdkg'] = $total_area * 0.11;
        $shuidian_gcj['sd_rdkg'] = $shuidian_gcl['sd_rdkg'] * 45 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_rdkg'];
        //视频线 sd_spx
        $shuidian_gcl['sd_spx'] = $total_area * 0.63;
        $shuidian_gcj['sd_spx'] = $shuidian_gcl['sd_spx'] * 7.3 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_spx'];
        //安装弱电箱 sd_rdx
        $shuidian_gcl['sd_rdx'] = $total_area * 0.01;
        $shuidian_gcj['sd_rdx'] = $shuidian_gcl['sd_rdx'] * 330 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_rdx'];
        //6分PVC线管（含管件） sd_pvc
        $shuidian_gcl['sd_pvc'] = $total_area * 3.13;
        $shuidian_gcj['sd_pvc'] = $shuidian_gcl['sd_pvc'] * 5.3 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_pvc'];
        //1.5㎡电线 sd_15dx
        $shuidian_gcl['sd_15dx'] = $total_area * 5.75;
        $shuidian_gcj['sd_15dx'] = $shuidian_gcl['sd_15dx'] * 4 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_15dx'];
        //2.5㎡电线 sd_25dx
        $shuidian_gcl['sd_25dx'] = $total_area * 6.25;
        $shuidian_gcj['sd_25dx'] = $shuidian_gcl['sd_25dx'] * 4.9 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_25dx'];
        //4㎡电线 sd_4dx
        $shuidian_gcl['sd_4dx'] = $total_area * 0.5;
        $shuidian_gcj['sd_4dx'] = $shuidian_gcl['sd_4dx'] * 7.3 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_4dx'];
        //安装强电箱箱体12路 sd_qdx
        $shuidian_gcl['sd_qdx'] = $total_area * 0.01;
        $shuidian_gcj['sd_qdx'] = $shuidian_gcl['sd_qdx'] * 400 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_qdx'];
        //强电开关/插座（地插价格另计） sd_qdkg
        $shuidian_gcl['sd_qdkg'] = $total_area * 0.56;
        $shuidian_gcj['sd_qdkg'] = $shuidian_gcl['sd_qdkg'] * 25 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_qdkg'];
        //开关插座暗盒 sd_kgah
        $shuidian_gcl['sd_kgah'] = $total_area * 0.68;
        $shuidian_gcj['sd_kgah'] = $shuidian_gcl['sd_kgah'] * 8 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_kgah'];
        //安装灯头盒 sd_azdth
        $shuidian_gcl['sd_azdth'] = $total_area * 0.38;
        $shuidian_gcj['sd_azdth'] = $shuidian_gcl['sd_azdth'] * 5.5 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_azdth'];
        //冷热水管 sd_lrsg
        $shuidian_gcl['sd_lrsg'] = $total_area * 0.63;
        $shuidian_gcj['sd_lrsg'] = $shuidian_gcl['sd_lrsg'] * 45 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_lrsg'];
        //灯具安装 sd_djaz
        $shuidian_gcl['sd_djaz'] = $total_area * 0.01;
        $shuidian_gcj['sd_djaz'] = $shuidian_gcl['sd_djaz'] * 400 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_djaz'];
        //砖墙管线开槽（含修粉） sd_qzgx
        $shuidian_gcl['sd_qzgx'] = $total_area * 1;
        $shuidian_gcj['sd_qzgx'] = $shuidian_gcl['sd_qzgx'] * 10.5 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_qzgx'];
        //钢筋混凝土墙面凿槽（含修粉） sd_gjhyt
        $shuidian_gcl['sd_gjhyt'] = $total_area * 0.38;
        $shuidian_gcj['sd_gjhyt'] = $shuidian_gcl['sd_gjhyt'] * 23 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_gjhyt'];
        //机器开孔（空调/脱排/浴霸） sd_jqkk
        $shuidian_gcl['sd_jqkk'] = $total_area * 0.06;
        $shuidian_gcj['sd_jqkk'] = $shuidian_gcl['sd_jqkk'] * 40 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_jqkk'];
        //杂物费（按建筑面积计算） sd_zwf
        $shuidian_gcl['sd_zwf'] = $total_area * 0.01;
        $shuidian_gcj['sd_zwf'] = $shuidian_gcl['sd_zwf'] * 250 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_zwf'];
        //垃圾清运费（新房，按建筑面积计算） sd_ljqyf
        $shuidian_gcl['sd_ljqyf'] = $total_area * 0.73;
        $shuidian_gcj['sd_ljqyf'] = $shuidian_gcl['sd_ljqyf'] * 6 * $i_p;
        $shuidian_price += $shuidian_gcj['sd_ljqyf'];

        $total_price += $shuidian_price;

        return array('total_price'=>$total_price,'arr_kt'=>$arr_kt,'arr_ws'=>$arr_ws,'arr_wsj'=>$arr_wsj,'arr_cf'=>$arr_cf,'arr_yt'=>$arr_yt,'arr_sd'=>$shuidian_price, 'sd_data' => [ 'shuidian_gcl' => $shuidian_gcl, 'shuidian_gcj' => $shuidian_gcj ]);

    }




    ############################ U_DECRYPT ############################

    # 加密
    protected function U_ENCRYPT($mobile) {
        return U_ENCRYPT($mobile);
        
        // $wMobile = array(
        //     '18601693057', # l
        //     '13918817036', # m
        //     '15001793098', # z
        //     '13611849697', # h
        //     '18605623290', # c
        //     '17051210248'  # f
        // );
        // if (in_array($mobile, $wMobile)) {
        //     return U_ENCRYPT($mobile);
        // }
        // return $mobile;
    }

    # 解密
    protected function U_DECRYPT($mobile) {
        if (strlen($mobile) == 16) {
            if (!empty($_SESSION['u_id'])) {
                $uid = $_SESSION['u_id'];
            } else if (!empty($_SESSION['uid'])) {
                $uid = $_SESSION['uid'];
            } else {
                $uid = 0;
            }
            @$this->get_decrypt_logs($uid,$mobile); #传当前登录的用户ID
            return U_DECRYPT($mobile); # 新存入字段 解密后返回
        } else {
            return $mobile; # 旧存入字段 直接返回
        }
    }

    # 调用解密方法记录日志
    private function get_decrypt_logs($uid = null,$mobile) {
        $project = $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : '';
        $u_controller = $this->params['controller'];
        $u_action = $this->params['action'];
        $u_ip = $this->cake24_getRealIp();
        $posttime = date("Y-m-d H:i:s");

        $data_array = array(
            'project'       => $project ? $project : '',
            'controller'    => $u_controller ? $u_controller : '',
            'action'        => $u_action ? $u_action : '',
            'uid'           => $uid ? $uid : 0,
            'mobile'        => $mobile,
            'ip'            => $u_ip ? $u_ip : '',
            'machine_id'    => '-1', # 设备号
            'session_data'  => '--',
            'cookie_data'   => '--',
            'posttime'      => $posttime
        );
        $this->Redis->push('decrypt_logs_list', json_encode($data_array), true);
    }

    # 事务系统主管评分加密
    public function U_ENCRYPT_WITH_KEY($score) {
        return U_ENCRYPT_WITH_KEY($score);

    }

    # 事务系统主管评分解密 c:\cryptor.txt
    public function U_DECRYPT_WITH_KEY($crypt_str, $key) {
        if (strlen($crypt_str) != 12){
            return false;
        }

        return U_DECRYPT_WITH_KEY($crypt_str, $key); # 新存入字段 解密后返回
        
    }

    # 保存明文
    public function U_SAVE_BM($bmid, $mobile) {
        $this->loadModel('BmLog');
        $data['baoming_id'] = $bmid;
        $data['mobile'] = $mobile;
        return $this->BmLog->save($data);
    }

    /**
      *获取当前页面报名人数(数据会有75秒的延迟)
      *该方法已被 function cake24_get_baomingNum() 替代
      *暂时废止
      *2017-03-13
      **/
    public function cake24_get_baoming_num($utm_page, $overTime = 75){
        
        if(empty($utm_page)){return 0;}

        $conditions = array('GlobalConfig.category_id' => 1); //1-报名页面来源
        $utmPageArr = $this->GlobalConfig->find('list', array(
            'conditions' => $conditions, 
            'fields' => array('GlobalConfig.short', 'GlobalConfig.name'))
        );

        //判断redis calss 是否存在
        $redisExist = $this->cake24_check_modelExist($this->Redis, 'get', NULL);
        if(!$redisExist){ return ; }

        //获取报名户型设计数量
        if ($this->Redis->get('UTMPAGE_'.$utm_page)) {
            $baoming_num = $this->Redis->get('UTMPAGE_'.$utm_page);
        } else {  
            
            $conditions = array('Baoming.utm_page LIKE'=>$utmPageArr[$utm_page].'%');
            $baoming_num = $this->Baoming->find('count',array('conditions'=>$conditions));
            $this->Redis->setex('UTMPAGE_'.$utm_page, $overTime, $baoming_num);
        }

        $this->set('baoming_num', $baoming_num);
    }

    /** 
      *-------------------------------------------------------------------------------
      * @获取报名数量
      * @注: 当参数错误、输入的日期格式不正确和找不到$type类型时，统一返回 0
      *-------------------------------------------------------------------------------
      * @param $type    utm_page(获取指定页面报名数量)，all(获取当前所有的报名数量)，date(获取具体日期的报名数量)，page_date (指定页面当天的报名量), month（获取具体月份的报名数量）
      * @param $param   具体的数据
      * @param $fix_num 需要填充的数量
      * @param $return  是否返回数据  false (不返回数据，直接定义变量) ， true （返回数据）
      * @param $overTime redis信息更新时间 默认为75 S
      * @return  $baoming_num (报名数量) 直接定义变量
      * @author 120291704@qq.com
      * @create date  2017-12-08
      * @update date  2018-10-09
      **/
    public function cake24_get_baomingNum($param, $type = 'utm_page', $fix_num = 0, $return = false, $overTime = 75){

        unset($baoming_num);
        if(empty($param)){ return 0; }

        //判断当前Model是否存在
        $configExist = $this->cake24_check_modelExist($this->GlobalConfig);
        if(!$configExist){ return 0; }

        $conditions = array('GlobalConfig.category_id' => 1); //1-报名页面来源
        $utmPageArr = $this->GlobalConfig->find('list', array(
            'conditions' => $conditions, 
            'fields' => array('GlobalConfig.short', 'GlobalConfig.name'))
        );
        $this->_cake24_disconnectMysql();

        //分析数据类型
        switch ($type) {
            case 'utm_page'://获取指定页面来源的报名数量
                $key = 'GET_UTMPAGE_'.$param;
                $conditions = array('`utm_page`'=>$utmPageArr[$param]);
                break;

            case 'all'://获取所有数据的报名数量
                $key = 'GET_'.strtoupper($param);
                $conditions = [];
                break;

            case 'date'://获取指定日期的报名数量
                $is_date = $this->cake24_check_date($param);//判断是否是日期格式
                if($is_date){

                    $key = 'GET_DATE_'.$param;
                    $conditions = array('DATE_FORMAT(`addtime`,\'%Y-%m-%d\')' => $param);

                }else{//如果日期格式不对则返回0

                    return 0;
                }
                break;
            case 'page_date'://指定页面当天的报名量
                $key = 'GET_PAGE_DATE_'.strtoupper($param);
                $conditions = array(
                    'DATE_FORMAT(`addtime`,\'%Y-%m-%d\')' => date('Y-m-d'),
                    '`utm_page`'=> $utmPageArr[$param]
                );
                break;
            case 'month'://获取指定月份的报名数量 （暫時不用）
                $is_month = $this->cake24_check_date($param);//判断是否是日期格式
                if($is_month){

                    $key = 'GET_MONTH_'.$param;
                    $conditions = array('DATE_FORMAT(`addtime`,\'%Y-%m\')' => $param);
                }else{//如果月份格式不对则返回0

                    return 0;
                }
                break;

            
            default:
                return 0;//默认返回0
                break;
        }

        //判断redis calss 是否存在
        $redisExist = $this->cake24_check_modelExist($this->Redis, 'get', NULL);
        if(!$redisExist){ return 0; }

        //获取各类型的报名数量
        if ($this->Redis->get($key)) {
            $baoming_num = $this->Redis->get($key);
        } else {
            
            //判断当前Model是否存在
            $baomingExist = $this->cake24_check_modelExist($this->Baoming);
            if(!$baomingExist){ return 0; }
            $baoming_num = $this->Baoming->find('count',array('conditions' => $conditions));
            $this->_cake24_disconnectMysql();
            $this->Redis->setex($key, $overTime, $baoming_num);
        }

        unset($conditions);

        //如果需要填充的数量部位0 则在原基础的数据上加上需要填充的数据
        if($fix_num != 0 && is_int($fix_num)){ $baoming_num += $fix_num; }

        if($return){

            return $baoming_num;
        }else{

            $this->set('baoming_num', $baoming_num);
        }

        
    }

    /** 
      * @判断日期格式时候正确
      * 功能 判断日期格式是否正确
      * 参数 $str 日期字符串, $format 日期格式
      * $format 支持 Y-m-d 和 Y/m/d 两种格式 Ymd暂时不兼容
      * 注意 ： 在判斷月份的時候$format 格式必須也只是 Y-m-d 和 Y/m/d 不能寫成  Y-m 和 Y/m
      * @return  true 格式正确 ， false 格式错误
      * @author dtz 2017-03-13
      **/
    protected function cake24_check_date($str, $format='Y-m-d'){
        
        
        $date_type = preg_replace('/[\.a-zA-Z]/s', '', $format);//去除大小寫字母

        //$date_type[0] 的值为 - 或者 /
        //计算 $date_type[0] 在字符串中的次数 （如果是日期格式的话 $date_type[0] 符号不会超过2个，如果超过2个则直接返回 false）
        $substr_count = substr_count($str, $date_type[0]);
        if(in_array($substr_count, array(1, 2))){

            if($substr_count == 1){ $str .= $date_type[0].'01'; }
        }else{

            return false;
        }

        $unixTime  = strtotime($str);
        if(!is_numeric($unixTime)) return false; //如果不是数字格式，则直接返回 

        $checkDate = date($format, $unixTime);
        $checktime = strtotime($checkDate);
        if($unixTime == $checktime){
            return true;
        }else{ 
            return false; 
        } 
    } 

    /** 
      * @屏蔽掉局域网、外测和系统配置ip
      * @par $ip  当前ip地址
      * @return  true 当前ip需要屏蔽 ， false 当前ip不需要屏蔽
      * @author dtz 2017-03-06
      **/
    protected function cake24_ignore_ip($ip){

        //判断是否是合法的公共IPv4地址  
        if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE)){

            return true;//局域网的ip需要屏蔽
        }

        $this->loadModel("ApplicationConfig");
        $ipData = $this->ApplicationConfig->find('list',array(
                'conditions'=>array(
                    'project'=>array('now_ipaddress','budding_ipaddress')
                ),
                'fields' => array('text','text')
            )
        );
        
        $check_ip_arr = explode('.', $ip);//要检测的ip拆分成数组
        $ipArr  = ['116.62.40.158','116.62.109.118','59.37.97.109'];//公司外测的ip也可以添加局域网ip段，但如果要加局域网ip段的话就和代码功能重复，在此加局域网ip段需要注释掉 59.37.97.109=>广点通
        $ignoreIpArr  = array_merge(array_values($ipData), $ipArr);
        
        if(!in_array($ip, $ignoreIpArr)){

            foreach ($ignoreIpArr as $key => $value) {
                
                if(strpos($value, '*') !== false ){//发现有*号替代符

                    $arr = explode('.', $value);
                    $bl = true;//用于记录循环检测中是否有匹配成功的

                    foreach ($arr as $k => $val) {
                        if($val != '*'){//不等于* 就要进来检测，如果为*符号替代符就不检查

                            if($val != $check_ip_arr[$k]){
                                $bl = false;
                                break;//终止检查本个ip 继续检查下一个ip
                            }
                        }
                    }
                }
            }

        }else{

            $bl = true;
        }

        return $bl;
    }


    /** 
      * @报名所必须依赖的models
      * @author dtz 2017-08-02
      **/
    private $_cake24_bm_basic_model = ['Baomings', 'Cities', 'JcSetPeriod', 'BaomingsPlus', 'BaomingService', 'RejectLogs', 'ResetLogs', 'BaomingSources', 'Sources', 'BaomingCxfp'];

    /**
    * 获取各项目的名称定义（目前支持 youjuke.com、360youjuke.com、youjistyle.com域名）
    * @author 120170714@qq.com
    * @create date  2018-04-09
    * @update date  2018-10-10
    */
    private $_cake24_getchannelname = [

        '51youjuke' => [//开发环境或外测

            'api'    => 'API',
            'm'      => '手机',
            'gongsi' => '公司',
            'fuwu'   => '服务',
            'gongdi' => '工地',
            'mall'   => '商城',
            'riji'   => '日志',
            'www'    => '首页',
            'tuku'   => '图库',
            'zixun'  => '资讯',
            '360'    => '360',
            'preapi'    => 'API',
            'prem'      => '手机',
            'pregongsi' => '公司',
            'prefuwu'   => '服务',
            'pregongdi' => '工地',
            'premall'   => '商城',
            'preriji'   => '日志',
            'pre'       => '首页',
            'pretuku'   => '图库',
            'prezixun'  => '资讯',
            'pre360'    => '360',
            'preyouji'  => '优几PC',//优几index外测
            'premyouji' => '优几MOBILE' //优几mobile外测
        ],
        '360youjuke' => [//线上360

            'm'      => '360'
        ],
        'youjuke'    => [//线上

            'api'    => 'API',
            'm'      => '手机',
            'gongsi' => '公司',
            'fuwu'   => '服务',
            'gongdi' => '工地',
            'mall'   => '商城',
            'riji'   => '日志',
            'www'    => '首页',
            'tuku'   => '图库',
            'zixun'  => '资讯'
        ],
        '51youjistyle'  => [//优几(index和mobile项目)本地

            'm'         => '优几MOBILE',//本地
            'www'       => '优几PC'     //本地

        ],
        'youjistyle' => [//优几(index和mobile项目)线上

            'm'      => '优几MOBILE',
            'www'    => '优几PC'
        ],
        
    ];
    

    /** 
      * @判断所有依赖的Models是否存在
      * @par $modelArr array
      * @return  array
      * @author dtz 2017-08-02
      **/
    protected function cake24_check_modelArrExist($modelArr){

        if(!empty($modelArr) && is_array($modelArr)){

            foreach ($modelArr as $key => $model) {
                
                $isExist = $this->cake24_check_modelExist($this->$model);
                if(!$isExist){

                    return ['status' => false, 'errorMsg' => '缺少报名依赖的model：'.$model];
                }
            }

            return ['status' => true];

        }else{

            return ['status' => false, 'errorMsg' => '缺少报名依赖的model'];
        }
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

    /** 
      * @获取客服热线电话（方便统一调用）
      * @par $return 是否返回数据  false (不返回数据，直接定义变量) ， true （返回数据）
      * @par $overTime redis信息更新时间 默认为75 S
      * @return  客服热线电话号码
      * @author dtz 2017-05-08
      * @update 2017-11-15
      **/
    protected function cake24_get_hotline($return = false, $overTime = 75, $redisObject = ''){

        $key = 'GET_HOTLINE';
        if(empty($redisObject) || !is_object($redisObject)){ $redisObject = $this->Redis; }
        
        //判断redis calss 是否存在
        $redisExist = $this->cake24_check_modelExist($redisObject, 'get', NULL);
        if(!$redisExist){ return ; }

        if ($redisObject->get($key)) {
            $hot_line = $redisObject->get($key);

        }else{

            //判断当前Model是否存在
            $isExist = $this->cake24_check_modelExist($this->GlobalConfig);
            if($isExist){

                $conditions = ['`GlobalConfig`.`category_id`' => 3, '`GlobalConfig`.`status`' => 1]; //3-客服热线
                $config_data = $this->GlobalConfig->find('first', [
                    'conditions' => $conditions,
                    'fields' => ['id', 'short'],
                ]);

                $hot_line = $config_data['GlobalConfig']['short'];
            }

            if(!empty($hot_line)){

                $redisObject->setex($key, $overTime, $hot_line);
            }else{

                $hot_line = '400-920-6688';
            }
            
        }

        if($return){

            return $hot_line;
        }else{

            $this->set('hot_line', $hot_line);
        }
    }

    /** 
      * 格式化网址 
      * par $url 待格式化的网址
      * return  处理后的网址
      * Recoded By dtz
      * date 2017-02-06
      **/ 
    protected function cake24_filterUrl($url = ''){
     
        $url = trim(strtolower($url));
        $url = trim(preg_replace('/^http:\/\//', '', $url));
        if ($url == ''){

            return false;
        }else{
            return urlencode('https://' . $url);
        }
        
    }

    //拼接请求地址，此地址你可以在官方的文档中查看到
    protected function cake24_sinaShortenUrl($long_url){
        
        //百度
        $ch = curl_init();
        $url = 'http://apis.baidu.com/3023/shorturl/shorten?url_long='.$long_url;
        $header = array(
            'apikey: c3aadd615ae4b6bfcbf8e4afc503fab7',
        );
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $result = curl_exec($ch);

        //解析json
        $json = json_decode($result,true);
        $url_short = $json['urls'][0]['url_short'];
        //异常情况返回false
        if (isset($json['error']) || $url_short == '') {
            
            return false;
        }else{
            
            return $url_short;
        }

    }

    /**
    * 验证手机号是否符号为规范
    * @author 120291704@qq.com 20170713
    * @param  number $mobile
    * @return bool true / false
    ---------------------------------------------------------
    * 目前支持的号段有：
    * 130、131、132、133、134、135、136、137、138、139
    * 145、147
    * 150、151、152、153、155、156、157、158、159
    * 166
    * 170、171、173、175、176、177、178
    * 180、181、182、183、184、185、186、187、188、189
    * 198、199 增加198 199 号段
    ---------------------------------------------------------
    * @update 2018-01-23
    */
    protected function cake24_check_mobile($mobile) {
        if (!is_numeric($mobile)) {
            return false;
        }
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[6]\d{8}$|^17[0,1,3,5,6,7,8]{1}\d{8}$|^18[\d]{9}$|^19[8,9]{1}\d{8}$#', $mobile) ? true : false;
    }


    /**
    * 转到工程部的单子，如果如果两月内业主再次报名则不让该单子再次进入分单流程，两月后可再次进入
    * @author dtz 120170714@qq.com
    * @param number $baoming_id
    * @return bool true / false
    */
    private function _check_baoming_ingcb($baoming_id){

        if(!is_numeric($baoming_id)){ return false; }//必须为正整数数字

        $bmp_data = $this->BaomingsPlus->find('first', [

            'conditions' => [
                '`BaomingsPlus`.`baoming_id`'   => $baoming_id,
                '`BaomingsPlus`.`transfer_gcb`' => [1, 2]
            ],
            'fields'     => ['`BaomingsPlus`.`id`', '`BaomingsPlus`.`baoming_id`', '`BaomingsPlus`.`transfer_gcb`', '`BaomingsPlus`.`gcb_time`'],
            'order'      => ['`BaomingsPlus`.`id` DESC']
        ]);

        $span_time = strtotime("-2 month");//跨度时间


        if(empty($bmp_data)){

            return true; //工程部中无此单，可以进入分单流程
            
        }elseif(strtotime($bmp_data['BaomingsPlus']['gcb_time']) >=  $span_time){

            //工程部中有此单，并且在两月内不可以进入分单流程
            return false;
        }else{

            //工程部中有此单，并且在两月外此单子可以进入分单流程
            return true;
        }

    }

    /**
    * 报名日志，记录当前业主每次报名的baoming_id、报名类型、报名的页面、是新增报名还是重复报名  
    * 报名的推广渠道来源等
    * @author 120170714@qq.com
    * @param  $baoming_id Int 报名ID
    * @param  $basic_data Array 报名基本数据
    * @param  $bm_type    Int 1-新增报名 2-重复报名
    * @return bool true / false
    * @date   2018-03-29
    */
    private function _cake24_load_baoming_log($baoming_id, $basic_data, $bm_type = 1){

        if( !is_numeric($baoming_id) || empty($baoming_id) ){ return ; }
        if( !is_array($basic_data)   || empty($basic_data) ){ return ; }
        if( !is_numeric($bm_type)    || empty($bm_type) ){ $bm_type = 1; }

        if(empty($basic_data['utm_page'])){ return ; }

        if($basic_data['zx_tuijian'] && !$basic_data['zx_jiaju'] && !$basic_data['zx_fuwu']){

            $zx_type = 1; //装修
        }elseif (!$basic_data['zx_tuijian'] && $basic_data['zx_jiaju'] && !$basic_data['zx_fuwu']) {
           
            $zx_type = 2; //建材
        }elseif (!$basic_data['zx_tuijian'] && !$basic_data['zx_jiaju'] && $basic_data['zx_fuwu']) {
            
            $zx_type = 3; //服务咨询
        }else{

            $zx_type = 1; //装修
        }

        $this->loadModel('BaomingLog');
        $dbconfig = $this->_cake24_get_dbconfig();

        if(empty($dbconfig)){ return ; }
        $this->BaomingLog->useDbConfig = $dbconfig;//修改数据库配置

        $log_data = [

            'baoming_id'            => $baoming_id,
            'bm_type'               => $bm_type,
            'bm_utmpage'            => $basic_data['utm_page'],
            'zx_type'               => $zx_type,
            'bm_promotion_channels' => isset($basic_data['from_union']) ? trim($basic_data['from_union']) : '',
            'bm_client_ip'          => $this->cake24_getRealIp(),
            'log_time'              => date('Y-m-d H:i:s')
        ];

        try {
            $this->BaomingLog->save($log_data);
        } catch (Exception $error) {
            
            return ;
        }
    }


    /**
    * @获取各项目的数据库（useDbConfig）配置信息
    * @目前支持 youjuke.com、360youjuke.com、youjistyle.com域名
    * @author 120170714@qq.com
    * @return dbconfig string 返回配置信息
    * @date   2018-10-08
    */
    protected function _cake24_get_dbconfig(){

        $configArr = [

            '51youjuke' => [//开发环境或外测

                'api'    => 'default',
                'm'      => 'default',
                'gongsi' => 'default',
                'fuwu'   => 'default',
                'gongdi' => 'default',
                'mall'   => 'default',
                'riji'   => 'default',
                'www'    => 'youjk',
                'tuku'   => 'youjk',
                'zixun'  => 'youjk',
                '360'    => 'youjk',
                'preapi'    => 'default',
                'prem'      => 'default',
                'pregongsi' => 'default',
                'prefuwu'   => 'default',
                'pregongdi' => 'default',
                'premall'   => 'default',
                'preriji'   => 'default',
                'pre'       => 'youjk',
                'pretuku'   => 'youjk',
                'prezixun'  => 'youjk',
                'pre360'    => 'youjk',
                'preyouji'  => 'youjk',//优几index外测
                'premyouji' => 'youjk',//优几mobile外测
            ],
            '360youjuke' => [//线上360

                'm'      => 'youjk'
            ],
            'youjuke'    => [//线上

                'api'    => 'default',
                'm'      => 'default',
                'gongsi' => 'default',
                'fuwu'   => 'default',
                'gongdi' => 'default',
                'mall'   => 'default',
                'riji'   => 'default',
                'www'    => 'youjk',
                'tuku'   => 'youjk',
                'zixun'  => 'youjk'
            ],
            '51youjistyle'  => [//优几(index和mobile项目)本地

                'm'         => 'youjk',//本地
                'www'       => 'youjk',//本地

            ],
            'youjistyle' => [//优几(index和mobile项目)线上

                'm'      => 'youjk',
                'www'    => 'youjk'
            ],
            
        ];

        $server_name = explode('.', $_SERVER['SERVER_NAME']);
        if(empty($server_name[0])){ return ; }

        return $configArr[$server_name[1]][$server_name[0]];
    }

    /**
    * 当非服务咨询报名时，点击需要了预约审核报价，则在报名日志中need_review_quote标记为1
    * @author 120170714@qq.com
    * @date   2018-03-29
    */
    protected function cake24_refresh_baomingLog($baoming_id){

        if(!is_numeric($baoming_id)){ return ; }

        $this->loadModel('BaomingLog');
        $dbconfig = $this->_cake24_get_dbconfig();

        if(empty($dbconfig)){ return ; }
        $this->BaomingLog->useDbConfig = $dbconfig;//修改数据库配置

        $conditions = ['BaomingLog.baoming_id' => $baoming_id];

        //获取当前报名id 最新的一条log记录
        $log_data = $this->BaomingLog->find('first', [
            'conditions' => $conditions,
            'fields' => ['BaomingLog.id', 'BaomingLog.baoming_id', 'BaomingLog.need_review_quote'],
            'order'  => ['BaomingLog.id DESC']
        ]);

        if(empty($log_data)){ return ; }
        $baoming_id   = $log_data['BaomingLog']['baoming_id'];
        $review_quote = $log_data['BaomingLog']['need_review_quote'];
        if(empty($baoming_id) || !empty($review_quote)){ return ; }

        $update_data = ['BaomingLog.need_review_quote' => 1 ];
        

        try {
            
            $this->BaomingLog->updateAll($update_data, $conditions);
        } catch (Exception $error) {
            return ;
        }
    }


    /**
     *  报名入口，检查当前号码是否存在于系统，如存在以下板块，则将被重置到报名管理中
     *  2017-10-25
     **/
    protected function _cake24_check_bm_reset($info) {
        
        $quest = false;
        
        $content = "";

        if (empty($info)) {
            return json_encode(array('code' => 400, 'baoming_id' => 0,'message' => '没有传入数据'));
        }

        //重复报名与重新分配流程新增  东亚楠  20180521 开始位置
        if($info['Baomings']['mes_bak'] >= 5){
			
            # 查询最新条重分记录
            $cxfp_field = ['id','baoming_id','reset','key','mes_bak','reject_over_status','reject_reallot_status'];
            $cxfp_info = $this->BaomingCxfp->find('first',['conditions' => ['baoming_id'=>$info['Baomings']['id'],'key'=>1], 'fields' =>$cxfp_field ,'order' => 'id desc' ]);

            # 如果为空，则新整条数据
            if(empty($cxfp_info)){
				
				/*
				 如果有重新分配的数据，判断是否在以下版块的重置
				*/
				//不需要重置操作

				if ($info['Baomings']['is_conect'] == 1 && $info['Baomings']['yixiang'] != 1 && $info['Baomings']['is_fp'] == 1 && $info['Baomings']['mes_bak'] == 1 && $info['Baomings']['delay'] == 0 && ($info['Baomings']['status'] == 0 || $info['Baomings']['status'] == 1) && ($info['Baomings']['status_trace'] == 0 || $info['Baomings']['status_trace'] == 1) && $info['Baomings']['invalid'] == 0 && $info['Baomings']['decoration'] != 1 && $info['Baomings']['is_qy'] == 0) {
					# 意向管理
					$quest = 1;
					$content = "由业主主动报名，从意向管理重置到报名管理中";
				} else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['mes_bak'] == 1 && ($info['Baomings']['delay'] == 1 || $info['Baomings']['delay'] == 2) && ($info['Baomings']['status'] == 0 || $info['Baomings']['status'] == 1) && ($info['Baomings']['status_trace'] == 0 || $info['Baomings']['status_trace'] == 1) && $info['Baomings']['invalid'] == 0 && $info['Baomings']['decoration'] == 0 && $info['Baomings']['is_qy'] == 0) {
					# 潜在管理
					$quest = 1;
					$content = "由业主主动报名，从潜在管理重置到报名管理中";

				} else if ($info['Baomings']['decoration'] == 1 && $info['Baomings']['is_qy'] == 0) {
					# 无装修需求
					$quest = 1;
					$content = "由业主主动报名，从无装修需求重置到报名管理中";

				} else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['yixiang'] != 1 && ($info['Baomings']['status_trace'] == 2 || $info['Baomings']['status_trace'] == 3) && $info['Baomings']['is_qy'] == 0) {
					# 结束
					$quest = 1;
					$content = "由业主主动报名，从结束管理重置到报名管理中";

				} else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['yixiang'] != 1 && ($info['Baomings']['status'] == 2 || $info['Baomings']['invalid'] == 1) && $info['Baomings']['is_qy'] == 0) {
					# 无效
					$quest = 1;
					$content = "由业主主动报名，从无效管理重置到报名管理中";

				} else if ($info['Baomings']['is_qy'] == 1 || $info['Baomings']['can_not_go'] == 1) {
					$quest = 2;
					return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '只返回报名id'));
				} else {
					$quest = 2;
					//都不符合条件，只返回报名id
					//return json_encode(array('status' => true, 'baoming_id' => $info['Baomings']['id']));
					return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '只返回报名id'));
				}
				#处于分单质检或者重分质检不被重置
				if($info['Baomings']['reject_allot_status']==1||$info['Baomings']['reject_reallot_status']==1){
					return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '处于分单质检或者重分质检不被重置'));
				}else{
					#新增数据
					if($quest==1){
						$this->ResetLogs->save(array('baoming_id' => $info['Baomings']['id'], 'content' => $content, 'create_time' => date("Y-m-d H:i:s"), 'cs_id' => 0));
					}
				}
				/* 
					end
				*/

                $save = [];
                $save['baoming_id'] = $info['Baomings']['id'];                      //报名ID
                $save['reset'] = 1;                             //标记为重置
                $save['key'] = 1;                               //重新分配标记
                $save['mes_bak'] = 1;                           //是否过质检
                $save['reject_reallot_status'] = 0;             //0.未申请
                $save['cz_addtime'] = date("Y-m-d H:i:s");      //提交时间
                $save['zx_plate'] = 1;                          //报名管理
                $save_cxfp = $this->BaomingCxfp->save($save);

                if($save_cxfp){
                    $this->BaomingsPlus->updateAll(['is_cxfp_new' => 1],['baoming_id' => $info['Baomings']['id']]);
					//如果重分新增数据，原来结束状态改掉
					//$update['is_conect'] = 0;
						//$update['mes_bak'] = 1;
						//$update['delay'] = 0;
						$update['status'] = 0;
						$update['status_trace'] = 1;
						$update['invalid'] = 0;
						//$update['yixiang'] = 0;
						//$update['allow'] = 0;
						//$update['is_qy'] = 0;
						$update['decoration'] = 0;
						$update['reset_time'] = "'" . date("Y-m-d H:i:s") . "'";
						//$update['reject_allot_status'] = 0;
						$update['reject_over_status'] = 0;
						$update['reject_invalid_status'] = 0;
						//$update['can_not_go'] = 0;
						//$update['can_not_cause'] = null;

					$this->Baomings->updateAll($update, array('Baomings.id' => $info['Baomings']['id']));
					
					
                    return json_encode(array( 'code' => 200 ,'baoming_id' => $info['Baomings']['id'] , 'message' => '成功插入第一条重分数据' ));
                }else{
                    //return json_encode(array( 'code' => 400 , 'error' => 401 , 'message' => '重分数据插入失败' ));
					return json_encode(array( 'code' => 400, 'baoming_id' => $info['Baomings']['id'],'message' => '重分数据插入失败'));
                }

            }else{
				
                # 如果最新条数据已过质检，则把其他记录的key更新为0，添加条新数据
                if($cxfp_info['BaomingCxfp']['mes_bak'] >= 5 ){
					
					/*
					 如果有重新分配的数据，判断是否在以下版块的重置
					*/
					//不需要重置操作

					if ($info['Baomings']['is_conect'] == 1 && $info['Baomings']['yixiang'] != 1 && $info['Baomings']['is_fp'] == 1 && $info['Baomings']['mes_bak'] == 1 && $info['Baomings']['delay'] == 0 && ($info['Baomings']['status'] == 0 || $info['Baomings']['status'] == 1) && ($info['Baomings']['status_trace'] == 0 || $info['Baomings']['status_trace'] == 1) && $info['Baomings']['invalid'] == 0 && $info['Baomings']['decoration'] != 1 && $info['Baomings']['is_qy'] == 0) {
						# 意向管理
						$quest = 1;
						$content = "由业主主动报名，从意向管理重置到报名管理中";
					} else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['mes_bak'] == 1 && ($info['Baomings']['delay'] == 1 || $info['Baomings']['delay'] == 2) && ($info['Baomings']['status'] == 0 || $info['Baomings']['status'] == 1) && ($info['Baomings']['status_trace'] == 0 || $info['Baomings']['status_trace'] == 1) && $info['Baomings']['invalid'] == 0 && $info['Baomings']['decoration'] == 0 && $info['Baomings']['is_qy'] == 0) {
						# 潜在管理
						$quest = 1;
						$content = "由业主主动报名，从潜在管理重置到报名管理中";

					} else if ($info['Baomings']['decoration'] == 1 && $info['Baomings']['is_qy'] == 0) {
						# 无装修需求
						$quest = 1;
						$content = "由业主主动报名，从无装修需求重置到报名管理中";

					} else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['yixiang'] != 1 && ($info['Baomings']['status_trace'] == 2 || $info['Baomings']['status_trace'] == 3) && $info['Baomings']['is_qy'] == 0) {
						# 结束
						$quest = 1;
						$content = "由业主主动报名，从结束管理重置到报名管理中";

					} else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['yixiang'] != 1 && ($info['Baomings']['status'] == 2 || $info['Baomings']['invalid'] == 1) && $info['Baomings']['is_qy'] == 0) {
						# 无效
						$quest = 1;
						$content = "由业主主动报名，从无效管理重置到报名管理中";

					} else if ($info['Baomings']['is_qy'] == 1 || $info['Baomings']['can_not_go'] == 1) {
						$quest = 2;
						return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '只返回报名id'));
					} else {
						$quest = 2;
						//都不符合条件，只返回报名id
						//return json_encode(array('status' => true, 'baoming_id' => $info['Baomings']['id']));
						return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '只返回报名id'));
					}
					#处于分单质检或者重分质检不被重置
					if($info['Baomings']['reject_allot_status']==1||$info['Baomings']['reject_reallot_status']==1){
						return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '处于分单质检或者重分质检不被重置'));
					}else{
						#新增数据
						if($quest==1){
							$this->ResetLogs->save(array('baoming_id' => $info['Baomings']['id'], 'content' => $content, 'create_time' => date("Y-m-d H:i:s"), 'cs_id' => 0));
						}
					}
					/* 
						end
					*/
					
					
                    $upd = $this->BaomingCxfp->updateAll(['key'=>0],['baoming_id' => $info['Baomings']['id']]);

                    $save = [];
                    $save['baoming_id'] = $info['Baomings']['id'];
                    $save['reset'] = 1;                             //标记为重置
                    $save['key'] = 1;                               //重新分配标记
                    $save['mes_bak'] = 1;                           //是否过质检
                    $save['reject_reallot_status'] = 0;             //0.未申请
                    $save['cz_addtime'] = date("Y-m-d H:i:s");      //提交时间
                    $save['zx_plate'] = 1;                          //报名管理
                    $save['limits'] = 0;                          //限制数
                    $save['cxfp_limiits'] = 0;                    //限制数
					
                    $save_cxfp = $this->BaomingCxfp->save($save);
					
                    if( !empty($upd) && !empty($save_cxfp) ){
					
                        $this->BaomingsPlus->updateAll(['is_cxfp_new' => 1],['baoming_id' => $info['Baomings']['id']]);
						//$update['is_conect'] = 0;
						//$update['mes_bak'] = 1;
						//$update['delay'] = 0;
						$update['status'] = 0;
						$update['status_trace'] = 1;
						$update['invalid'] = 0;
						//$update['yixiang'] = 0;
						//$update['allow'] = 0;
						//$update['is_qy'] = 0;
						$update['decoration'] = 0;
						$update['reset_time'] = "'" . date("Y-m-d H:i:s") . "'";
						//$update['reject_allot_status'] = 0;
						$update['reject_over_status'] = 0;
						$update['reject_invalid_status'] = 0;
						//$update['can_not_go'] = 0;
						//$update['can_not_cause'] = null;

					$this->Baomings->updateAll($update, array('Baomings.id' => $info['Baomings']['id']));

						return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '成功插入条新的重置数据'));
                    }else{
						return json_encode(array( 'code' => 400, 'baoming_id' => $info['Baomings']['id'],'message' => '重置数据插入失败'));
                    }

                }else if($cxfp_info['BaomingCxfp']['mes_bak'] == 1 && in_array($cxfp_info['BaomingCxfp']['reject_reallot_status'],[2])){
					
					/*
					 如果有重新分配的数据，判断是否在以下版块的重置
					*/
					//不需要重置操作

					if ($info['Baomings']['is_conect'] == 1 && $info['Baomings']['yixiang'] != 1 && $info['Baomings']['is_fp'] == 1 && $info['Baomings']['mes_bak'] == 1 && $info['Baomings']['delay'] == 0 && ($info['Baomings']['status'] == 0 || $info['Baomings']['status'] == 1) && ($info['Baomings']['status_trace'] == 0 || $info['Baomings']['status_trace'] == 1) && $info['Baomings']['invalid'] == 0 && $info['Baomings']['decoration'] != 1 && $info['Baomings']['is_qy'] == 0) {
						# 意向管理
						$quest = 1;
						$content = "由业主主动报名，从意向管理重置到报名管理中";
					} else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['mes_bak'] == 1 && ($info['Baomings']['delay'] == 1 || $info['Baomings']['delay'] == 2) && ($info['Baomings']['status'] == 0 || $info['Baomings']['status'] == 1) && ($info['Baomings']['status_trace'] == 0 || $info['Baomings']['status_trace'] == 1) && $info['Baomings']['invalid'] == 0 && $info['Baomings']['decoration'] == 0 && $info['Baomings']['is_qy'] == 0) {
						# 潜在管理
						$quest = 1;
						$content = "由业主主动报名，从潜在管理重置到报名管理中";

					} else if ($info['Baomings']['decoration'] == 1 && $info['Baomings']['is_qy'] == 0) {
						# 无装修需求
						$quest = 1;
						$content = "由业主主动报名，从无装修需求重置到报名管理中";

					} else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['yixiang'] != 1 && ($info['Baomings']['status_trace'] == 2 || $info['Baomings']['status_trace'] == 3) && $info['Baomings']['is_qy'] == 0) {
						# 结束
						$quest = 1;
						$content = "由业主主动报名，从结束管理重置到报名管理中";

					} else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['yixiang'] != 1 && ($info['Baomings']['status'] == 2 || $info['Baomings']['invalid'] == 1) && $info['Baomings']['is_qy'] == 0) {
						# 无效
						$quest = 1;
						$content = "由业主主动报名，从无效管理重置到报名管理中";

					} else if ($info['Baomings']['is_qy'] == 1 || $info['Baomings']['can_not_go'] == 1) {
						$quest = 2;
						return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '只返回报名id'));
					} else {
						$quest = 2;
						//都不符合条件，只返回报名id
						//return json_encode(array('status' => true, 'baoming_id' => $info['Baomings']['id']));
						return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '只返回报名id'));
					}
					#处于分单质检或者重分质检不被重置
					if($info['Baomings']['reject_allot_status']==1||$info['Baomings']['reject_reallot_status']==1){
						return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '处于分单质检或者重分质检不被重置'));
					}else{
						#新增数据
						if($quest==1){
							$this->ResetLogs->save(array('baoming_id' => $info['Baomings']['id'], 'content' => $content, 'create_time' => date("Y-m-d H:i:s"), 'cs_id' => 0));
						}
					}
					/* 
						end
					*/
					
					
                    # 如果最新条数据被质检驳回，则更新原有数据

                    //$upd = $this->BaomingCxfp->updateAll(['key'=>0],['baoming_id' => $info['Baomings']['id']]);
                    $save = [];
                    $save['id'] = $cxfp_info['BaomingCxfp']['id'];
                    $save['reset'] = 1;                     //标记为重置
                    $save['key'] = 1;                       //重新分配标记
                    $save['reject_reallot_status'] = 0;     //0.未申请
                    $save['yixiang']    = 0;                //是否意向
                    $save['delay']      = 0;                //是否潜在
                    $save['qz_cause']   = NULL;             //潜在原因
                    $save['delay_time'] = NULL;             //潜在用户标记时间
                    $save['mes_bak']    = 1;                //是否过质检
                    $save['is_conect']  = 0;                //跟进中状态
                    $save['zx_plate']  = 1;                 //装修业务板块标识符
                    $save['is_conect_time']  = NULL;        //标记待确认时间
                    $save['next_intention_type']  = NULL;   //再次转意向分类
                    $save['next_intention_qt']  = NULL;     //再次转为意向其他类型描述
                    $save['intention_type']  = NULL;        //再次转意向分类
                    $save['intention_qt']  = NULL;          //再次转为意向其他类型描述
                    $save['reject_allot_status']  = 0;      //申请分单质检状态
                    $save['limits'] = 3;                    //联系商家数
                    $save['cxfp_limiits'] = 3;              //联系商家数
                    $save['cz_addtime'] = date("Y-m-d H:i:s");    //提交时间
                    $save_cxfp = $this->BaomingCxfp->save($save);

                    if( !empty($save_cxfp) ){

                        $this->BaomingsPlus->updateAll(['is_cxfp_new' => 1],['baoming_id' => $info['Baomings']['id']]);
                        return json_encode(array( 'code' => 200 ,'baoming_id' => $info['Baomings']['id'] , 'message' => '驳回的重置/重分数据更新成功' ));
                    }else{
						return json_encode(array( 'code' => 400, 'baoming_id' => $info['Baomings']['id'],'message' => '驳回的重置/重分数据更新失败'));
                    }

                }else{
					return json_encode(array( 'code' => 400, 'baoming_id' => $info['Baomings']['id'],'message' => '当前数据无法重置或重复提交'));
                }

            }

        }elseif($info['Baomings']['mes_bak'] == 1){
            //$this->Baoming->updateAll($update, array('id' => $info['Baomings']['id']));
            //不需要重置操作
            $update  = array();

            if ($info['Baomings']['is_conect'] == 1 && $info['Baomings']['yixiang'] != 1 && $info['Baomings']['is_fp'] == 1 && $info['Baomings']['mes_bak'] == 1 && $info['Baomings']['delay'] == 0 && ($info['Baomings']['status'] == 0 || $info['Baomings']['status'] == 1) && ($info['Baomings']['status_trace'] == 0 || $info['Baomings']['status_trace'] == 1) && $info['Baomings']['invalid'] == 0 && $info['Baomings']['decoration'] != 1 && $info['Baomings']['is_qy'] == 0) {
                # 意向管理
                $quest = 1;
                $content = "由业主主动报名，从意向管理重置到报名管理中";
                $update['is_fp'] = 1;
            } else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['mes_bak'] == 1 && ($info['Baomings']['delay'] == 1 || $info['Baomings']['delay'] == 2) && ($info['Baomings']['status'] == 0 || $info['Baomings']['status'] == 1) && ($info['Baomings']['status_trace'] == 0 || $info['Baomings']['status_trace'] == 1) && $info['Baomings']['invalid'] == 0 && $info['Baomings']['decoration'] == 0 && $info['Baomings']['is_qy'] == 0) {
                # 潜在管理
                $quest = 1;
                $content = "由业主主动报名，从潜在管理重置到报名管理中";
                
				#判断一下是否有客服
				if($info['Baomings']['ownercs']==0){
					$update['ownercs'] = 0;
					$update['is_fp'] = 0;
				}
                
            } else if ($info['Baomings']['decoration'] == 1 && $info['Baomings']['is_qy'] == 0) {
                # 无装修需求
                $quest = 1;
                $content = "由业主主动报名，从无装修需求重置到报名管理中";
                $update['is_fp'] = 0;
				$update['ownercs'] = 0;
            } else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['yixiang'] != 1 && ($info['Baomings']['status_trace'] == 2 || $info['Baomings']['status_trace'] == 3) && $info['Baomings']['is_qy'] == 0) {
                # 结束
                $quest = 1;
                $content = "由业主主动报名，从结束管理重置到报名管理中";
                $update['is_fp'] = 0;
                $update['ownercs'] = 0;
            } else if ($info['Baomings']['is_fp'] == 1 && $info['Baomings']['yixiang'] != 1 && ($info['Baomings']['status'] == 2 || $info['Baomings']['invalid'] == 1) && $info['Baomings']['is_qy'] == 0) {
                # 无效
                $quest = 1;
                $content = "由业主主动报名，从无效管理重置到报名管理中";
                $update['is_fp'] = 0;
                $update['ownercs'] = 0;
            } else if ($info['Baomings']['is_qy'] == 1|| $info['Baomings']['can_not_go'] == 1) {
                $quest = 2;
            } else {
				//都不符合条件，只返回报名id
				return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '只返回报名id'));
            }
			#处于分单质检或者重分质检不被重置
			if($info['Baomings']['reject_allot_status']==1||$info['Baomings']['reject_reallot_status']==1){
				return json_encode(array( 'code' => 200, 'baoming_id' => $info['Baomings']['id'],'message' => '处于分单质检或者重分质检不被重置'));
			}else{
				if ($quest == 1) {
					$update['is_conect'] = 0;
					$update['mes_bak'] = 1;
					$update['delay'] = 0;
					$update['status'] = 0;
					$update['status_trace'] = 1;
					$update['invalid'] = 0;
					$update['yixiang'] = 0;
					$update['allow'] = 0;
					$update['is_qy'] = 0;
					$update['decoration'] = 0;
					$update['reset_time'] = "'" . date("Y-m-d H:i:s") . "'";
					$update['reject_allot_status'] = 0;
					$update['reject_over_status'] = 0;
					$update['reject_invalid_status'] = 0;
					$update['can_not_go'] = 0;
					$update['can_not_cause'] = null;

					$this->Baomings->updateAll($update, array('Baomings.id' => $info['Baomings']['id']));

					#新增数据
					$this->ResetLogs->save(array('baoming_id' => $info['Baomings']['id'], 'content' => $content, 'create_time' => date("Y-m-d H:i:s"), 'cs_id' => 0));
				}
				
				return json_encode(array( 'code' => 200, 'baoming_id' => !empty($info['Baomings']['id']) ? $info['Baomings']['id'] : 0,'message' => '日志新增'));
			}
        }
        //重复报名与重新分配流程新增  东亚楠  20180521 结束位置

        //$this->RejectLogs->updateAll(array('reset_tiger' => 1, 'reset_time' => "'" . date("Y-m-d H:i:s") . "'"), array('baoming_id' => $info['Baomings']['id'], 'type' => array(1,4,5), 'ex_status' => 1));
		
    }


    /**
    * 断开MYSQL数据库短连接（暂不开通）
    * @author 120170714@qq.com
    * @date   2018-04-11
    */
    private function _cake24_disconnectMysql(){

        return ;
        unset($host);
        unset($login);
        unset($password);
        $dbconfig = $this->_cake24_get_dbconfig();
        if(empty($dbconfig)){ return ; }

        $source_data  = ConnectionManager::getDataSource($dbconfig);
        $connect_data = array_values( (array)$source_data );

        $host     = $connect_data[1]['host'];
        $login    = $connect_data[1]['login'];
        $password = $connect_data[1]['password'];

        try {
            mysql_close(mysql_connect($host, $login, $password));
        } catch (Exception $error) {
            
            return ;
        }
    }

    /** 
     * @格式化mall_users表中用户的用户名
     * -----------------------------------------------------
     * @目前mall_users表中username大部分存储的是明文的手机号
     * @为了防止手机号泄露，则如果用户名是手机号的话需要隐藏手机号中间四位数
     * -----------------------------------------------------
     * @param  $username String 用户名
     * @author 120291704@qq.com
     * @date 2018-08-24
     */
    protected function _cake24_formatUsername($username){

        if(empty($username)){ return ; }
        
        //如果用户的用户名是以明文手机号形式显示，则需要隐藏中间四位数
        if($this->cake24_check_mobile($username)){

            return substr_replace($username, '****', 3, 4);
        }else{

            //非手机号形式则直接返回
            return $username;
        }

    }

    /**
     * 判断是否加载类 ToolGlobal
     * @author 120291704@qq.com @date 2018-08-31
     * ---------------------------------------------------------
     * @param $post_data Array 报名提交的表单数据
     * @注:如果发现未加载ToolGlobal类，则记录在log日志中，方便后续添加和维护
     * ---------------------------------------------------------
     * @更新于 2018-08-31
     */
    protected function _check_toolGlobalExist($post_data){

        if(class_exists('ToolGlobal')){

            return $this->_YJK->reqCheck($post_data);
        }else{

            $this->log($_SERVER['SERVER_NAME'].' class ToolGlobal not loaded !', 'toolglobal');
            return $post_data;

        }
    }


    /** 
     * @针对优居客后台管理调用报名接口
     * -----------------------------------------------------------------------------------------------
     * @param $post_data            Array 报名提交的数据（报名姓名、手机号、报名来源和报名页面来源等信息）
     * @param $post_data['name']        Str 报名姓名                               （必填）
     * @param $post_data['mobile']      Str 报名手机号                             （必填）
     * @param $post_data['utm_page']    Str 报名页面来源（即菜单），建议存储方式为中文（必填）
     * @param $post_data['bm_laiyuan']  Str 报名来源（即项目名称），建议存储方式为英文（必填）
     * @param $post_data['area']        Int 报名房屋面积                           （选填）
     * @注：其它选填的字段包括 报名表中出上面列出的字段的任意字段
     * -----------------------------------------------------------------------------------------------
     * @param $params                    Array 报名的必要配置参数
     * @param $params['return_type']     Bool 返回方式  如果想要获取报名失败后的详细信息则为 true,默认为true
     * @param $params['decoration_type'] Bool 报名类型 （1-装修 2-建材 3-咨询服务），默认为1 （装修报名）
     * -----------------------------------------------------------------------------------------------
     * @return $return_data['status']             Int   返回状态
     * @return $return_data['errorMsg']           Str   返回错误信息
     * @return $return_data['data']               Array 返回数据
     * @return $return_data['data']['baoming_id'] Int   返回报名ID
     * @return $return_data['data']['bm_type']    Int   报名方式 1-新入库 2-已入库
     * -----------------------------------------------------------------------------------------------
     * @调用案例(报名类型为装修报名)
     * $post_data = [
     *     'name'       => 报名姓名, 
     *     'mobile'     => 报名手机号,
     *     'utm_page'   => '业主查询（推广）',
     *     'bm_laiyuan' => 'baoming',
     *     …报名表其它选填字段… => 数据
     * ];
     *
     * $return_data = $this->cake24_yjkAdminbmCommit($post_data);
     * dump(json_decode($return_data, true));exit;
     * -----------------------------------------------------------------------------------------------
     * @注：报名接口添加判断所有依赖的Models是否存在的容错机制，避免致命错误
     * @注：报名接口添加判断是否加载类 ToolGlobal
     * -----------------------------------------------------------------------------------------------
     * @author 120291704@qq.com
     * -----------------------------------------------------------------------------------------------
     * @create date 2018-10-30
     */
    protected function cake24_yjkAdminbmCommit(array $post_data = [], array $params = []){

        //错误提示返回类型
        if( isset($params['return_type']) && is_bool($params['return_type']) ){

            $return_type = $params['return_type'];
        }else{

            $return_type = true;
        }

        //判断是否有报名数据
        if( empty($post_data) || !is_array($post_data)){

            return $this->bm_returns( ['status' => 10400, 'errorMsg' => '报名请求参数不能为空！'], $return_type);
        }

        //判断是否加载类 ToolGlobal
        $post_data = $this->_check_toolGlobalExist($post_data);

        //报名类型 1-装修报名 2-建材报名 3-服务咨询报名（默认装修报名）
        if( isset($params['decoration_type']) && in_array((int)$params['decoration_type'], [1, 2, 3]) ){

            $decoration_type = $params['decoration_type'];
        }else{

            $decoration_type = 1;
        }

        //后台报名来源 例如：报名后台过来的数据则存储 baoming
        if( !isset($post_data['bm_laiyuan']) || empty($post_data['bm_laiyuan']) ){

            return $this->bm_returns( ['status' => 10401, 'errorMsg' => '请定义报名来源（即项目名称）！'], $return_type);
        }

        //后台报名页面来源 例如：报名后台某个菜单过来的数据则存储 具体菜单名称
        if( !isset($post_data['utm_page']) || empty($post_data['utm_page']) ){

            return $this->bm_returns( ['status' => 10402, 'errorMsg' => '请定义报名页面来源（即菜单）！'], $return_type);
        }


        /**************判断所有依赖的Models是否存在 2017-10-29 start*****************/
        $need_modelArr   = $this->_cake24_yjkAdminbmModels;
        $checkmodelExist = $this->cake24_check_modelArrExist($need_modelArr);
        if(!$checkmodelExist['status']){

            return $this->bm_returns( ['status' => 10403, 'errorMsg' => $checkmodelExist['errorMsg'], 'data' => '必须依赖的models有：'.implode(',', $need_modelArr)], $return_type );
        }
        /**************判断所有依赖的Models是否存在 2017-10-29 end*****************/

        $data           = [];
        $need_fieldsArr = ['name', 'mobile'];//报名必填的字段进行判断

        foreach ($need_fieldsArr as $field) {
            
            if(empty($post_data[$field])){//判断关键数据是否为空，为空则返回错误

                $field_name = $field == 'name' ? '姓名':'手机号';

                return $this->bm_returns( ['status' => 10404, 'errorMsg' => $field_name.'不能为空！'], $return_type);
            }

            $function_name = 'cake24_ck' . $field;
            if($field == 'mobile') {

                //U_ENCRYPT 作用是对手机进行加密处理
                $foo = !$data[$field] = $this->U_ENCRYPT($this->$function_name($post_data[$field]));
            }else{

                //对姓名进行过滤，去除掉特殊字符和如果姓名为空则标注为NONAME
                $foo = !$data[$field] = $this->$function_name($post_data[$field]);
            }
            if (empty($post_data[$field]) || !method_exists($this, $function_name) || $foo) {

                return $this->bm_returns( ['status' => 10405, 'errorMsg' => '提交错误，请不要填写特殊字符！'], $return_type );
            }
        }

        unset($need_modelArr, $checkmodelExist, $need_fieldsArr, $field_name, $foo);


        //对报名非必要（选填）字段数据进行判断
        $analyze = $this->_cake24_analyzeFieldsInModelColumn(array_keys($post_data), $this->Baomings);

        if(!$analyze['status']){

            return $this->bm_returns( ['status' => 10406, 'errorMsg' => $analyze['errorMsg']], $return_type );
        }

        foreach ($post_data as $key => $value) {
        
            //报名表中name mobile两字段的数据已做过判断，后续无需处理
            if(in_array($key, ['name', 'mobile'])){ continue; }

            //传递过来的数据在判断数组中则判断格式是否正确并且不能为空
            $function_name = 'cake24_ck' . $key;
            if (method_exists($this, $function_name)) {
                
                if(!$data[$key] = $this->$function_name($value)){

                    return $this->bm_returns( ['status' => 10407, 'errorMsg' => $key.'数据字段格式错误！', 'data' => ['error field' => $key]], $return_type );
                }

            }else{//判断该字段的方法不存在

                if(empty($value)){

                    return $this->bm_returns( ['status' => 10408, 'errorMsg' => $key.'数据字段值不能为空！'], $return_type );
                }

                $data[$key] = $value;
            }
        }

        unset($analyze, $function_name);

        //屏蔽掉局域网、外测和系统配置ip（屏蔽掉的ip无报名次数限制）
        $ip = $this->cake24_getRealIp();

        //true 当前ip已屏蔽（无报名次数限制） ， false 当前ip没有屏蔽（有报名次数限制）
        $ignore = $this->cake24_ignore_ip($ip);

        if( !$ignore ){//没有屏蔽的ip,报名次数不能超过10次

            $client_ip_count = $this->Baomings->find('count',array(
                    'conditions'    => [
                        'client_ip' => $ip,
                        'DATE_FORMAT(addtime,\'%Y-%m-%d\')' => date('Y-m-d')
                    ]
                )
            );

            //同一个ip下报名次数不能超过10次
            if( $client_ip_count >= 10 ){
                
                return $this->bm_returns( ['status' => 10409, 'errorMsg' => '报名次数过多，请稍后再试！'], $return_type );
            }

            unset($client_ip_count);
        }


        $data['province_id'] = 9;
        $data['city_id']     = 72;
        $data['client_ip']   = $ip;
        $data['detail_ly']   = 'pc';
        $data['addtime']     = date('Y-m-d H:i:s');
        $data['utm_page']    = trim($post_data['utm_page']);
        $data['bm_laiyuan']  = trim($post_data['bm_laiyuan']);
        $data['bm_bak']      = isset($post_data['bm_bak']) ? trim($post_data['bm_bak']) : '';

        $baoming_data = $this->Baomings->find('first', [
                'conditions' => ['Baomings.mobile' => $data['mobile']], 
                'fields'     => ['Baomings.id'], 
                'order'      => ['Baomings.id' => 'DESC']
            ]
        );

        //装修报名
        if( $decoration_type == 1 ){

            $data['zx_tuijian'] = 1;
            $data['zx_jiaju']   = 0;
            $data['zx_fuwu']    = 0;

        //建材报名
        }elseif( $decoration_type == 2 ){

            $data['zx_tuijian'] = 0;
            $data['zx_jiaju']   = 1;
            $data['zx_fuwu']    = 0;

        //服务咨询
        }elseif( $decoration_type == 3 ){

            $data['zx_tuijian'] = 0;
            $data['zx_jiaju']   = 0;
            $data['zx_fuwu']    = 1;
        }

        //判断手机号是否在白名单列表
        $in_whitelist  = $this->_cake24_check_mobile_whitelist($post_data['mobile']);
        //白名单手机号报名进来的数据不进入分单流程
        if($in_whitelist){ $data['is_fp']  = 2; }

        //没有数据添加  否则重置或修改
        if( !$baoming_data || $in_whitelist ){

            try {
                
                if (!$this->Baomings->save($data)) {

                    return $this->bm_returns( ['status' => 10410, 'errorMsg' => '报名失败，请重试！'], $return_type );
                }

            } catch (Exception $error) {
                
                return $this->bm_returns( ['status' => 10411, 'errorMsg' => $error->errorInfo[2]], $return_type );
            }

            $bm_type    = 1;//新入库
            $baoming_id = $this->Baomings->id;
            $this->U_SAVE_BM($baoming_id, $post_data['mobile']);
            
            
        }else{

            $bm_type    = 2;//已入库
            $baoming_id = $baoming_data['Baomings']['id'];
        }

        unset($in_whitelist);
        $this->_cake24_load_baoming_log($baoming_id, $data, $bm_type);//添加报名日志记录
        $this->_cake24_check_baomingServiceExist($baoming_id, $decoration_type);

        return $this->bm_returns( [

            'status'   => 10200,
            'errorMsg' => '',
            'data'     => [

                'baoming_id' => $baoming_id,
                'bm_type'    => $bm_type
            ]

        ], $return_type );

    }


    /** 
     * @后台调用报名接口所必须依赖的models
     * @author 120291704@qq.com
     * @date 2018-10-29
     **/
    private $_cake24_yjkAdminbmModels = ['Baomings', 'BaomingsPlus', 'BaomingService', 'GenzongCsfp'];

    /**
     * @针对后台管理的服务咨询报名逻辑处理
     * ---------------------------------------------------------------------
     * @param $baoming_id      Int 报名ID
     * @param $decoration_type Int 报名装修类型
     * @return
     * @注:如果是咨询服务报名，则判断BaomingService表中是否存在当前baoming_id记录，如果有则直接返回
     *     如果不存在，则插入 last_plate=0，create_time为当前时间的当前报名ID的记录
     * ---------------------------------------------------------------------
     * @author 120291704@qq.com @date 2018-10-30
     * @date 2018-10-30
     */
    private function _cake24_check_baomingServiceExist($baoming_id, $decoration_type){

        if( !is_numeric($baoming_id) || empty($baoming_id) ){ return ; }
        if( !is_numeric($decoration_type) || $decoration_type != 3 ){ return ; }

        //判断服务咨询报名是否存在
        $service_exist = $this->BaomingService->find('count', [

            'conditions' => ['baoming_id' => $baoming_id]
        ]);

        if($service_exist){ return ; }

        $service_data  = ['baoming_id' => $baoming_id, 'last_plate' => 0, 'create_time' => time()];

        //判断当前报名是否分配过客服（如果分配过需要baoming_service.is_fp_genzong 为1）
        $genzong_con   = ['baoming_id' => $baoming_id, 'type' => 1, 'f_cs !=' => NULL];
        $genzong_exist = $this->GenzongCsfp->find('count', [ 'conditions' => $genzong_con ]);
        if($genzong_exist){

            $service_data['is_fp_genzong'] = 1;
        }

        unset($service_exist, $genzong_con, $genzong_exist);

        try {
                
            $this->BaomingService->save($service_data);
        } catch (Exception $error) {
            
            return ;
        }

    }


    /**
     * @判断字段（以数组形式的多个字段）是否在指定的Model中
     * ---------------------------------------------------------------------
     * @param  $fieldsArr               Array  需要核实的字段数组
     * @param  $model                   Object 指定的Model
     * @return $return_data             Array  返回信息
     * @return $return_data['status']   Bool true-(指定的字段在Model中) / false-(有字段有不在Model中)
     * @return $return_data['errorMsg'] Str 错误提示信息
     * ---------------------------------------------------------------------
     * @author 120291704@qq.com @date 2018-10-31
     * @update date 2018-11-01
     */
    private function _cake24_analyzeFieldsInModelColumn(array $fieldsArr, object $model){

        if(!is_array($fieldsArr) || empty($fieldsArr)){

            return ['status' => false, 'errorMsg' => '缺少必要的字段！'];
        }

        if(!is_object($model)){

            return ['status' => false, 'errorMsg' => '缺少必要的Model！'];
        }

        if(!$this->cake24_check_modelExist($model)){

            return ['status' => false, 'errorMsg' => '缺少必须依赖的model！'];
        }

        foreach ($fieldsArr as $key => $value) {
            
            if(!in_array($value, array_keys($model->getColumnTypes()))){

                return ['status' => false, 'errorMsg' => $value.'字段不在'.$model->useTable.'表中'];
            }
        }

        return ['status' => true];
    }

}