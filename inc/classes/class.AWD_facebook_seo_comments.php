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
    public $version_requiered = '1.0';                    
	
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
		require_once(dirname(__FILE__).'/class.table_comments.php');

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
		
		add_filter('get_comments_number', array(&$this,'set_comments_number'),10,2); 
		add_action('AWD_facebook_save_custom_settings',array(&$this,'hook_post_from_custom_options'));
		add_action('AWD_facebook_seo_comments_clear_cache',array(&$this,'clear_comments_cache'));
	
		if($this->AWD_facebook->options['comments_merge'] == 1)
			add_filter('comments_array', array(&$this,'set_comments_content'),10,2);
		
		if($this->AWD_facebook->options['comments_fb_display'] == 1)
			add_action('comments_template', array(&$this,'print_hidden_fbcomments'));
		
		add_filter('AWD_facebook_comments_array', array(&$this,'set_comments_content'),10,2);   
			
	    add_shortcode('AWD_facebook_comments_hidden',array(&$this,'get_hidden_fbcomments'));
	}
	public function admin_menu()
	{
		$this->plugin_admin_hook = add_submenu_page($this->AWD_facebook->plugin_slug, __('SEO Comments',$this->plugin_text_domain), __('SEO Comments',$this->plugin_text_domain), 'administrator', $this->AWD_facebook->plugin_slug.'_seo_comments', array($this->AWD_facebook,'admin_content'));
		add_meta_box($this->AWD_facebook->plugin_slug."_seo_comments_settings", __('Settings',$this->plugin_text_domain), array(&$this,'plugin_form'), $this->plugin_admin_hook , 'normal', 'core');
		add_meta_box($this->AWD_facebook->plugin_slug."_seo_comments_list", __('Manage comments',$this->plugin_text_domain), array(&$this,'seo_comments_list'), $this->plugin_admin_hook , 'normal', 'core');

		parent::admin_menu();
	}
	public function plugin_form()
	{
		?>
		<div id="div_options_content">
		<form method="POST" action="" id="<?php echo $this->plugin_slug; ?>_form_settings" action="admin.php?page=<?php echo $this->plugin_slug; ?>">
			<div id="seo_comments_settings">
				<div class="uiForm">
					<table class="AWD_form_table">
						<tr class="dataRow" >
							<th class="label"><?php _e('Merge Fb comments with WP comments ?',$this->plugin_text_domain); ?> <?php echo $this->AWD_facebook->get_the_help('comments_merge'); ?></th>
							<td class="data">
								<select id="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_merge" name="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_merge">
									<option value="0" <?php if($this->AWD_facebook->options['comments_merge'] == 0) echo 'selected="selected"'; ?> ><?php _e('No',$this->plugin_text_domain); ?></option>
									<option value="1" <?php if($this->AWD_facebook->options['comments_merge'] == 1) echo 'selected="selected"'; ?>><?php _e('Yes',$this->plugin_text_domain); ?></option>
								</select>
							</td>
						</tr>
						<tr class="dataRow">
							<th class="label"><?php _e('Cache option',$this->plugin_text_domain); ?> <?php echo $this->AWD_facebook->get_the_help('comments_cache'); ?></th>
							<td class="data">
								<select id="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_cache" name="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_cache">
									<option value="0" <?php if($this->AWD_facebook->options['comments_cache'] == "0") echo 'selected="selected"'; ?> ><?php _e('Disable',$this->plugin_text_domain); ?></option>
									<option value="hourly" <?php if($this->AWD_facebook->options['comments_cache'] == "hourly") echo 'selected="selected"'; ?>><?php _e('Hourly',$this->plugin_text_domain); ?></option>
									<option value="twicedaily" <?php if($this->AWD_facebook->options['comments_cache'] == "twicedaily") echo 'selected="selected"'; ?>><?php _e('Twice daily',$this->plugin_text_domain); ?></option>
									<option value="daily" <?php if($this->AWD_facebook->options['comments_cache'] == "daily") echo 'selected="checked"'; ?>><?php _e('Daily',$this->plugin_text_domain); ?></option>
								</select><br />
								<?php
								if($this->AWD_facebook->options['comments_cache'] != "0"){
									_e('Next cache cleaning:',$this->plugin_text_domain); 								
									echo '<br /><strong> ';
									$next_clean = wp_next_scheduled('AWD_facebook_seo_comments_clear_cache');
									if($next_clean != '')
										echo get_date_from_gmt(date("Y-m-d H:i:s",wp_next_scheduled('AWD_facebook_seo_comments_clear_cache'))).'</strong>';
									else
										echo __('Disabled',$this->plugin_text_domain);
								}
								?>
							</td>
						</tr>
						<tr class="dataRow">
							<th class="label"><?php _e('Add FB comments to html with no diplsay ?',$this->plugin_text_domain); ?> <?php echo $this->AWD_facebook->get_the_help('comments_fb_display'); ?></th>
							<td class="data">
								<select id="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_fb_display" name="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_fb_display">
									<option value="0" <?php if($this->AWD_facebook->options['comments_fb_display'] == 0) echo 'selected="selected"'; ?> ><?php _e('No',$this->plugin_text_domain); ?></option>
									<option value="1" <?php if($this->AWD_facebook->options['comments_fb_display'] == 1) echo 'selected="selected"'; ?>><?php _e('Yes',$this->plugin_text_domain); ?></option>
								</select>
							</td>
						</tr>
						<tr class="dataRow">
							<th class="label"><?php _e('Merge Fb comments count with WP comments count ?',$this->plugin_text_domain); ?> <?php echo $this->AWD_facebook->get_the_help('comments_count_merge'); ?></th>
							<td class="data">
								<select id="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_count_merge" name="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_count_merge">
									<option value="0" <?php if($this->AWD_facebook->options['comments_count_merge'] == 0) echo 'selected="selected"'; ?>><?php _e('No',$this->plugin_text_domain); ?></option>
									<option value="1" <?php if($this->AWD_facebook->options['comments_count_merge'] == 1) echo 'selected="selected"'; ?>><?php _e('Yes',$this->plugin_text_domain); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<?php wp_nonce_field($this->AWD_facebook->plugin_slug.'_update_options',$this->AWD_facebook->plugin_option_pref.'_nonce_options_update_field'); ?>
			<div class="center">
				<a href="#" id="submit_settings" class="uiButton uiButtonSubmit"><?php _e('Save all settings',$this->AWD_facebook->plugin_text_domain); ?></a>
				<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZQ2VL33YXHJLC" target="_blank" title="Please help me by making a donation. This will contribute to support this free plugin." class="floatright uiButton uiButtonNormal"><?php _e('Make a donation!',$this->plugin_text_domain); ?></a>
			</div>
		</form>
		</div>
		<?php
		/**
		* Javascript for admin
		*/
		?>
		<script type="text/javascript">
			jQuery(document).ready( function($){				
				$('#submit_settings').click(function(e){
					e.preventDefault();
					$('#<?php echo $this->plugin_slug; ?>_form_settings').submit();
				});
			});
		</script>
		<?php
		//help file
		include_once(dirname(dirname(__FILE__)).'/help/help_settings.php');
	}
	public function hook_post_from_custom_options()
	{
		//clear cache if we deactivate it.
		if($_POST[$this->AWD_facebook->plugin_option_pref.'comments_cache'] == "0"){		
			wp_clear_scheduled_hook('AWD_facebook_seo_comments_clear_cache');
			do_action('AWD_facebook_seo_comments_clear_cache');
		}elseif(
		$_POST[$this->AWD_facebook->plugin_option_pref.'comments_cache'] == "daily" ||
		$_POST[$this->AWD_facebook->plugin_option_pref.'comments_cache'] == "twicedaily" ||
		$_POST[$this->AWD_facebook->plugin_option_pref.'comments_cache'] == "hourly"){
			//clear then add an event scheduled.
			wp_clear_scheduled_hook('AWD_facebook_seo_comments_clear_cache');			
			wp_schedule_event(time(), $_POST[$this->AWD_facebook->plugin_option_pref.'comments_cache'], 'AWD_facebook_seo_comments_clear_cache');
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
				$count +=  $this->AWD_facebook_comments->get_comments_count();
		}
		return $count;
	}
	
	
	public function seo_comments_list()
	{
    	$this->AWD_facebook_comments->set_AWD_facebook();
    	$this->AWD_facebook_comments->comments_url = $_REQUEST['s'];
		$this->AWD_facebook_comments->get_comments_id_by_url();
    	?>
    	<div class="ui-state-highlight"><?php printf(__('It is currently not possible to remove comments from the comments box via the Graph API. You can moderate comments to hide/boost a comment or ban a user from the Developer App (http://developers.facebook.com/apps) or directly from the comments box (provided the appropriate open graph meta tags are added). You can see Comments here, but to manage them you must use tools from facebook. %sManage FB comments%s',$this->plugin_text_domain),'<br /><p class="right"><a href="https://developers.facebook.com/tools/comments?id='.$this->AWD_facebook->options['app_id'].'" class="uiButton uiButtonNormal" target="_blank">','</a></p>'); ?></div><br />
		
		<?php
    	//post a comment on the specified url
    	$comment_to_post = $_POST[$this->plugin_slug.'comments_area'];
		if($comment_to_post){
			if($comment_to_post != ''){	
				$comment_posted = $this->AWD_facebook_comments->post_comment($comment_to_post);
				if($comment_posted['id'])
					echo '<div class="ui-state-highlight fadeOnload"><p>'.__('Comment was posted',$this->plugin_text_domain).'</p></div>';
				else
					echo '<div class="ui-state-error"><p>'.__('Sorry there is an error, comment was not posted.',$this->plugin_text_domain).'</p></div>';
			}else{
				echo '<div class="ui-state-error"><p>'.__('Sorry but you must enter a comment.',$this->plugin_text_domain).'</p></div>';
			}
		}
		
    	$AWD_facebook_table_comments = new AWD_facebook_table_comments($this);
    	$AWD_facebook_table_comments->prepare_items(); 
    	?>
    	
        <form id="<?php echo $this->plugin_slug; ?>comments-filter" action="admin.php?page=<?php echo $this->plugin_slug; ?>" method="POST">
            <table cellspacing="5">
                 </tr>
                    <td>
                        <select onchange="jQuery('#<?php echo  $this->plugin_slug.'_search-search-input'; ?>').val(jQuery(this).val()); " id="AWD_select_post">
				        	<option value=""><?php _e('Search by posts',$this->plugin_text_domain); ?></option>
                           	<?php
                            $posts = new WP_Query(array( 'post_type' => array( 'post', 'page'),'posts_per_page'=>-1,'nopaging')); ?>
                            <?php while ( $posts->have_posts() ) : $posts->the_post(); ?>
                                <option <?php if(get_permalink() == $_REQUEST['s']){ echo 'selected="selected"';} ?> value="<?php the_permalink(); ?>"><?php the_title(); ?></option>
                            <?php endwhile; wp_reset_query(); ?>
                        </select>
                    </td>
                    <td>
                         <select id="AWD_select_nb" name="nb_page">
                            <option <?php if(10 == $_REQUEST['nb_page']){ echo 'selected="selected"';} ?> value="10">10 / page</option>
                            <option <?php if(20 == $_REQUEST['nb_page']){ echo 'selected="selected"';} ?> value="20">20 / page</option>
                            <option <?php if(50 == $_REQUEST['nb_page']){ echo 'selected="selected"';} ?> value="50">50 / page</option>
                            <option <?php if(100 == $_REQUEST['nb_page']){ echo 'selected="selected"';} ?> value="100">100 / page</option>
                         </select>
                    </td>
                </tr>
            </table>
            <br />
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <?php $AWD_facebook_table_comments->search_box(__('Search URL',$this->plugin_text_domain), $this->plugin_slug.'_search' ); ?> 
            <?php $AWD_facebook_table_comments->display(); ?>
        </form>
		<br />
		<h3><?php _e('Add a comment on Facebook',$this->plugin_text_domain); ?></h3>
		<?php if($this->AWD_facebook->is_user_logged_in_facebook()): ?>
			<form id="<?php echo $this->plugin_slug; ?>comments-post" action="admin.php?page=<?php echo $this->plugin_slug; ?>&s=<?php echo urlencode($_REQUEST['s']); ?>" method="post">
				<textarea style="display:block; width:100%; margin-bottom: 5px" id="<?php echo $this->plugin_slug; ?>comments_area" name="<?php echo $this->plugin_slug; ?>comments_area" class="uiTextarea"></textarea>
				<a href="#" class="uiButton uiButtonSubmit" id="comment_submit"><?php _e('Submit Comment',$this->plugin_text_domain); ?></a>
			</form>
		<?php else: ?>
			<p class="ui-state-highlight"><?php _e('You must be logged in with Facebook to comment',$this->plugin_text_domain); ?></p>
		<?php endif; 
		$style_js = '
        <style type="text/css">
        	th#like.manage-column {
        		width:8%;
        	}
        	th#comment.manage-column {
        		width:60%;
        	}
        	td.like {
        		font-weight:bold;
        		color:#627AAD;
        	}
        </style>
        <script type="text/javascript">
			jQuery(document).ready(function($){
				$("#search_submit").click(function(e){
					e.preventDefault();
					$("#'.$this->plugin_slug.'comments-filter").submit();
					$("body").css("cursor", "progress");
				});
				jQuery("#comment_submit").click(function(e){
					e.preventDefault();
					$("#'.$this->plugin_slug.'comments-post").submit();
					$("body").css("cursor", "progress");
				});
			});
        </script>
        ';
        echo $style_js;
    }
}