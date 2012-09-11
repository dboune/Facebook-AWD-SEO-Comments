<?php
/*
*
* Help settings Admin AWD FCBK
* (C) 2012 AH WEB DEV
* Hermann.alexandre@ahwebdev.fr
*
*/
?>
<div class="header_lightbox_help_title hidden"><img style="vertical-align:middle;" src="<?php echo $this->AWD_facebook->plugin_url_images; ?>facebook-mini.png" alt="facebook logo"/> <?php _e('Help',$this->plugin_text_domain); ?></div>
<div id="lightbox_help_comments_merge" class="hidden">
	<p>
	<?php _e("This will merge All Facebook comments with your native Wordpress comments, If cache is enabled, Fb comments will be saved in post meta to prevent longs requests",$this->plugin_text_domain); ?>
	</p>
</div>
<div id="lightbox_help_comments_cache" class="hidden">
	<p>
	<?php _e("Scheduled task to clean the Facebook comments cache",$this->plugin_text_domain); ?>
	</p>
</div>
<div id="lightbox_help_comments_fb_display" class="hidden">
	<p>
	<?php _e("This will add All Facebook comments in html format in posts and hide them with javascript when page is loaded. It's a good way to boost SEO without add comments in Wordpress comments loop",$this->plugin_text_domain); ?>
	</p>
</div>
<div id="lightbox_help_comments_count_merge" class="hidden">
	<p>
	<?php _e("This will merge Facebook comments count with your native wordpress comments count on each post",$this->plugin_text_domain); ?>
	</p>
</div>
