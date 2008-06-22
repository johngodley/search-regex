<?php

class SearchTagName extends Search
{
	function find ($pattern, $limit, $offset, $orderby)
	{
		global $wpdb;
			
		$results = array ();
		$terms = $wpdb->get_results ("SELECT {$wpdb->terms}.term_id, {$wpdb->terms}.name FROM {$wpdb->terms} LIMIT $offset,$limit");
		if (count ($terms) > 0)
		{
			foreach ($terms AS $term)
			{
				if (($matches = $this->matches ($pattern, $term->name, $term->term_id)))
				{
					foreach ($matches AS $match)
					{
						$match->sub_id = $term->term_id;
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
		printf (__ ('Tag #%d: %s', 'search-regex'), $result->id, $result->title);
	}
	
	function name () { return __ ('Tag Name', 'search-regex'); }

	function get_content ($id)
	{
		global $wpdb;

		$row = $wpdb->get_row ("SELECT name FROM {$wpdb->terms} WHERE term_id='$id'");
		return $row->name;
	}
	
	function replace_content ($id, $content)
	{
		global $wpdb;
		$content = wpdb::escape ($content);
		$wpdb->query ("UPDATE {$wpdb->terms} SET name='{$content}' WHERE term_id='$id'");
	}
}


?>