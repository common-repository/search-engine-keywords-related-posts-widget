<?php
/*
Plugin Name: Search Engine Keywords Related Posts Widget
Plugin URI: http://www.blogseye.com
Description: Watches for hits arriving from search engines. Displays a list of posts related to the search engine keywords.
Author: Keith P. Graham
Version: 1.2
Author URI: http://www.blogseye.com
*/
 
class search_engine_keywords_related_posts extends WP_Widget {
    /** constructor */
    function search_engine_keywords_related_posts() {
        parent::WP_Widget(false, $name = 'Search Engine Keywords Related Posts');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
		// defaults
		echo "\r\n<!-- Search Engine Keywords Related Posts -->\r\n";
		$title="Related Posts";
		$postcount=5;
		$type="*";
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $postcount = esc_attr($instance['postcount']);
        $type = esc_attr($instance['type']);
		if (empty($title)) $title="Related Posts";
		if (empty($postcount)) $postcount=5;
		if (!is_numeric($postcount)) $postcount=5;
		if ($postcount<3) $postcount=5;
		if (empty($type)) $type="*";
		// old code goes here

		// let's see if we are in a page referred by google or such
		$ref=urldecode($_SERVER['HTTP_REFERER']);
		$q='';
		$sep='';
		if ((strpos($ref,'google')>0||strpos($ref,'bing')>0||strpos($ref,'ask.com')>0||strpos($ref,'search.aol.')>0 )) {
			if (strpos($ref,'&q=')>0) $sep='&q=';
			else if (strpos($ref,'?q=')>0) $sep='?q=';
		} else if (strpos($ref,'yahoo')>0&&strpos($ref,'&p=')>0) {
			if (strpos($ref,'&p=')>0) $sep='&p=';
			else if (strpos($ref,'?p=')>0) $sep='?p=';
		} else if (strpos($ref,'baidu')>0) {
			if (strpos($ref,'?wd=')>0) $sep='?wd=';
		} else {
			return; // not a search engine - get out of here.
		}
		if ($sep==null) return; // no search parameter found	
		$q=substr($ref,strpos($ref,$sep)+strlen($sep));
		if (empty($q)) return; // not a query let's leave
		$n=strpos($q,'&');
		if ($n===false) $n=strlen($q);
		if ($n>0) $q=substr($q,0,$n);
		$q=trim($q);
		if (empty($q)) return; 
		//echo "\r\n<!--\r\n";
		//echo $q;
		//echo "\r\n-->\r\n";
		// now lest's search the database for the key words
		$rposts=kpg_get_related_se_posts($q,$type);
		if ($rposts==null||!is_array($rposts)||count($rposts)==0) return;
		// end of old code
		// if we have reached here, we can display the widget
		echo $before_widget;
		if ( $title ) {
            echo $before_title . $title . $after_title; 
		}
        // display the data -  Hello, World!
		echo '<ul>'; 
		// display the recent searches
		for ($j=0;$j<count($rposts)&&$j<$postcount;$j++) {
			echo $rposts[$j];
		}
		echo '</ul>';

        echo $after_widget; 
		echo "\r\n<!-- end of Search Engine Keywords Related Posts -->\r\n";
		return;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
	    $title = esc_attr($new_instance['title']);
        $postcount = esc_attr($new_instance['postcount']);
        $type = esc_attr($new_instance['type']);
		if (empty($title)) $title="Related Posts";
		if (empty($postcount)) $postcount="5";
		if (empty($type)) $type="*";
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
		echo "\r\n<!-- Search Engine Keywords Related Posts -->\r\n";
		// outputs the content of the widget
		
		$title="Related Posts";
		$postcount=5;
		$type="*";
        $title = esc_attr($instance['title']);
        $postcount = esc_attr($instance['postcount']);
        $type = esc_attr($instance['type']);
		if (empty($title)) $title="Related Posts";
		if (empty($postcount)) $postcount="5";
		if (empty($type)) $type="*";
        ?>
         <p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
			<?php echo 'Widget title:'; ?> 
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
			<label for="<?php echo $this->get_field_id('type');?> " style="line-height:25px;display:block;">
			<?php echo 'Search Pages or posts:'; ?> 
				<select style="width: 200px;" type="text" id="<?php echo $this->get_field_id('type');?>" name="<?php echo $this->get_field_name('type');?>" >
					<option value="*" <?php if (empty($type)||$type=='*') echo "selected";   ?> >Posts and Pages</option>
					<option value="post" <?php if (!empty($type)&&$type=='post') echo "selected";  ?>>Posts only</option>
					<option value="page" <?php if (!empty($type)&&$type=='page') echo "selected";  ?>>Pages only</option>
				</select>
			</label>
			<label for="<?php echo $this->get_field_id('postcount');?>" style="line-height:25px;display:block;">
			<?php echo 'Number of posts:'; ?> 
				<input style="width: 200px;" type="text" id="<?php echo $this->get_field_id('postcount');?>" name="<?php echo $this->get_field_name('postcount');?>" value="<?php echo $postcount; ?>" />
			</label><br/><small>note: the widget will not display on a page unless there has actually been a user arriving by a search engine query)</small>

			</p>
        <?php 
		echo "\r\n<!-- end of Search Engine Keywords Related Posts -->\r\n";
		return;
    }

} // class search_engine_keywords_related_posts

// register search_engine_keywords_related_posts widget
add_action('widgets_init', create_function('', 'return register_widget("search_engine_keywords_related_posts");'));

