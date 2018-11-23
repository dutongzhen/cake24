<?php
/**
 * 全站节日期间添加文字提示
 * @author 120291704@qq.com @date 2018-01-17
 * ---------------------------------------------------------
 * @param $css_style         string   显示春节提示的样式（样式尽量简洁）
 * @param $notice_tag        string   春节提示所需要运用到的html标签,默认为<p>标签
 * @param $startdate         datetime 节假日开始日期
 * @param $enddate           datetime 节假日结束日期
 * @param $expire_time_show  bool     是否需要显示春节过后的提示信息
 * @param $show_tag          string   春节过后需要提示的文字需要用到的html标签
 * @param $show_style        string   春节过后需要提示的样式（样式尽量简洁）
 * @param $show_content      string   春节过后需要提示的内容（内容尽量简短）
 * ---------------------------------------------------------
 */

$nowtime      = time();
$startdate    = strtotime('2018-02-01 11:00:00'); //节假日开始日期
$enddate      = strtotime('2018-02-22 00:00:00'); //节假日结束日期
$css_style    = !empty($css_style) ? $css_style : 'color:red;text-align:center;padding:2px 0;';
$notice_tag   = !empty($notice_tag) ? $notice_tag : 'p';
$show_content = !empty($show_content) ? $show_content : '优居客将在24小时内与您联系，为您详细介绍相关服务！请您确保手机畅通！';
$notice_content = !empty($notice_content) ? $notice_content : '优居客春节于2月2日-2月21日放假，过年期间报名，客服将在年后给您回电，届时您可咨询装修相关的任何问题！';


if($nowtime >= $startdate && $nowtime < $enddate){
	
	echo '<'.$notice_tag.' style="'.$css_style.'" >';
	echo $notice_content;
	echo '</'.$notice_tag.'>';

}elseif($expire_time_show){

	if($show_tag){

		echo '<'.$show_tag.' style="'.$show_style.'" >';
		echo $show_content;
		echo '</'.$show_tag.'>';

	}else{

		echo $show_content;
	}

} ?>