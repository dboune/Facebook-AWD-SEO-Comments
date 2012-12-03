<?php
/**
 * 
 * @author alexhermann
 *
 */
Class AWD_facebook_seo_comments extends AWD_facebook_plugin_abstract
{
	/**
	 * The Slug of the plugin
	 * @var string
	 */
	public $plugin_slug = 'awd_fcbk_seo_comments';

	/**
	 * The Name of the plugin
	 * @var string
	 */
	public $plugin_name = 'Facebook AWD Seo Comments';

	/**
	 * The text domain of the plugin
	 * @var string
	 */
	public $ptd = 'AWD_facebook_seo_comments';

	/**
	 * The version required for AWD_facebook object
	 * @var float
	 */
	public $version_requiered = 1.4;

	/**
	 * The array of deps
	 * @var array
	 */
	public $deps = array('connect' => 1);
	
	/**
	 * The admin kook name
	 * @var string
	 */
	public $plugin_admin_hook;
	//****************************************************************************************
	//	INIT
	//****************************************************************************************
	/**
	 * plugin init
	 */
	public function __construct($file, $AWD_facebook)
	{		
		
		parent::__construct(__FILE__, $AWD_facebook);
		require_once(dirname(__FILE__) . '/class.AWD_facebook_comments_base.php');
		$this->AWD_facebook_comments = new AWD_facebook_comments_base($this->AWD_facebook);
	}

	/**
	 * Initialisation of the Facebook AWD plugin
	 */
	public function initialisation()
	{
		parent::init();
		add_action('AWD_facebook_seo_comments_clear_cache', array(&$this, 'clear_comments_cache'));
		add_shortcode('AWD_facebook_comments_hidden', array(&$this, 'get_hidden_fbcomments'));
		add_filter('get_avatar_comment_types', array(&$this, 'add_comment_type'), 10, 1);
		
		//wait for user to  save the form.
		if(isset($this->AWD_facebook->options['seo_comments'])){
			if ($this->AWD_facebook->options['seo_comments']['merge'] == 1) {
				add_filter('comments_array', array(&$this, 'set_comments_content'), 10, 2);
			}
	
			if ($this->AWD_facebook->options['seo_comments']['display'] == 1) {
				add_action('comments_template', array(&$this, 'print_hidden_fbcomments'));
			}
	
			if ($this->AWD_facebook->options['seo_comments']['count_merge'] == 1) {
				add_filter('get_comments_number', array(&$this, 'set_comments_number'), 10, 2);
			}
		}
	}

	/**
	 * Define default $options
	 * @param array $options
	 */
	public function default_options($options)
	{
		$options = parent::default_options($options);
		$default_options = array();
		$default_options['merge'] = 0;
		$default_options['display'] = 0;
		$default_options['count_merge'] = 'btn';
		$default_options['cache'] = 3600;

		//attach options to Container
		if (!isset($options['seo_comments']))
			$options['seo_comments'] = array();
		$options['seo_comments'] = wp_parse_args($options['seo_comments'], $default_options);
		
		return $options;
	}

	/**
	 * get the admin menu
	 */
	public function admin_menu()
	{		
		$this->plugin_admin_hook = add_submenu_page($this->AWD_facebook->plugin_slug, __('SEO Comments', $this->ptd), '<img src="' . $this->plugin_url_images . 'facebook_seocom-mini.png" /> ' . __('SEO Comments', $this->ptd), 'administrator', $this->AWD_facebook->plugin_slug . '_seo_comments', array($this->AWD_facebook, 'admin_content'));
		if($this->plugin_admin_hook != '')
			add_meta_box($this->AWD_facebook->plugin_slug . "_seo_comments_settings", __('Settings', $this->ptd) . ' <img src="' . $this->plugin_url_images . 'facebook_seocom-mini.png" />', array(&$this, 'admin_form'), $this->plugin_admin_hook, 'normal', 'core');
		parent::admin_menu();
	}

	/**
	 * get he admin form
	 */
	public function admin_form()
	{
		$form = new AWD_facebook_form('form_settings', 'POST', '', $this->AWD_facebook->plugin_option_pref);
		echo $form->start();
		?>
		<div class="row">
			<?php
			echo $form->addSelect(__('Merge Fb comments with WP', $this->ptd) . ' ' . $this->AWD_facebook->get_the_help('seo_comments_merge'), 'seo_comments[merge]', array(array('value' => 0, 'label' => __('No', $this->ptd)), array('value' => 1, 'label' => __('Yes', $this->ptd))), $this->AWD_facebook->options['seo_comments']['merge'], 'span3', array('class' => 'span2'));
			echo $form->addInputText(__('Cache option', $this->ptd) . ' ' . $this->AWD_facebook->get_the_help('seo_comments_cache'), 'seo_comments[cache]', $this->AWD_facebook->options['seo_comments']['cache'], 'span4', array('class' => 'span1'), 'icon-repeat', '<span class="add-on">S</span>');
			?>
		</div>
		<div class="row">
			<?php
			echo $form->addSelect(__('Add hidden FB comments to html', $this->ptd) . ' ' . $this->AWD_facebook->get_the_help('seo_comments_display'), 'seo_comments[display]', array(array('value' => 0, 'label' => __('No', $this->ptd)), array('value' => 1, 'label' => __('Yes', $this->ptd))), $this->AWD_facebook->options['seo_comments']['display'], 'span3', array('class' => 'span2'));
			echo $form->addSelect(__('Merge Fb comments count', $this->ptd) . ' ' . $this->AWD_facebook->get_the_help('seo_comments_count_merge'), 'seo_comments[count_merge]', array(array('value' => 0, 'label' => __('No', $this->ptd)), array('value' => 1, 'label' => __('Yes', $this->ptd))), $this->AWD_facebook->options['seo_comments']['count_merge'], 'span3', array('class' => 'span2'));
			?>
		</div>
		<?php wp_nonce_field($this->AWD_facebook->plugin_slug . '_update_options', $this->AWD_facebook->plugin_option_pref . '_nonce_options_update_field'); ?>
		<div class="form-actions">
			<a href="#" id="submit_settings" class="btn btn-primary" data-loading-text="<i class='icon-time icon-white'></i> <?php _e('Saving settings...', $this->ptd); ?>"><i class="icon-cog icon-white"></i> <?php _e('Save all settings', $this->ptd); ?></a>
			<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZQ2VL33YXHJLC" class="awd_tooltip_donate btn pull-right" id="help_donate" target="_blank" class="btn pull-right"><i class="icon-heart"></i> <?php _e('Donate!', $this->ptd); ?></a>
		</div>
		<?php
		echo $form->end();
		//help file
		include_once(dirname(dirname(__FILE__)) . '/help/help_settings.php');
	}

	/**
	 * Hook Save settings from post settings
	 */
	public function hook_post_from_custom_options()
	{
		//clear cache if we deactivate it.
		if (isset($_POST[$this->AWD_facebook->plugin_option_pref . 'seo_comments']['cache'])) {
			if ($_POST[$this->AWD_facebook->plugin_option_pref . 'seo_comments']['cache'] == "0") {
				$this->AWD_facebook_comments->clear_cache();
			}
		}
	}

	//****************************************************************************************
	//	Self methods
	//****************************************************************************************
	
	/**
	 * Echo list of comments
	 * @param int $post_id
	 */
	public function print_hidden_fbcomments($post_id = null)
	{
		echo $this->get_hidden_fbcomments($post_id);
	}
	
	/**
	 * Return the list of Fb hidden comments
	 * @param int $post_id
	 * @return string
	 */
	public function get_hidden_fbcomments($post_id = null)
	{
		if (!is_int($post_id)) {
			global $post;
			$post_id = $post->ID;
		}
		$html = "\n" . '<!-- ' . $this->plugin_name . ' Hidden Comments -->' . "\n";
		$fb_comments = apply_filters('AWD_facebook_comments_array', '', $post_id);
		if (is_array($fb_comments)) {
			$html .= '<div class="AWD_fb_comments_hidden" style="display:none;">';
			foreach ($fb_comments as $comment) {
				$html .= '<div class="AWD_fb_comment_hidden">';
				$html .= '<span class="fb_comment_id">' . $comment->comment_ID . '</span> | ';
				$html .= '<span class="fb_comment_author"><strong>' . $comment->comment_author . '</strong></span>';
				$html .= '<div class="fb_comment_content">' . $comment->comment_content . '</div>';
				$html .= "</div><br />\n";
			}
			$html .= '</div>' . "\n";
		}
		$html .= '<!-- ' . $this->plugin_name . ' Hidden Comments End -->' . "\n\n";
		return $html;
	}
	
	/**
	 * Add filter comment type
	 * to allow the comment object to be passed into get_avatar function
	 * return array
	 */
	public function add_comment_type($types)
	{
		$types[] = 'comment fb_comment';
		return $types;
	}
	
	/**
	 * Add the
	 * @param string $comment_template
	 * @param int $post_id
	 */
	public function set_comments_content($comments, $post_id)
	{
		$this->AWD_facebook_comments->comments_url = get_permalink($post_id);
		$this->AWD_facebook_comments->wp_post_id = $post_id;

		$response = $this->AWD_facebook_comments->wp_get_comments();
		$comments_wait = array();
		if (is_array($this->AWD_facebook_comments->comments_array)) {
			foreach ($this->AWD_facebook_comments->comments_array as $comment) {
				$wp_from_fb_comments = $this->AWD_facebook_comments->wp_comments_data_model($comment);
				$comments_wait[] = $wp_from_fb_comments['wp_comment'];
				if (is_array($wp_from_fb_comments['response_comments'])){
					foreach ($wp_from_fb_comments['response_comments'] as $response_comment){
						$comments_wait[] = $response_comment;
					}
					usort($comments_wait, array(&$this, 'order_comment_by_date'));
				}
			}
		}
		if (!is_array($comments))
			$comments = array();
		$comments = array_merge($comments_wait, $comments);				
		usort($comments, array(&$this, 'order_comment_by_date'));
		return $comments;
	}

	/**
	 * callback to order comments by date
	 */
	public function order_comment_by_date($a, $b)
	{
		$t1 = strtotime($a->comment_date);
		$t2 = strtotime($b->comment_date);
		return $t1 - $t2;
	}
	
	/**
	 * Merge the count of WP comments with count of Facebook comments
	 * @param number $count
	 * @param int $post_id
	 * @return number
	 */
	public function set_comments_number($count, $post_id)
	{
		$this->AWD_facebook_comments->wp_post_id = $post_id;
		if ($this->AWD_facebook->options['seo_comments']['count_merge'] == 1) {
			$this->AWD_facebook_comments->comments_url = get_permalink($post_id);
			$action = isset($_GET['action']) ? $_GET['action'] : null;
			if ($this->AWD_facebook->options['seo_comments']['cache'] != "0" && $action == 'clear_fb_cache') {
				$this->AWD_facebook_comments->get_comments_from_cache();
				if ($this->AWD_facebook_comments->comments_status != 1) {
					$this->AWD_facebook_comments->get_comments_id_by_url();
				}
			} else {
				$this->AWD_facebook_comments->get_comments_id_by_url();
			}
			if ($this->AWD_facebook_comments->get_comments_count() > 0)
				$count = $count + $this->AWD_facebook_comments->get_comments_count();
		}
		return $count;
	}

}
?>