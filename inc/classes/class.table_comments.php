<?php
/*
*
* Table comments class | AWD FCBK SEO comments
* (C) 2011 AH WEB DEV
* Hermann.alexandre@ahwebdev.fr
*
*/
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class AWD_facebook_table_comments extends WP_List_Table {
    
    function __construct(){
        global $status, $page, $AWD_facebook, $AWD_facebook_seo_comments;
    	$this->AWD_facebook = $AWD_facebook;
    	$this->AWD_facebook_comments_plus = $AWD_facebook_seo_comments;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'comment',     //singular name of the listed records
            'plural'    => 'comments',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    /**
    * No itmes
    */
    function no_items() {
		_e('No comments found.', $this->AWD_facebook_comments_plus->plugin_text_domain);
	}
	/**
	* Search
	*/
	function search_box( $text, $input_id ) {
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
			<input type="text" id="<?php echo $input_id ?>" name="s" value="<?php if($this->comments_url ==''){ _admin_search_query();}else{echo $this->comments_url;} ?>" size="100"/>
			<a href="#" class="uiButton uiButtonSubmit" id="search_submit"><?php echo $text; ?></a>
		</p>
		<?php
	}
    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item, $column_name){
        switch($column_name){
            case 'like':
            case 'author':
            case 'comment':
            case 'post':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }   
    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_comment($item){
        
        //Build row actions
        $actions = array(
            //'edit'      => sprintf('<a href="?page=%s&action=%s&comment=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
        );
        //if user connect he can delete his own comment
        if($this->AWD_facebook->uid == $item['author_fbuid'])
        	$actions['delete'] = sprintf('<a href="?page=%s&action=%s&comment=%s&s=%s">%s</a>',$_REQUEST['page'],'delete',$item['ID'],urlencode($_REQUEST['s'],__('Delete',$this->AWD_facebook_comments_plus->plugin_text_domain)));

        
        //Return the title contents
        return sprintf('%1$s %2$s',
            /*$1%s*/ $item['comment'],
            /*$2%s*/ "<p>".$this->row_actions($actions)."</p>"
        );
    }
    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
        );
    }
    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
        	'author'  => __('Author',$this->AWD_facebook_comments_plus->plugin_text_domain),
            'comment'     => __('Comment',$this->AWD_facebook_comments_plus->plugin_text_domain),
            'like'    => '<img src="'.$this->AWD_facebook_comments_plus->plugin_url_images.'facebook_button_heart.png" alt="Like" />',//render image like instead of text
            'post'    => __('In response to',$this->AWD_facebook_comments_plus->plugin_text_domain)
        );
        return $columns;
    }
    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns() {
        $sortable_columns = array(
            'comment'     => array('ID',false),     //true means its already sorted
            'like'    => array('like',false),
            'author'  => array('author',false),
            'post'  => array('post',false)
        );
        return $sortable_columns;
    }
    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array(
            'delete'    => __('Delete',$this->AWD_facebook_comments_plus->plugin_text_domain)
        );
        return $actions;
    }
    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
        if('delete' === $this->current_action() ) {
        	if(ereg('_',$_REQUEST['comment'])){
        		$comment_id = $_REQUEST['comment'];
				if($this->AWD_facebook_comments_plus->AWD_facebook_comments->delete_comment($comment_id))
					echo '<div id="message" class="updated fadeOnload"><p>'.__('Comment was deleted',$this->AWD_facebook_comments_plus->plugin_text_domain).'</p></div>';
				//else
					//echo '<div id="message" class="error"><p>'.__('Sorry there is an error, comment can not be deleted.',$this->AWD_facebook_comments_plus->plugin_text_domain).'</p></div>';
        	}
        }
        
    }
    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {  
    	$per_page = $_REQUEST['nb_page'] == '' ? 10 : $_REQUEST['nb_page']; 
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
		$data = array();
		$this->comments_data = array();
		$this->AWD_facebook_comments_plus->AWD_facebook_comments->get_comments_by_url();
		$comments_set = $this->AWD_facebook_comments_plus->AWD_facebook_comments->comments_array;
		if($comments_set){      
			foreach($comments_set as $comment){
				$this->comments_data[] = $this->table_data_model($comment);
			}
        	//inverse sort from facebook
			$data = array_reverse($this->comments_data,false);//example_data;        
		}
        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : ''; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        if(!empty($_REQUEST['orderby']))
       		usort($data, 'usort_reorder');
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }
    /**
	* Model fb comment to wp comment.
	*/
	function table_data_model($comment){
			$good_comment_format = array();
			//id
			$good_comment_format['ID'] = $comment['id'];
			//author
			$fb_avatar_url = 'http://graph.facebook.com/'.$comment['from']['id'].'/picture';
			$fb_avatar = "<img src='".$fb_avatar_url."' class='avatar AWD_fbavatar' alt='' height='32' width='32' />";
			$good_comment_format['author'] = $fb_avatar.$comment['from']['name'];
			$good_comment_format['author_fbuid'] = $comment['from']['id'];
			//comment 
			$good_comment_format['comment'] = '<div class="submitted-on">'.__("Submitted on",$this->AWD_facebook_comments_plus->plugin_text_domain).' <a href="'.$comments_url.'">'.date("Y/m/d - H:i:s",strtotime($comment['created_time'])).'</a></div><br />';
			$good_comment_format['comment'] .= $comment['message'];
			//like
			$good_comment_format['like'] = $comment['likes'] == 0 ? "0" : $comment['likes'];
			//in respone to (permalink)
			$post_id = url_to_postid($_REQUEST['s']);
			//if find home url, its that the same domain
			if($post_id)
				$good_comment_format['post'] = '<a href="'.$this->AWD_facebook_comments_plus->search_comments_url.'" target="_blank">'.get_the_title($post_id).'</a>';
			else
				$good_comment_format['post'] = '<a href="'.$this->AWD_facebook_comments_plus->search_comments_url.'" target="_blank">'.$this->search_comments_url.'</a>';
			
			//if subcomment ad it to other
			if($comment['comments']['data'])
				foreach($comment['comments']['data'] as $subcomment)
					$this->comments_data[] = $this->table_data_model($subcomment);
			//set attr
			return $good_comment_format;
	}
}
?>