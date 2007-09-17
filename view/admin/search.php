<div class="wrap">
	<h2><?php _e ('Search Regex', 'search-regex') ?></h2>
	<p><?php _e ("Replacements will only be saved to the database if you click '<strong>Replace &amp; Save</strong>', otherwise you will get a preview of the results.", 'search-regex') ?></p>
	<p><?php _e ("NOTE: <strong>No liability</strong> is accepted for any damage caused.  You are strongly advised to backup your database before making any changes.", 'search-regex') ?></p>

	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
	<table class="searchargs">
	  <tr>
	    <th width="150"><?php _e ("Source", 'search-regex') ?></th>
	    <td>
				<select name="source">
					<?php foreach ($searches AS $searcher) : ?>
					<option value="<?php echo get_class ($searcher) ?>" <?php if (strcasecmp ($_POST['source'], get_class ($searcher)) == 0 || ($_POST['source'] == '' && strcasecmp (get_class ($searcher), 'SearchPostContent') == 0)) echo ' selected="selected"' ?>><?php echo $searcher->name () ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th width="150" valign="top"><?php _e ("Search pattern", 'search-regex') ?></th>
			<td>
			  <input class="term" type="text" name="search_pattern" value="<?php echo htmlentities ($search) ?>"/><br/>
			</td>
		</tr>
		<tr>
		  <th width="150" valign="top"><?php _e ('Replace pattern', 'search-regex') ?></th>
			<td>
			  <input class="term" type="text" name="replace_pattern" value="<?php echo htmlentities ($replace) ?>"/><br/>
			</td>
		</tr>
		<tr>
			<th><label for="regex">Regex</label>:</th>
			<td>
				<input onclick="toggle_regex ()" id="regex" type="checkbox" value="regex" name="regex"<?php if (isset ($_POST['regex'])) echo 'checked="checked"' ?>/>
		  <span id="regex-options" <?php if (!isset ($_POST['regex'])) : ?>style="display: none"<?php endif; ?> class="sub">
				<label for="case"><?php _e ('case-insensitive:', 'search-regex') ?></label> <input id="case" type="checkbox" name="regex_case" value="caseless"<?php if (isset ($_POST['regex_case'])) echo 'checked="checked"' ?>/>
		  	<label for="multi"><?php _e ('multi-line:', 'search-regex') ?></label> <input id="multi" type="checkbox" name="regex_multi" value="multiline"<?php if (isset ($_POST['regex_multi'])) echo 'checked="checked"' ?>/>
		  	<label for="dotall"><?php _e ('dot-all:', 'search-regex') ?></label> <input id="dotall" type="checkbox" name="regex_dot" value="dotall"<?php if (isset ($_POST['regex_dot'])) echo 'checked="checked"' ?>/>
			</span>
		  </td>
		</tr>
		<tr>
		  <th width="150"></th>
			<td><p class="submit">
      	<input type="submit" name="search" value="<?php _e ('Search', 'search-regex')?> &raquo;" />
	     	<input type="submit" name="replace" value="<?php _e ('Replace', 'search-regex')?> &raquo;" />
				<input type="submit" name="replace_and_save" value="<?php _e ('Replace &amp; Save &raquo;', 'search-regex') ?>"/>
    		</p>
			</td>
		</tr>
	</table>
	</form>
</div>
