<link rel="stylesheet" type="text/css" href="basic.css">
	<style type="text/css">
		.container{
			width: 1200px;
			margin: 0 auto;
		}
		.error{
			height: 400px;
			border-bottom: solid 1px #e0e0e0;
		}
		.error .photo{
			width: 400px;
			height: 400px;
			float: left;
			background: url('http://www.youjuke.com/images/error.png') no-repeat center;
		}
		.error .font{
			float: left;
			margin-left: 40px;
			margin-top: 100px;
		}
		.error .font h2{
			font-size: 30px;
			color: #000;
			margin-bottom: 40px;
			font-weight: normal;
		}
		.error .font p{
			font-size: 12px;
			color: #666;
			margin-bottom: 10px;
		}
		.error .font p a{
			color: #009933;
		}
		.error .font ul{
			margin-bottom: 10px;
		}
		.error .font ul li{
			float: left;
			margin-right: 12px;
			font-size: 12px;
			color: #666;
			height: 13px;
			border-right: solid 1px #cccccc;
			padding-right: 12px;
		}
		.error .font ul .first,.error .font ul .last{
			border-right: 0 none;
			padding-right: 0;
		}
		.error .font ul li a{
			color: #000;
			line-height: 13px;
		}
		.home_zh{
			padding: 50px 0;
		}
		.home_zh h2{
			text-align: center;
			font-size: 24px;
			color: #ff6600;
			margin-bottom: 40px;
		}
		.home_zh ul{
			text-align: center;
		}
		.home_zh ul li{
			display: inline-block;
			font-size: 14px;
			color: #000;
			height: 40px;
			line-height: 40px;
			margin-right: 12px;
		}
		.home_zh ul li input{
			width: 60px;
			height: 40px;
			border: 0;
			background: #f6f6f6;
			text-align: center;
			font-size: 36px;
			vertical-align: middle;
			margin: 0 8px;
			font-family: Arial Narrow !important;
		}
		.home_zh ul .mianji{
			margin-right: 52px;
		}
		.home_zh ul .mianji input{
			width: 80px;
		}
		.home_zh .tijaio{
			margin-top: 30px;
			text-align: center;
		}
		.home_zh .tijaio .login_submit{
			width: 230px;
			height: 40px;
			background: #ff6600;
			text-align: center;
			line-height: 40px;
			color: #fff;
			border: 0;
			font-size: 18px;
		}
	</style>
</head>
<body>
	<div class="error container">
		<div class="photo"></div>
		<div class="font">
			<h2>抱歉！您访问的页面已失效……</h2>
			<p>您可以直接访问　<a href="<?php echo INDEX_WEB_URL?>">优居客首页</a></p>
			<p>或去其他地方逛逛</p>
			<ul class="clearfix">
				<li class="first">装修服务：</li>
				<li><a href="<?php echo FW_WEB_URL?>">免费定制公司</a></li>
				<li><a href="<?php echo FW_WEB_URL?>/stagejianli.html">免费节点监理</a></li>
				<li><a href="<?php echo FW_WEB_URL?>/zxguwen.html">免费装修顾问</a></li>
				<li><a href="<?php echo FW_WEB_URL?>/zhuangxiutuoguan.html">装修全程托管</a></li>
				<li class="last"><a href="<?php echo FW_WEB_URL?>/mianxifenqi.html">免息家装分期</a></li>
			</ul>
			<ul class="clearfix">
				<li class="first">装修公司：</li>
				<li><a href="<?php echo GS_WEB_URL?>/tlist">百强公司</a></li>
				<li class="last"><a href="<?php echo GD_WEB_URL?>">在建工地</a></li>
			</ul>
			<ul class="clearfix">
				<li class="first">装修攻略：</li>
				<li><a href="<?php echo TK_WEB_URL?>">装修效果图</a></li>
				<li><a href="<?php echo ZX_WEB_URL?>">装修知识</a></li>
				<li class="last"><a href="<?php echo RJ_WEB_URL?>">装修日记</a></li>
			</ul>
		</div>
	</div>
	<div class="container home_zh">
		<h2>算算我家装修要花多少钱</h2>
		<form method="post" action="<?php echo INDEX_WEB_URL.'/baojia.html'?>" name="baojia">
		<ul class="clearfix">
			<li class="mianji">面积<input type="text" name="square" value="70" id="mianji">㎡</li>
			<li>户型<input type="text" id="shi" name="shi" value="2">室</li>
			<li><input type="text" id="my_ting" name="ting" value="1">厅</li>
			<li><input type="text" id="my_chu" name="chu" value="1">厨</li>
			<li><input type="text" id="my_wei" name="wei" value="1">卫</li>
			<li><input type="text" id="my_yang" name="yangtai" value="1">阳台</li>
		</ul>
		<div class="tijaio">
		<input type="submit" value="查看报价清单" class="login_submit disabled">
		</div>
		</form>
	</div>
<script type="text/javascript" src="<?php echo INDEX_WEB_URL?>/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript">
	$(function(){
		$("#mianji").change(function () {
            var pm = parseInt($(this).val());
            if (0 < pm < 500) {
                fenpei(pm);
            }
        });
})

  function fenpei(area) {
    if (area <= 60) {
        $("#shi").val(1);
        $("#my_ting").val(1);
        $("#my_chu").val(1);
        $("#my_wei").val(1);
        $("#my_yang").val(1);
    } else if (area > 60 && area <= 90) {
        $("#shi").val(2);
        $("#my_ting").val(1);
        $("#my_chu").val(1);
        $("#my_wei").val(1);
        $("#my_yang").val(1);
    } else if (area > 90 && area <= 110) {
        $("#shi").val(3);
        $("#my_ting").val(1);
        $("#my_chu").val(1);
        $("#my_wei").val(1);
        $("#my_yang").val(1);
    } else if (area > 110 && area <= 130) {
        $("#shi").val(3);
        $("#my_ting").val(2);
        $("#my_chu").val(1);
        $("#my_wei").val(1);
        $("#my_yang").val(1);
    } else if (area > 130 && area <= 150) {
        $("#shi").val(3);
        $("#my_ting").val(2);
        $("#my_chu").val(1);
        $("#my_wei").val(2);
        $("#my_yang").val(1);
    } else if (area > 150 && area <= 180) {
        $("#shi").val(4);
        $("#my_ting").val(2);
        $("#my_chu").val(1);
        $("#my_wei").val(2);
        $("#my_yang").val(1);
    } else if (area > 180) {
        $("#shi").val(4);
        $("#my_ting").val(2);
        $("#my_chu").val(1);
        $("#my_wei").val(2);
        $("#my_yang").val(1);
    }
}
</script>
