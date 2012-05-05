<?php

class SearchCommentAuthorEmail extends Search
{
	function find ($pattern, $limit, $offset, $orderby)
	{
		global $wpdb;

		if ($orderby == 'id')
			$order = 'comment_ID ASC';
		else
			$order = 'comment_date DESC';

		$results = array ();
		$comments = $wpdb->get_results ($wpdb->prepare( "SELECT {$wpdb->comments}.comment_ID AS comment_ID, {$wpdb->comments}.comment_post_ID AS comment_post_ID, {$wpdb->comments}.comment_author_email AS comment_author_email, {$wpdb->posts}.post_title AS post_title FROM {$wpdb->comments},{$wpdb->posts} WHERE {$wpdb->comments}.comment_approved='1' AND {$wpdb->posts}.ID={$wpdb->comments}.comment_post_ID ORDER BY {$wpdb->comments}.comment_ID $orderby LIMIT %d,%d", $offset,$limit ) );
		if (count ($comments) > 0)
		{
			foreach ($comments AS $comment)
			{
				if (($matches = $this->matches ($pattern, $comment->comment_author_email, $comment->comment_ID)))
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
			$options[] = '<a href="#" onclick="regex_replace (\'SearchCommentAuthorEmail\','.$result->id.','.$result->offset.','.$result->length.',\''.str_replace ("'", "\'", $result->replace_string).'\'); return false">replace</a>';

		if (current_user_can ('edit_post', $result->id))
			$options[] = '<a href="'.get_bloginfo ('wpurl').'/wp-admin/comment.php?action=editcomment&amp;c='.$result->id.'">'.__ ('edit','search-regex').'</a>';
		return $options;
	}

	function show ($result)
	{
		printf (__ ('Comment #%d: %s', 'search-regex'), $result->id, $result->title);
	}

	function name () { return __ ('Comment author email', 'search-regex'); }

	function get_content ($id)
	{
		global $wpdb;

		$row = $wpdb->get_row ($wpdb->prepare( "SELECT comment_author_email FROM {$wpdb->comments} WHERE comment_ID=%d", $id ) );
		return $row->comment_author_email;
	}

	function replace_content ($id, $content)
	{
		global $wpdb;
		$wpdb->query ($wpdb->prepare( "UPDATE {$wpdb->comments} SET comment_author_email=%s WHERE comment_ID=%d", $content, $id ) );
	}
}
