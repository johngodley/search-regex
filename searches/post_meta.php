<?php

class SearchPostMetaValue extends Search
{
	function find ($pattern, $limit, $offset, $orderby)
	{
		global $wpdb;

		$results = array ();

		$metas = $wpdb->get_results ($wpdb->prepare( "SELECT {$wpdb->postmeta}.meta_id AS meta_id, {$wpdb->postmeta}.meta_value AS meta_value, {$wpdb->postmeta}.post_id AS post_id, {$wpdb->posts}.post_title AS title FROM {$wpdb->postmeta} LEFT JOIN {$wpdb->posts} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID ORDER BY meta_value $orderby LIMIT %d,%d", $offset,$limit ) );

		if (count ($metas) > 0)
		{
			foreach ($metas AS $meta)
			{
				// Perform a regex
				if (($result = $this->matches ($pattern, $meta->meta_value, $meta->meta_id)) !== false)
				{
					foreach ($result AS $item)
					{
						$item->sub_id = $meta->post_id;
						$item->title  = $meta->title;
					}
					$results = array_merge ($results, $result);
				}
			}
		}

		return $results;
	}

	function get_options ($result)
	{
		$options[] = '<a href="'.get_permalink ($result->sub_id).'">'.__ ('view post', 'search-regex').'</a>';
		if ($result->replace)
			$options[] = '<a href="#" onclick="regex_replace (\'SearchPostMetaValue\','.$result->id.','.$result->offset.','.$result->length.',\''.str_replace ("'", "\'", $result->replace_string).'\'); return false">replace</a>';

		if (current_user_can ('edit_post', $result->sub_id))
			$options[] = '<a href="'.get_bloginfo ('wpurl').'/wp-admin/post.php?action=edit&amp;post='.$result->sub_id.'">'.__ ('edit','search-regex').'</a>';
		return $options;
	}

	function show ($result)
	{
		printf (__ ('Meta data for post #%d: %s', 'search-regex'), $result->sub_id, $result->title);
	}

	function name () { return __ ('Post meta value', 'search-regex'); }

	function get_content ($id)
	{
		global $wpdb;

		$post = $wpdb->get_row ($wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_id=%d", $id ) );
		return $post->meta_value;
	}

	function replace_content ($id, $content)
	{
		global $wpdb;
		$wpdb->query ($wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value=%s WHERE meta_id=%d", $content, $id ) );
	}
}