/*********************************************************************
* this goes to database to get the related posts based on the 
* search items
*********************************************************************/
function kpg_get_related_se_posts($q,$type) {
	// $q has the google query
	// $type has posts or pages - I should update to include custom post types
	$ret=array();
	global $wpdb;
	global $wp_query;
	$q=str_replace('&quote',' ',$q); 
	$q=str_replace('%20',' ',$q); 
	$q=str_replace('_',' ',$q); 
	$q=str_replace('.',' ',$q); 
	$q=str_replace('-',' ',$q); 
	$q=str_replace('+',' ',$q); 
	$q=str_replace('"',' ',$q); 
	$q=str_replace("\'",' ',$q); 
	$q=str_replace('`',' ',$q); 
	$q=str_replace('  ',' ',$q);
	$q=str_replace('  ',' ',$q);
	$q=str_replace('"','',$q);
	$q=str_replace('`','',$q);
	$q=str_replace("'",'',$q);
	$q=trim($q);
	// put it into an array
	$qs=explode(' ',$q);
	// get rid of common words - don'd need to search for these:
	$common="  am an and at be but by did does had has her him his its may she than that the them then there these they ";
	$good=0;
	for ($j=0;$j<count($qs);$j++) {
	    if (strlen($qs[$j])<3) {
			$qs[$j]='';
		} else if (strpos($common,' '.$qs[$j].' ')>0) {
			$qs[$j]='';
		} else {
			$good++;
		}
	}
	if ($good==0) return $ret;
	$ptab=$wpdb->posts;
	$sql= "SELECT ID from $ptab where "; //  return most recent posts if nothing is found
	$orderby=" ORDER BY ";
	for ($j=0;$j<count($qs);$j++) {
		if (strlen($qs[$j])>2) {
			$sss=strtolower(mysql_real_escape_string($qs[$j]));
			$ssl="IF(LENGTH(post_content) = LENGTH(REPLACE(LCASE(post_content), '$sss', '')),0,1) + ";
			$ssl.="IF(LENGTH(post_title) = LENGTH(REPLACE(LCASE(post_title), '$sss', '')),0,2) + ";
			$orderby=$orderby.$ssl;
		}	
	}
	$orderby.="0  DESC, ID DESC "; // order by most popular and on a tie the newest first
	
	// finish up with the paperwork:
	$thisid=get_the_ID();
	if ($type=='*') {
		$sql=$sql." (post_type='post' OR post_type='page') ";
	} else {
		$sql=$sql." post_type='$type' ";
	}
	$sql=$sql." AND post_status = 'publish' ";
	$sql=$sql." AND ID <> $thisid ";
	$sql=$sql.$orderby;
	// time to execute the select to get a list of 
	$pageposts = $wpdb->get_col($sql,0);
	$ret=array();
	if (!empty($pageposts)) {
		foreach ($pageposts as $ID) { 
			$post_data=get_post(intval($ID)); 
			$post_title=$post_data->post_title;
			$post_link=get_permalink($ID);
			$ret[count($ret)]="<li><a href=\"$post_link\">$post_title</a></li>";
		}
	} 
	return $ret;
}

function kpg_get_related_se_control()  {
?>

<div class="wrap">
<h2>Search Engine Keywords Related Posts Widget</h2>
<h4>The Search Engine Keywords Related Posts Widget is installed and working correctly.</h4><div style="position:relative;float:right;width:35%;background-color:ivory;border:#333333 medium groove;padding:4px;margin-left:4px;">
    <p>This plugin is free and I expect nothing in return. If you would like to support my programming, you can buy my book of short stories.</p>
    <p>Some plugin authors ask for a donation. I ask you to spend a very small amount for something that you will enjoy. eBook versions for the Kindle and other book readers start at 99&cent;. The book is much better than you might think, and it has some very good science fiction writers saying some very nice things. <br/>
      <a target="_blank" href="http://www.blogseye.com/buy-the-book/">Error Message Eyes: A Programmer's Guide to the Digital Soul</a></p>
    <p>A link on your blog to one of my personal sites would also be appreciated.</p>
    <p><a target="_blank" href="http://www.WestNyackHoney.com">West Nyack Honey</a> (I keep bees and sell the honey)<br />
      <a target="_blank" href="http://www.cthreepo.com/blog">Wandering Blog</a> (My personal Blog) <br />
      <a target="_blank" href="http://www.cthreepo.com">Resources for Science Fiction</a> (Writing Science Fiction) <br />
      <a target="_blank" href="http://www.jt30.com">The JT30 Page</a> (Amplified Blues Harmonica) <br />
      <a target="_blank" href="http://www.harpamps.com">Harp Amps</a> (Vacuum Tube Amplifiers for Blues) <br />
      <a target="_blank" href="http://www.blogseye.com">Blog&apos;s Eye</a> (PHP coding) <br />
      <a target="_blank" href="http://www.cthreepo.com/bees">Bee Progress Beekeeping Blog</a> (My adventures as a new beekeeper) </p>
  </div>

<p>The Search Engine Keywords Related Posts Widget collects the query string from Google, Bing and Yahoo and extracts the keywords that the user used in his search. The widget then lists posts related to the search engine keywords. Since the search engines usually only show one result from your site, but there may be many pages that are related to the users search, this encourages surfers to browse through the posts related to their search.</p>
<p>The widget is only visible when a user arrives through a Google, Bing, Yahoo, AOL, Ask, and Baidu searches. Bookmarked users and users referred from other sites do not see the widget. Users who are logged into Google do not pass their query string in the referrer page so it will not work for them.

</p>

</div>

<?php
}



function kpg_get_related_se_init() {
   add_options_page('Search Engine Keywords Related Posts', 'Search Engine Keywords', 'manage_options','se_keywords','kpg_get_related_se_control');
}
	add_action('admin_menu', 'kpg_get_related_se_init');	


?>