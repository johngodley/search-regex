<?php

include ('../../../wp-config.php');
include (dirname (__FILE__).'/models/search.php');
include (dirname (__FILE__).'/models/result.php');

class Search_AJAX extends Redirection_Plugin
{
	function Search_AJAX ($id, $command)
	{
		if (!current_user_can ('administrator') && !current_user_can ('search_regex_write'))
			die ('<p style="color: red">You are not allowed access to this resource</p>');
		
		$this->register_plugin ('search-regex', __FILE__);
		if (method_exists ($this, $command))
			$this->$command ($id);
		else
			die ('<p style="color: red">That function is not defined</p>');
	}
	
	function regex_replace ($id)
	{
		if (Search::valid_search (stripslashes( $_POST['klass'] )))
		{
			$searcher = new $_POST['klass'];
			$searcher->replace_inline (intval ($_POST['item']), intval ($_POST['offset']), intval ($_POST['length']), stripslashes( $_POST['replace']) );
		}
	}

}

$id  = $_GET['id'];
$cmd = $_GET['cmd'];

$obj = new Search_AJAX ($id, $cmd);
