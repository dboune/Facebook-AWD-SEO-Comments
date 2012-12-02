<?php
/*
*
* Table comments class | AWD FCBK SEO comments
* (C) 2012 AH WEB DEV
* Hermann.alexandre@ahwebdev.fr
*
*/
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class AWD_facebook_table_comments extends WP_List_Table{
    
    function __construct($AWD_facebook_seo_comments)
    {
        $this->AWD_facebook_seo_comments = $AWD_facebook_seo_comments;
    	$this->AWD_facebook = $AWD_facebook_seo_comments->AWD_facebook;
    	$this->AWD_facebook_comments = $AWD_facebook_seo_comments->AWD_facebook_comments;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'comment',     
            'plural'    => 'comments',  
            'ajax'      => true
        ));
    }
    function ajax_user_can()
    {
    	return true;
    }
    function no_items()
    {
		_e('No comments found.', $this->AWD_facebook_seo_comments->plugin_text_domain);
	}
	
	function search_box($text, $input_id)
	{
		$input_id = $input_id . '-search-input';
		$internal_linking_nonce = wp_create_nonce("internal-linking");
		?>
		
		<?php
		if ( ! empty( $_REQUEST['orderby'] ) )
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		if ( ! empty( $_REQUEST['order'] ) )
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		?>
		<p>
			<input type="text" id="<?php echo $input_id ?>" name="s" value="<?php if($this->AWD_facebook_comments->comments_url ==''){ _admin_search_query(); }else{ echo $this->AWD_facebook_comments->comments_url;} ?>" size="100"/>
			<a href="#" class="uiButton uiButtonSubmit" id="search_submit"><?php echo $text; ?></a>
			<img src="/wp-content/plugins/facebook-awd/assets/css/images/loading.gif" alt="loading..." class="search_comment_loading"/>
		</p>
		<?php
		wp_nonce_field( "fetch-list-" . get_class( $this ), '_ajax_fetch_list_nonce' );
	}
    function column_default($item, $column_name)
    {
        switch($column_name){
            case 'like':
            case 'author':
            case 'comment':
            case 'post':
                return $item[$column_name];
            default:
                return print_r($item,true);
        }
    }   
    
    function column_comment($item)
    {
        //Build row actions
        $actions = array(
            //'edit' => sprintf('<a href="?page=%s&action=%s&comment=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
        );
        //if user connect he can delete his own comment
        if($this->AWD_facebook->uid == $item['author_fbuid'])
        	$actions['delete'] = sprintf('<a href="?page=%s&action=%s&comment[]=%s&s=%s">%s</a>',
        		$_REQUEST['page'],
        		'delete',
        		$item['ID'],
        		urlencode($_REQUEST['s']),
        		__('Delete',$this->AWD_facebook_seo_comments->plugin_text_domain)
        	);
        //Return the title contents
        return sprintf('%1$s %2$s',
            $item['comment'],
            "<p>".$this->row_actions($actions)."</p>"
        );
    }
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'], 
         	$item['ID']               
        );
    }
    function get_columns()
    {
        $columns = array(
            'cb'        => '<input type="checkbox" />',
        	'author'  => __('Author',$this->AWD_facebook_seo_comments->plugin_text_domain),
            'comment'     => __('Comment',$this->AWD_facebook_seo_comments->plugin_text_domain),
            'like'    => '<img src="'.$this->AWD_facebook_seo_comments->plugin_url_images.'facebook_button_heart.png" alt="Like" />',
            'post'    => __('In response to',$this->AWD_facebook_seo_comments->plugin_text_domain)
        );
        return $columns;
    }
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'comment'     => array('ID',false),
            'like'    => array('like',false),
            'author'  => array('author',false),
            'post'  => array('post',false)
        );
        return $sortable_columns;
    }
    function get_bulk_actions()
    {
        $actions = array(
            'delete'    => __('Delete',$this->AWD_facebook_seo_comments->plugin_text_domain)
        );
        return $actions;
    }
    function process_bulk_action()
    {
        //Detect when a bulk action is being triggered...
        if('delete' === $this->current_action() ) {
			if(isset($_REQUEST['comment']) && is_array($_REQUEST['comment'])){
				foreach($_REQUEST['comment'] as $comment_id){
					if(preg_match('@_@',$comment_id)){
						$response = $this->AWD_facebook_comments->delete_comment($comment_id);
						if($response === true)
							echo '<div id="message" class="ui-state-highlight fadeOnload"><p>'.__('Comment was deleted',$this->AWD_facebook_seo_comments->plugin_text_domain).'</p></div>';
						else
							echo '<div id="message" class="ui-state-highlight fadeOnload"><p>'.$response.'</p></div>';
					}
				
				}
			}
        	
        }
        
    }
    function prepare_items()
    {  
    	$per_page = $_REQUEST['nb_page'] == '' ? 10 : $_REQUEST['nb_page']; 
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
		$data = array();
		$this->comments_data = array();
		$this->AWD_facebook_comments->get_comments_by_url();
		$comments_set = $this->AWD_facebook_comments->comments_array;
		if($comments_set){      
			foreach($comments_set as $comment){
				$this->comments_data[] = $this->table_data_model($comment);
			}
        	//inverse sort from facebook
			$data = array_reverse($this->comments_data,false);   
		}
        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : ''; 
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc';
            $result = strcmp($a[$orderby], $b[$orderby]);
            return ($order==='asc') ? $result : -$result;
        }
        if(!empty($_REQUEST['orderby']))
       		usort($data, 'usort_reorder');
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );
    }
   
   
	function table_data_model($comment)
	{
			$good_comment_format = array();
			//id
			$good_comment_format['ID'] = $comment['id'];
			//author
			$fb_avatar_url = 'http://graph.facebook.com/'.$comment['from']['id'].'/picture';
			$fb_avatar = "<img src='".$fb_avatar_url."' class='avatar AWD_fbavatar' alt='' height='32' width='32' />";
			$good_comment_format['author'] = $fb_avatar.$comment['from']['name'];
			$good_comment_format['author_fbuid'] = $comment['from']['id'];
			//comment 
			$good_comment_format['comment'] = '<div class="submitted-on">'.__("Submitted on",$this->AWD_facebook_seo_comments->plugin_text_domain).' <a href="'.$comments_url.'">'.date("Y/m/d - H:i:s",strtotime($comment['created_time'])).'</a></div><br />';
			$good_comment_format['comment'] .= $comment['message'];
			//like
			$good_comment_format['like'] = $comment['likes'] == 0 ? "0" : $comment['likes'];
			//in respone to (permalink)
			$post_id = url_to_postid($_REQUEST['s']);
			//if find home url, its that the same domain
			if($post_id != false)
				$good_comment_format['post'] = '<a href="'.$this->AWD_facebook_seo_comments->search_comments_url.'" target="_blank">'.get_the_title($post_id).'</a>';
			else
				$good_comment_format['post'] = '<a href="'.$this->AWD_facebook_seo_comments->search_comments_url.'" target="_blank">'.$this->AWD_facebook_seo_comments->search_comments_url.'</a>';
			
			//if subcomment ad it to other
			if($comment['comments']['data'])
				foreach($comment['comments']['data'] as $subcomment)
					$this->comments_data[] = $this->table_data_model($subcomment);
			//set attr
			return $good_comment_format;
	}
	
	
	function ajax_response() {
		$this->prepare_items();
		extract( $this->_args );
		extract( $this->_pagination_args );

		ob_start();
		$this->display();
		$table = ob_get_clean();
		$response = array( 'table' => $table);
		
		if ( isset( $total_items ) )
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		die( json_encode( $response ) );
	}

	
}
?>