<?php
/*
*
* AWD_facebook_seo_comments class | AWD FCBK SEO comments
* (C) 2012 AH WEB DEV
* Hermann.alexandre@ahwebdev.fr
*
*/
Class AWD_facebook_seo_comments extends AWD_facebook_plugin_abstract
{
	//****************************************************************************************
	//	VARS
	//****************************************************************************************
	public $AWD_facebook;
    public $plugin_slug = 'awd_fcbk_seo_comments';
    public $plugin_name = 'Facebook AWD Seo Comments';
    public $plugin_text_domain = 'AWD_facebook_seo_comments';
    public $version_requiered = '1.4';                    
	
	//****************************************************************************************
	//	INIT
	//****************************************************************************************
	/**
	 * plugin init
	 */
	public function __construct($file,$AWD_facebook)
	{
		parent::__construct(__FILE__,$AWD_facebook);

	    require_once(dirname(__FILE__).'/class.AWD_facebook_comments_base.php');

		//init the object to manage comments into blog and Facebook
		$this->AWD_facebook_comments = new AWD_facebook_comments_base($this->AWD_facebook);
	}
	
	//****************************************************************************************
	//	Extended methods
	//****************************************************************************************
	public function deactivation()
	{
		wp_clear_scheduled_hook('AWD_facebook_seo_comments_clear_cache');
	}
	
	
	public function initialisation()
	{
		parent::init();
		add_action('AWD_facebook_save_custom_settings',array(&$this,'hook_post_from_custom_options'));
		add_action('AWD_facebook_seo_comments_clear_cache',array(&$this,'clear_comments_cache'));
		add_filter('AWD_facebook_comments_array', array(&$this,'set_comments_content'),10,2);   
	    add_shortcode('AWD_facebook_comments_hidden',array(&$this,'get_hidden_fbcomments'));
	    
	    if($this->AWD_facebook->options['comments_merge'] == 1){
			add_filter('comments_array', array(&$this,'set_comments_content'),10,2);
		}
		
		if($this->AWD_facebook->options['comments_fb_display'] == 1){
			add_action('comments_template', array(&$this,'print_hidden_fbcomments'));
		}
		
		if($this->AWD_facebook->options['comments_count_merge'] == 1){
			add_filter('get_comments_number', array(&$this,'set_comments_number'),10,2); 
		}
	}
	
	public function default_options($options){
		$options = parent::default_options($options);
		$options['comments_merge'] = $options['comments_merge'] != '' ? $options['comments_merge'] : 0;
		$options['comments_fb_display'] = $options['comments_fb_display'] != '' ? $options['comments_fb_display'] : 0;
		$options['comments_count_merge'] = $options['comments_count_merge'] != '' ? $options['comments_count_merge'] : 0;
		$options['comments_cache'] = $options['comments_cache'] != '' ? $options['comments_cache'] : 3600;
		return $options;
	}
	
	public function admin_menu()
	{
		$this->plugin_admin_hook = add_submenu_page($this->AWD_facebook->plugin_slug, __('SEO Comments',$this->plugin_text_domain), '<img src="'.$this->plugin_url_images.'facebook_seocom-mini.png" /> '.__('SEO Comments',$this->plugin_text_domain), 'administrator', $this->AWD_facebook->plugin_slug.'_seo_comments', array($this->AWD_facebook,'admin_content'));
		add_meta_box($this->AWD_facebook->plugin_slug."_seo_comments_settings", __('Settings',$this->plugin_text_domain).' <img src="'.$this->plugin_url_images.'facebook_seocom-mini.png" />', array(&$this,'admin_form'), $this->plugin_admin_hook , 'normal', 'core');
		parent::admin_menu();
	}
	
	public function admin_form()
	{
		$form = new AWD_facebook_form('form_settings', 'POST', '', $this->AWD_facebook->plugin_option_pref);
		echo $form->start();
		?>
		<div class="row">
			<?php
			echo $form->addSelect(__('Merge Fb comments with WP',$this->plugin_text_domain).' '.$this->AWD_facebook->get_the_help('comments_merge'), 'comments_merge', array(
				array('value'=>0, 'label'=>__('No',$this->plugin_text_domain)),
				array('value'=>1, 'label'=>__('Yes',$this->plugin_text_domain))									
			), $this->AWD_facebook->options['comments_merge'], 'span3', array('class'=>'span2'));
			?>
			<?php
			echo $form->addInputText(__('Cache option',$this->plugin_text_domain).' '.$this->AWD_facebook->get_the_help('comments_cache'), 'comments_cache', $this->AWD_facebook->options['comments_cache'], 'span4', array('class'=>'span1'), 'icon-repeat','<span class="add-on">S</span>');
			?>
		</div>
		<div class="row">
			<?php
			echo $form->addSelect(__('Add hidden FB comments to html',$this->plugin_text_domain).' '.$this->AWD_facebook->get_the_help('comments_fb_display'), 'comments_fb_display', array(
				array('value'=>0, 'label'=>__('No',$this->plugin_text_domain)),
				array('value'=>1, 'label'=>__('Yes',$this->plugin_text_domain))									
			), $this->AWD_facebook->options['comments_fb_display'], 'span3', array('class'=>'span2'));
			
			echo $form->addSelect(__('Merge Fb comments count',$this->plugin_text_domain).' '.$this->AWD_facebook->get_the_help('comments_count_merge'), 'comments_count_merge', array(
				array('value'=>0, 'label'=>__('No',$this->plugin_text_domain)),
				array('value'=>1, 'label'=>__('Yes',$this->plugin_text_domain))									
			), $this->AWD_facebook->options['comments_count_merge'], 'span3', array('class'=>'span2'));
			?>
		</div>
		<?php wp_nonce_field($this->AWD_facebook->plugin_slug.'_update_options',$this->AWD_facebook->plugin_option_pref.'_nonce_options_update_field'); ?>
		<div class="form-actions">
			<a href="#" id="submit_settings" class="btn btn-primary"><i class="icon-cog icon-white"></i> <?php _e('Save all settings',$this->plugin_text_domain); ?></a>
			<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZQ2VL33YXHJLC" class="awd_tooltip_donate btn pull-right" id="help_donate" target="_blank" class="btn pull-right"><i class="icon-heart"></i> <?php _e('Donate!',$this->plugin_text_domain); ?></a>
		</div>
		<?php 
		echo $form->end();
		//help file
		include_once(dirname(dirname(__FILE__)).'/help/help_settings.php');
	}
	
	public function hook_post_from_custom_options()
	{
		//clear cache if we deactivate it.
		if($_POST[$this->AWD_facebook->plugin_option_pref.'comments_cache'] == "0"){		
			do_action('AWD_facebook_seo_comments_clear_cache');
		}
	}
	
	
	//****************************************************************************************
	//	Self methods
	//****************************************************************************************
	public function clear_comments_cache()
	{
		$this->AWD_facebook->wpdb->query("DELETE FROM ".$this->AWD_facebook->wpdb->postmeta." WHERE post_id !='' AND (meta_key = '_".$this->AWD_facebook->plugin_option_pref."cache_fb_comments_array' OR meta_key = '_".$this->AWD_facebook->plugin_option_pref."cache_fb_comments_infos' OR meta_key = '_".$this->AWD_facebook->plugin_option_pref."cache_fb_comments_status') ");
	}
	
	public function print_hidden_fbcomments($post_id='')
	{
		echo $this->get_hidden_fbcomments($post_id);
	}
	
	public function get_hidden_fbcomments($post_id='')
	{
		if(!is_int($post_id)){
			global $post;
			$post_id = $post->ID;
		}
		$html = "\n".'<!-- '.$this->plugin_name.' Hidden Comments -->'."\n";
		$fb_comments = apply_filters('AWD_facebook_comments_array','',$post_id);
		if(is_array($fb_comments)){
			$html .= '<div class="AWD_fb_comments_hidden" style="display:none;">';
				foreach($fb_comments as $comment){
					$html .= '<div class="AWD_fb_comment_hidden">';
						$html .= '<span class="fb_comment_id">'.$comment->comment_ID.'</span> | ';
						$html .= '<span class="fb_comment_author"><strong>'.$comment->comment_author.'</strong></span>';
						$html .= '<div class="fb_comment_content">'.$comment->comment_content.'</div>';
					$html .= "</div><br />\n";
				}
			$html .= '</div>'."\n";
		}
		$html .='<!-- '.$this->plugin_name.' Hidden Comments End -->'."\n\n";
		return $html;
	}
	
	public function set_comments_content($comment_template,$post_id)
	{
		$this->AWD_facebook_comments->set_AWD_facebook();
		$this->AWD_facebook_comments->comments_url = get_permalink($post_id);
		$this->AWD_facebook_comments->wp_post_id = $post_id;
		
		$response = $this->AWD_facebook_comments->wp_get_comments();
		$comments_wait = array();
		if(is_array($this->AWD_facebook_comments->comments_array)){      
			foreach($this->AWD_facebook_comments->comments_array as $comment){
				$wp_from_fb_comments = $this->AWD_facebook_comments->wp_comments_data_model($comment);
				$comments_wait[] = $wp_from_fb_comments['wp_comment'];
				if(is_array($wp_from_fb_comments['response_comments']))
					foreach($wp_from_fb_comments['response_comments'] as $response_comment)
						$comments_wait[] = $response_comment;
			}
		}
		if(!is_array($comments))
			$comments = array();
		$comments = array_merge($comments_wait,$comments);
		return $comments;
	}
	
	public function set_comments_number($count, $post_id)
	{
		$this->AWD_facebook_comments->set_AWD_facebook();
		$this->AWD_facebook_comments->wp_post_id = $post_id;
		if($this->AWD_facebook->options['comments_count_merge'] == 1){
			$this->AWD_facebook_comments->comments_url = get_permalink($post_id);
			if($this->AWD_facebook->options['comments_cache'] != "0" && $_REQUEST['action'] != 'clear_fb_cache'){
				$this->AWD_facebook_comments->get_comments_from_cache();
				if($this->AWD_facebook_comments->comments_status != 1){	
					$this->AWD_facebook_comments->get_comments_id_by_url();
				}
			}else{
				$this->AWD_facebook_comments->get_comments_id_by_url();
			}	
			if($this->AWD_facebook_comments->get_comments_count() > 0)
				$count =  $count + $this->AWD_facebook_comments->get_comments_count();
		}
		return $count;
	}

}