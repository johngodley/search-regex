<?php

class SearchPostContent extends Search
{
	function find ($pattern, $limit, $offset, $orderby)
	{
		global $wpdb;

		$results = array ();
		$sql = "SELECT ID, post_content, post_title FROM {$wpdb->posts} WHERE post_status != 'inherit' AND post_type IN ('post','page') ORDER BY ID $orderby";

		if ( $limit > 0 ) {
			$prepared_sql = $wpdb->prepare( $sql . " LIMIT %d, %d ", $offset, $limit );	
		} else {
			$prepared_sql = $wpdb->prepare( $sql );
		}

		$posts = $wpdb->get_results ( $prepared_sql );

		if (count ($posts) > 0)
		{
			foreach ($posts AS $post)
			{
				if (($matches = $this->matches ($pattern, $post->post_content, $post->ID)))
				{
					foreach ($matches AS $match)
						$match->title = $post->post_title;

					$results = array_merge ($results, $matches);
				}
			}
		}

		return $results;
	}

	function get_options ($result)
	{
		$options[] = '<a href="'.get_permalink ($result->id).'">'.__ ('view', 'search-regex').'</a>';
		if ($result->replace)
			$options[] = '<a href="#" onclick="regex_replace (\'SearchPostContent\','.$result->id.','.$result->offset.','.$result->length.',\''.str_replace ("'", "\'", $result->replace_string).'\'); return false">replace</a>';

		if (current_user_can ('edit_post', $result->id))
			$options[] = '<a href="'.get_bloginfo ('wpurl').'/wp-admin/post.php?action=edit&amp;post='.$result->id.'">'.__ ('edit','search-regex').'</a>';
		return $options;
	}

	function show ($result)
	{
		printf (__ ('Post #%d: %s', 'search-regex'), $result->id, $result->title);
	}

	function name () { return __ ('Post content', 'search-regex'); }

	function get_content ($id)
	{
		global $wpdb;

		$post = $wpdb->get_row ( $wpdb->prepare( "SELECT post_content FROM {$wpdb->prefix}posts WHERE id=%d", $id ) );
		return $post->post_content;
	}

	function replace_content ($id, $content)
	{
		global $wpdb;
		$wpdb->query ($wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content=%s WHERE ID=%d", $content, $id ) );
	}
}
