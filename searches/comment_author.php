<?php

class SearchCommentAuthor extends Search
{
	function find ($pattern, $limit, $offset, $orderby)
	{
		global $wpdb;

		if ($orderby == 'id')
			$order = 'comment_ID ASC';
		else
			$order = 'comment_date DESC';

		$results = array ();
		$sql = "SELECT {$wpdb->comments}.comment_ID AS comment_ID, {$wpdb->comments}.comment_post_ID AS comment_post_ID, {$wpdb->comments}.comment_author AS comment_author, {$wpdb->posts}.post_title AS post_title FROM {$wpdb->comments},{$wpdb->posts} WHERE {$wpdb->comments}.comment_approved='1' AND {$wpdb->posts}.ID={$wpdb->comments}.comment_post_ID ORDER BY {$wpdb->comments}.comment_ID $orderby";

		if ( $limit > 0 ) {
			$prepared_sql = $wpdb->prepare( $sql . " LIMIT %d, %d ", $offset, $limit );	
		} else {
			$prepared_sql = $wpdb->prepare( $sql );
		}

		$comments = $wpdb->get_results ( $prepared_sql );

		if (count ($comments) > 0)
		{
			foreach ($comments AS $comment)
			{
				if (($matches = $this->matches ($pattern, $comment->comment_author, $comment->comment_ID)))
				{
					foreach ($matches AS $match)
					{
						$match->sub_id = $comment->comment_post_ID;
						$match->title  = $comment->post_title;
					}

					$results = array_merge ($results, $matches);
				}
			}
		}

		return $results;
	}

	function get_options ($result)
	{
		$options[] = '<a href="'.get_permalink ($result->sub_id).'">'.__ ('view post', 'search-regex').'</a>';
		if ($result->replace)
			$options[] = '<a href="#" onclick="regex_replace (\'SearchCommentAuthor\','.$result->id.','.$result->offset.','.$result->length.',\''.str_replace ("'", "\'", $result->replace_string).'\'); return false">replace</a>';

		if (current_user_can ('edit_post', $result->id))
			$options[] = '<a href="'.get_bloginfo ('wpurl').'/wp-admin/comment.php?action=editcomment&amp;c='.$result->id.'">'.__ ('edit','search-regex').'</a>';
		return $options;
	}

	function show ($result)
	{
		printf (__ ('Comment #%d: %s', 'search-regex'), $result->id, $result->title);
	}

	function name () { return __ ('Comment author', 'search-regex'); }

	function get_content ($id)
	{
		global $wpdb;

		$row = $wpdb->get_row ($wpdb->prepare( "SELECT comment_author FROM {$wpdb->comments} WHERE comment_ID=%d", $id ) );
		return $row->comment_content;
	}

	function replace_content ($id, $content)
	{
		global $wpdb;
		$wpdb->query ($wpdb->prepare( "UPDATE {$wpdb->comments} SET comment_author=%s WHERE comment_ID=%d", $content, $id ) );
	}
}

