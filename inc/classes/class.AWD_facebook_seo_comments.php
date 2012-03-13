<?php
Class AWD_facebook_seo_comments extends AWD_facebook_plugins_abstract
{
	//****************************************************************************************
	//	VARS
	//****************************************************************************************
   
    public $plugin_name = 'Facebook AWD Seo Comments';
    public $plugin_slug = 'awd_fcbk_seo_comments';
    public $plugin_text_domain = 'AWD_facebook_seo_comments';
    public $version_requiered = '0.9.9.1';
	public $AWD_facebook;
	
	
	//****************************************************************************************
	//	INIT
	//****************************************************************************************
	/**
	 * plugin init
	 */
	public function __construct($AWD_facebook)
	{
		parent::__construct($AWD_facebook);

	    require_once(ABSPATH.'wp-admin/includes/plugin.php');
	    require_once(dirname(__FILE__).'/class.facebook_comments.php');
		require_once(dirname(__FILE__).'/class.table_comments.php');

		if(is_plugin_inactive('facebook-awd/AWD_facebook.php')){
			add_action('admin_notices',array(&$this,'missing_parent'));
			deactivate_plugins(__FILE__);
		}elseif($this->AWD_facebook->get_version() < $this->version_requiered){
			add_action('admin_notices',array(&$this,'old_parent'));
			deactivate_plugins(__FILE__);
		}else
			add_action('AWD_facebook_plugins_init',array(&$this,'initialisation'));
	}
	
	/**
	* Deactivation fucntion
	* Call when the plugin is deactivated
	*/
	public function deactivation()
	{
		wp_clear_scheduled_hook('AWD_facebook_seo_comments_clear_cache');
	}
	/**
	* Deactivation fucntion
	* Call when the plugin is deactivated
	*/
	public function activation()
	{

	}
	
	/**
	* Real init when AWD_facebook is init too.
	*/
	public function initialisation()
	{
		parent::init(__FILE__);
		
		
		//TODO
		//add_action('wp_enqueue_scripts',array(&$this,'enqueue_scripts'));
		
		//add_filter('get_comments_number', array(&$this,'set_comments_number'),10,2); 
		
		//remove_action('comments_template', array(&$this->AWD_facebook,'the_comments_form'));
		//add_action('comment_form', array(&$this->AWD_facebook,'the_comments_form'));
		//add_filter('comment_form_defaults', array(&$this,'the_comments_form_defaults'),10,1);
		
		
		/*add_action("AWD_facebook_save_custom_settings",array(&$this,'hook_post_from_custom_options'));
		add_action('AWD_facebook_seo_comments_clear_cache',array(&$this,'clear_comments_cache'));
        
	    
	    if($this->AWD_facebook->options['comments_merge'] == 1)
	    	add_filter('comments_array', array(&$this,'set_comments_content'),10,2);
	    
	    if($this->AWD_facebook->options['comments_fb_display'] == 1)
	    	add_action('comments_template', array(&$this,'print_hidden_fbcomments'));
	    */
	    
	    //add_filter('AWD_facebook_comments_array', array(&$this,'set_comments_content'),10,2);
	    
	    //add_shortcode('AWD_facebook_comments_hidden',array(&$this,'get_hidden_fbcomments'));
	    
	}
	
	/**
	* Add info to defaults from WP.
	*/
	public function the_comments_form_defaults($defaults)
	{
		$defaults['must_log_in'] = $defaults['must_log_in']." ".$this->AWD_facebook->get_the_login_button();
		return $defaults;
	}
	
	/**
	* Add submenu
	*/
	public function admin_menu()
	{
		$this->AWD_facebook->blog_admin_seo_comments_hook = add_submenu_page($this->AWD_facebook->plugin_slug, __('SEO Comments',$this->plugin_text_domain), __('SEO Comments',$this->plugin_text_domain), 'administrator', $this->AWD_facebook->plugin_slug.'_seo_comments', array($this->AWD_facebook,'admin_content'));
	}
	
	/**
	* init admin
	*/
	public function admin_init()
	{
		
		add_meta_box($this->AWD_facebook->plugin_slug."_seo_comments_settings", __('Settings',$this->plugin_text_domain), array(&$this,'plugin_form'), $this->plugin_slug.'_box' , 'normal', 'core');
		//add_meta_box($this->AWD_facebook->plugin_slug."_seo_comments_list", __('Manage comments',$this->plugin_text_domain), array(&$this,'seo_comments_list'), $this->plugin_slug.'_box' , 'normal', 'core');
		
		add_action("AWD_facebook_custom_metabox",array(&$this,'do_metabox'));
		
	   
	}
	
	/**
	* Front JS
	*/
	public function enqueue_scripts()
	{
		wp_enqueue_script('jquery');
	}
	/**
	* Admin JS
	*/
	public function admin_enqueue_scripts()
	{
		wp_enqueue_script('jquery');
	}
	
	/**
	* clear All comments Cache
	*/
	public function clear_comments_cache(){
		$query_posts = new WP_Query(array( 'post_type' => array( 'post', 'page'),'posts_per_page'=>-1,'nopaging'));
		//To do do it with 1 query! 
		foreach($query_posts->posts as $post){
			$this->AWD_facebook->wpdb->query( "DELETE FROM ".$this->AWD_facebook->wpdb->postmeta." WHERE post_id = '".$post->ID."' AND meta_key = '_".$this->AWD_facebook->plugin_option_pref."cache_fb_comments_array' ");
		}
	}
	
	/**
	* call metabox in hook Facebook AWD
	*/
	public function do_metabox(){
		if($_GET['page'] == $this->plugin_slug)
			do_meta_boxes($this->plugin_slug.'_box','normal',null);
	}
	
	/**
	* Return Fb comments for hidden state in template
	* Call this with shortcode
	*/
	public function print_hidden_fbcomments($post_id=''){
		echo $this->get_hidden_fbcomments($post_id);
	}
	
	
	public function get_hidden_fbcomments($post_id=''){
		if($post_id == ''){
			global $post;
			$post_id = $post->ID;
		}
		$fb_comments = apply_filters('AWD_facebook_comments_array','',$post_id);
		if(count($fb_comments)>0){
			$html = '<div class="AWD_fb_comments_hidden">';
				foreach($fb_comments as $comment){
					$html .= '<div class="AWD_fb_comment_hidden">';
						$html .= '<span class="fb_comment_id">'.$comment->comment_ID.'</span> | ';
						$html .= '<span class="fb_comment_author"><strong>'.$comment->comment_author.'</strong></span>';
						$html .= '<div class="fb_comment_content">'.$comment->comment_content.'</div>';
					$html .= "</div><br />\n";
				}
			$html .= '</div>';
			$html .= '
			<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery(".AWD_fb_comments_hidden").hide();
				});
			</script>'."\n";
		}
		return $html;
	}
	
	
	
	/**
	* Add fb comments to wp coments front array.
	*/
	public function set_comments_content($comments, $post_id){
		$this->AWD_facebook_comments->set_AWD_facebook();
		$this->AWD_facebook_comments->comments_url = get_permalink($post_id);
		$this->AWD_facebook_comments->wp_post_id = $post_id;
		$this->AWD_facebook_comments->wp_get_comments();
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
	/**
	* return the number of comments WP + FB
	*/
	public function set_comments_number($count, $post_id){
		$this->AWD_facebook_comments->set_AWD_facebook();
		if($this->AWD_facebook->options['comments_count_merge'] == 1){
			if($this->AWD_facebook_comments->get_comments_count() == ''){
				$this->AWD_facebook_comments->comments_url = get_permalink($post_id);
				$this->AWD_facebook_comments->get_comments_id_by_url();
			}
			$count += $this->AWD_facebook_comments->comments_count;
		}
		return $count;
	}
	/**
	* Add content option comments + general ADMIN
	*/
	public function seo_comments_list(){
    	$this->AWD_facebook_comments->set_AWD_facebook();
    	$this->AWD_facebook_comments->comments_url = $_REQUEST['s'];
        $this->AWD_facebook_comments->comments_url == '' ? $this->AWD_facebook_comments->comments_url = home_url() : '';
		$this->AWD_facebook_comments->get_comments_id_by_url();
    	?>
    	<div class="ui-state-highlight"><?php printf(__('It is currently not possible to remove comments from the comments box via the Graph API. You can moderate comments to hide/boost a comment or ban a user from the Developer App (http://developers.facebook.com/apps) or directly from the comments box (provided the appropriate open graph meta tags are added). You can see Comments here, but to manage them you must use tools from facebook. %sManage FB comments%s',$this->plugin_text_domain),'<br /><p class="right"><a href="https://developers.facebook.com/apps/'.$this->AWD_facebook->options['app_id'].'/plugins" class="uiButton uiButtonNormal" target="_blank">','</a></p>'); ?></div><br />
		
		
		
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
		
		
    	
    	$AWD_facebook_table_comments = new AWD_facebook_table_comments();
    	$AWD_facebook_table_comments->prepare_items(); ?>
    	
        <form id="<?php echo $this->plugin_slug; ?>comments-filter" action="admin.php?page=<?php echo $this->plugin_slug; ?>" method="POST">
            <table cellspacing="5">
                 </tr>
                    <td>
                        <select class="uiSelectHTML" onchange="onchange_uiSelect(this.id); jQuery('#<?php echo  $this->plugin_slug.'_search-search-input'; ?>').val(jQuery(this).val()); " id="AWD_select_post">
				        	<option value=""><?php _e('Search by posts',$this->plugin_text_domain); ?></option>
                           	<?php
                            $posts = new WP_Query(array( 'post_type' => array( 'post', 'page'),'posts_per_page'=>-1,'nopaging')); ?>
                            <?php while ( $posts->have_posts() ) : $posts->the_post(); ?>
                                <option <?php if(get_permalink() == $_REQUEST['s']){ echo 'selected="selected"';} ?> value="<?php the_permalink(); ?>"><?php the_title(); ?></option>
                            <?php endwhile; wp_reset_query(); ?>
                        </select>
                    </td>
                    <td>
                         <select class="uiSelectHTML" onchange="onchange_uiSelect(this.id);" id="AWD_select_nb" name="nb_page">
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
		<?php if($this->AWD_facebook->uid): ?>
			<form id="<?php echo $this->plugin_slug; ?>comments-post" action="admin.php?page=<?php echo $this->plugin_slug; ?>&s=<?php echo urlencode($_REQUEST['s']); ?>" method="post">
				<textarea style="display:block; width:100%; margin-bottom: 5px" id="<?php echo $this->plugin_slug; ?>comments_area" name="<?php echo $this->plugin_slug; ?>comments_area" class="uiTextarea"></textarea>
				<a href="#" class="uiButton uiButtonSubmit" id="comment_submit"><?php _e('Submit Comment',$this->plugin_text_domain); ?></a>
			</form>
		<?php else: ?>
			<p class="ui-state-highlight"><?php _e('You must be logged in with Facebook to comment',$this->plugin_text_domain); ?></p>
		<?php endif; ?>

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
			jQuery(document).ready(function(){
				jQuery('#search_submit').click(function(e){
					e.preventDefault();
					jQuery('#<?php echo $this->plugin_slug; ?>comments-filter').submit();
					jQuery("body").css("cursor", "progress");
					return false;
				});
				jQuery('#comment_submit').click(function(e){
					e.preventDefault();
					jQuery('#<?php echo $this->plugin_slug; ?>comments-post').submit();
					jQuery("body").css("cursor", "progress");
					return false;
				});
				
			});
        </script>
		<?php
    }
	
	/**
	* Add a line for menu plugins
	*/
	public function plugin_menu(){
		?>
		<li><a href="#seo_comments_settings"><?php _e('SEO Comments',$this->plugin_text_domain); ?></a></li>
		<?php
	}
	
	/**
	* Add custom action to hook post in plugins
	* Options from $_POST and with the correct prefix (plugin_slug) will be saved in database options.
	* TO not save in, unset the var.
	*/
	public function hook_post_from_custom_options(){
		//clear cache if we deactivate it.
		if($_POST[$this->AWD_facebook->plugin_option_pref.'comments_cache'] == "0"){		
			wp_clear_scheduled_hook('AWD_facebook_seo_comments_clear_cache');
			do_action('AWD_facebook_seo_comments_clear_cache');
		}elseif($_POST[$this->AWD_facebook->plugin_option_pref.'comments_cache'] == "daily" || $_POST[$this->AWD_facebook->plugin_option_pref.'comments_cache'] == "twicedaily" || $_POST[$this->AWD_facebook->plugin_option_pref.'comments_cache'] == "hourly"){
			//clear then add an event scheduled.
			wp_clear_scheduled_hook('AWD_facebook_seo_comments_clear_cache');			
			wp_schedule_event(time(), $_POST[$this->AWD_facebook->plugin_option_pref.'comments_cache'], 'AWD_facebook_seo_comments_clear_cache');
		}
	}
	
	/**
	* add content admin
	*/
	public function plugin_form(){
		?>
		<form method="POST" action="" id="<?php echo $this->plugin_slug; ?>_form_settings" action="admin.php?page=<?php echo $this->plugin_slug; ?>">
			<div id="seo_comments_settings">
				<div class="uiForm">
					<table class="AWD_form_table">
						<tr class="dataRow" >
							<th class="label"><?php _e('Merge Fb comments with WP comments ?',$this->plugin_text_domain); ?> <?php echo $this->AWD_facebook->get_the_help('comments_merge'); ?></th>
							<td class="data">
								<select id="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_merge" name="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_merge" class="uiSelectHTML" onchange="onchange_uiSelect(this.id);">
									<option value="0" <?php if($this->AWD_facebook->options['comments_merge'] == 0) echo 'selected="selected"'; ?> ><?php _e('No',$this->plugin_text_domain); ?></option>
									<option value="1" <?php if($this->AWD_facebook->options['comments_merge'] == 1) echo 'selected="selected"'; ?>><?php _e('Yes',$this->plugin_text_domain); ?></option>
								</select>
							</td>
						</tr>
						<tr class="dataRow">
							<th class="label"><?php _e('Cache option',$this->plugin_text_domain); ?> <?php echo $this->AWD_facebook->get_the_help('comments_cache'); ?></th>
							<td class="data">
								<select id="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_cache" name="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_cache" class="uiSelectHTML" onchange="onchange_uiSelect(this.id);">
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
								<select id="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_fb_display" name="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_fb_display" class="uiSelectHTML" onchange="onchange_uiSelect(this.id);">
									<option value="0" <?php if($this->AWD_facebook->options['comments_fb_display'] == 0) echo 'selected="selected"'; ?> ><?php _e('No',$this->plugin_text_domain); ?></option>
									<option value="1" <?php if($this->AWD_facebook->options['comments_fb_display'] == 1) echo 'selected="selected"'; ?>><?php _e('Yes',$this->plugin_text_domain); ?></option>
								</select>
							</td>
						</tr>
						<tr class="dataRow">
							<th class="label"><?php _e('Merge Fb comments count with WP comments count ?',$this->plugin_text_domain); ?> <?php echo $this->AWD_facebook->get_the_help('comments_count_merge'); ?></th>
							<td class="data">
								<select id="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_count_merge" name="<?php echo $this->AWD_facebook->plugin_option_pref; ?>comments_count_merge" class="uiSelectHTML" onchange="onchange_uiSelect(this.id);">
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
			</div>
		</form>
		<?php
		/**
		* Javascript for admin
		*/
		?>
		<script type="text/javascript">
			jQuery(document).ready( function(){				
				jQuery('#submit_settings').click(function(){
					jQuery('#<?php echo $this->plugin_slug; ?>_form_settings').submit();
					jQuery("body").css("cursor", "progress");
					return false;
				});
			});
		</script>
		<?php
		//help file
		include_once(dirname(__FILE__).'/inc/help/help_settings.php');
	}
	
	/**
	* Add notice if Facebook AWD is not present and activated
	*/
	public function missing_parent(){
		echo '<div class="error"><p>'.$this->plugin_name.' '.__("can not be activated: Facebook AWD All in One plugin must be installed... you can download it from the Wordpress plugin directory",$this->plugin_text_domain).'</p></div>';
	}
	/**
	* Add notice if Facebook AWD fb connect is disable
	* we need it to use API on facebook.
	*/
	public function no_fbconnect_api(){
		echo '<div class="error"><p>'.$this->plugin_name.' '.__("can not be activated: fb Connect module of Facebook AWD must be activated first... you can do it in plugin settings of Facebook AWD",$this->plugin_text_domain).'</p></div>';

	}
	/**
	* Add notice if Facebook AWD is too old
	*/
	public function old_parent(){
			echo '<div class="error"><p>'.$this->plugin_name.' '.__("can not be activated: Facebook AWD All in One plugin is out to date... You can download the last version or update it from the Wordpress plugin directory",$this->plugin_text_domain).'</p></div>';
	}
}