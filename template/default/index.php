<?php
if(!defined('IN_CRONLITE'))exit();
require INDEX_ROOT.'head.php';
?>
<section class="screen1">
<div id="myCarousel"class="carousel slide">
<div class="carousel-inner">

<div class="item active">
<div class="banner2 banner3">
<div class="container">
<div class="row">

<div class="col-xs-12 col-sm-6 col-md-6">
<div class="ban2_img">
<div class="cloud_db_img"><img src="<?php echo STATIC_ROOT?>images/banner4.png"class="img-responsive"></div>
</div>
</div>
<div class="col-xs-12 col-sm-6 col-md-6">
<div class="ban2_text">
<div class="ban2_status docker">
<div class="ban2_middle"><?php echo __('welcome_to')?><?php echo $conf['sitename']?></div>
<div class="ban2_content"><?php echo __('provide_pay_service')?></div>

                        <div class="ban2_experience">
<a href="/user/"class="btn proceed"><?php echo __('login_merchant')?></a>&nbsp;&nbsp;<a href="/user/reg.php"class="btn proceed"><?php echo __('register_merchant_btn')?></a><br/>
                      </div>
</div>
</div>
</div>
</div>
</div>
</div>

</div>
<!--   <ol class="carousel-indicators">
<li data-target="#myCarousel"data-slide-to="1"class="active"></li>
</ol>
<div id="trun_left"><a href="#myCarousel"data-slide="prev"class="_left">&lsaquo;</a></div>
<div id="trun_right"><a href="#myCarousel"data-slide="next"class="_right">&rsaquo;</a></div>-->
</div>

</section>
   
   <section class="screen3">
<div class="container">
<div class="row">
<div class="col-xs-12 cloud_server">
<div class="h3"><?php echo $conf['sitename']?>®<?php echo __('free_sign_product')?></div>
</div>
<div class="col-xs-6 col-sm-4 col-md-4">
<div id="container_server">
<div class="server_item container_server"></div>
<div class="server-head h4"><?php echo __('multi_pay_methods')?>
<div class="h5 text-center"><?php echo __('multi_pay_desc')?></div>
</div>
</div>
</div>
<div class="col-xs-6 col-sm-4 col-md-4">
<div id="server-arrange">
<div class="server_item arrange"></div>
<div class="server-head h4"><?php echo __('low_rate')?>
<div class="h5 text-center"><?php echo __('low_rate_desc')?></div>
</div>
</div>
</div>
<div class="col-xs-6 col-sm-4 col-md-4">
<div id="codebuild">
<div class="server_item codebuild"></div>
<div class="server-head h4"><?php echo __('auto_withdraw')?>
<div class="h5 text-center"><?php echo __('auto_withdraw_desc')?></div>
</div>
</div>
</div>


</div>
</div>
</section>

  
    <section class="screen4">
<div class="container">
<div class="row">
<div class="col-xs-12 blog-head">
<div class="h3"><?php echo __('partners')?></div>
<div class="col-xs-3">
<img src="<?php echo STATIC_ROOT?>images/alipay.png" width="150">
</div>
<div class="col-xs-3">
<img src="<?php echo STATIC_ROOT?>images/wxpay.png" width="150">
</div>
<div class="col-xs-3">
<img src="<?php echo STATIC_ROOT?>images/qqpay.png" width="150">
</div>
<div class="col-xs-3">
<img src="<?php echo STATIC_ROOT?>images/tenpay.png" width="150">
</div>
</div>
          
          
        </div>
</div>
</section>

<?php require INDEX_ROOT.'foot.php';?>