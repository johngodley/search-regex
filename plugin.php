<?php
/**
 * This program and all files not otherwise specifically mentioned are copyright of the
 * author and may be used in any commercial or non-commercial environment.  It may not
 * be sold or derived from, and the author reserves the right to change the license
 * at any time (including Christmas)
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @author     John Godley
 * @copyright  Copyright &copy; 2007 John Godley, All Rights Reserved
 * @version    0.1.1
 */

class SearchRegex_Plugin
{
	var $plugin_name;
	var $plugin_base;
	
	function register_plugin ($name, $base)
	{
		$this->plugin_base = rtrim (dirname ($base), '/');
		$this->plugin_name = $name;
		
		load_plugin_textdomain ("$name/locale/$name");
	}
	
	function add_action ($action, $function = '', $priority = 10, $accepted_args = 1)
	{
		add_action ($action, array (&$this, $function == '' ? $action : $function), $priority, $accepted_args);
	}

	function add_filter ($filter, $function = '', $priority = 10, $accepted_args = 1)
	{
		add_filter ($filter, array (&$this, $function == '' ? $filter : $function), $priority, $accepted_args);
	}

	function render_admin ($ug_name, $ug_vars = array ())
	{
		global $plugin_base;
		foreach ($ug_vars AS $key => $val)
			$$key = $val;

		if (file_exists ("{$this->plugin_base}/view/admin/$ug_name.php"))
			include ("{$this->plugin_base}/view/admin/$ug_name.php");
		else
			echo "<p>Rendering of admin template {$this->plugin_base}/view/admin/$ug_name.php failed</p>";
	}

	function render ($ug_name, $ug_vars = array ())
	{
		foreach ($ug_vars AS $key => $val)
			$$key = $val;

		if (file_exists (TEMPLATEPATH."/view/{$this->plugin_name}/$ug_name.php"))
			include (TEMPLATEPATH."/view/{$this->plugin_name}/$ug_name.php");
		else if (file_exists ("{$this->plugin_base}/view/default/$ug_name.php"))
			include ("{$this->plugin_base}/view/default/$ug_name.php");
		else
			echo "<p>Rendering of template $ug_name.php failed</p>";
	}
	
	function capture ($ug_name, $ug_vars = array ())
	{
		ob_start ();
		$this->render ($ug_name, $ug_vars);
		$output = ob_get_contents ();
		ob_end_clean ();
		return $output;
	}
	
	function capture_admin ($ug_name, $ug_vars = array ())
	{
		ob_start ();
		$this->render_admin ($ug_name, $ug_vars);
		$output = ob_get_contents ();
		ob_end_clean ();
		return $output;
	}
	
	function render_error ($message)
	{
	?>
<div class="fade error" id="message">
 <p><?php echo $message ?></p>
</div>
<?php
	}
	
	function render_message ($message)
	{
		?>
<div class="fade updated" id="message">
 <p><?php echo $message ?></p>
</div>
	<?php	
	}
	
	function dir ()
	{
		return $this->plugin_base;
	}
	
	function url ()
	{
		$url = substr ($this->plugin_base, strlen (realpath (ABSPATH)));
		return get_bloginfo ('wpurl').$url;
	}
}

?>