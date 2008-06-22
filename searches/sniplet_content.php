<?php

class SearchSnipletContent extends Search
{
	function find ($pattern, $limit, $offset, $orderby)
	{
		global $wpdb;
			
		$results = array ();
		$terms = $wpdb->get_results ("SELECT {$wpdb->prefix}sniplets.id, {$wpdb->prefix}sniplets.contents, {$wpdb->prefix}sniplets.name FROM {$wpdb->prefix}sniplets LIMIT $offset,$limit");
		if (count ($terms) > 0)
		{
			foreach ($terms AS $term)
			{
				if (($matches = $this->matches ($pattern, $term->contents, $term->id)))
				{
					foreach ($matches AS $match)
					{
						$match->sub_id = $term->id;
						$match->title  = $term->name;
					}
					
					$results = array_merge ($results, $matches);
				}
			}
		}

		return $results;
	}
	
	function get_options ($result)
	{
		return array ();
	}
	
	function show ($result)
	{
		printf (__ ('Sniplet #%d: %s', 'search-regex'), $result->id, $result->title);
	}
	
	function name () { return __ ('Sniplet Content', 'search-regex'); }

	function get_content ($id)
	{
		global $wpdb;

		$row = $wpdb->get_row ("SELECT contents FROM {$wpdb->prefix}sniplets WHERE id='$id'");
		return $row->contents;
	}
	
	function replace_content ($id, $content)
	{
		global $wpdb;
		$content = wpdb::escape ($content);
		$wpdb->query ("UPDATE {$wpdb->prefix}sniplets SET contents='{$content}' WHERE id='$id'");
	}
}


?>