<?php
if(!defined('IN_CRONLITE'))exit();
?>

<div class="address">
<footer>
<div class="container">
<div class="row">
<div class="col-xs-12 col-md-8 col-lg-9">
<ul class="porduct">
<h4><?php echo __('footer_product')?></h4>
<li><a href="agreement.html" target="_blank"><?php echo __('footer_terms')?></a></li>
<li><a href="doc.html" target="_blank"><?php echo __('footer_dev_doc')?></a></li>
</ul>
<ul class="price">
<h4><?php echo __('footer_about')?></h4>
<li><?php echo $conf['sitename']?><?php echo __('footer_is')?><?php echo $conf['orgname']?><?php echo __('footer_free_sign')?></li>
</ul>
<ul class="about"style="width: 40%;padding-left: 22px;">
<h4><?php echo __('footer_contact')?></h4>
<li><strong>QQ:</strong><a href="https://wpa.qq.com/msgrd?v=3&uin=<?php echo $conf['kfqq']?>&Site=pay&Menu=yes" target="_blank"><?php echo $conf['kfqq']?></a></li>
<li><strong>Email:</strong><a href="mailto:<?php echo $conf['email']?>"><?php echo $conf['email']?></a></li>
</ul>
</div>

</div>
<div class="xinxi">
<p><?php echo $conf['sitename']?>&nbsp;&nbsp;&copy;<?php echo date("Y")?>&nbsp;All Rights Reserved.&nbsp;&nbsp;<?php echo $conf['footer']?></p>
</div>
<script type="text/javascript">
        if('ontouchend' in document.body && $(window).width() < 996){
          $('.col-xs-12 .h2').css('text-align','center');
        }
      </script>
</div>
</footer>
</div>
</div>
</body>
</html> 