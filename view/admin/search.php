<?php if (!defined( 'ABSPATH' )) die( 'No direct access allowed' ); ?>
<div class="wrap">
	<h2><?php _e( 'Search Regex', 'search-regex' ) ?></h2>
	<p><?php _e( "Replacements will only be saved to the database if you click '<strong>Replace &amp; Save</strong>', otherwise you will get a preview of the results.", 'search-regex' ) ?></p>
	<p><?php _e( "NOTE: <strong>No liability</strong> is accepted for any damage caused.  You are strongly advised to backup your database before making any changes.", 'search-regex' ) ?></p>

	<form method="post" action="">
		<table class="searchargs">
		  <tr>
		    <th width="150"><?php _e( "Source", 'search-regex' ) ?></th>
		    <td>
					<select name="source">
						<?php foreach ( $searches AS $searcher ) : ?>
							<option value="<?php echo get_class( $searcher ) ?>" <?php if ( strcasecmp( $source, get_class( $searcher ) ) == 0 || ( $source == '' && strcasecmp( get_class( $searcher ), 'SearchPostContent' ) == 0 ) ) echo ' selected="selected"' ?>>
								<?php echo $searcher->name() ?>
							</option>
						<?php endforeach; ?>
					</select>

					<strong><?php _e( 'Limit to', 'search-regex' ); ?>:</strong>
					<?php $limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 0 ?>
					<select name="limit">
						<?php echo $this->select( array( '0' => __( 'No limit', 'search-regex' ), '10' => '10', '25' => '25', '50' => '50', '100' => '100' ), $limit ) ?>
					</select>

					<strong><?php _e( 'Order By', 'search-regex' ); ?>:</strong>
					<?php $orderby = isset( $_POST['orderby'] ) ? $_POST['orderby'] : ''; ?>
					<select name="orderby">
						<?php echo $this->select( array( 'asc' => __( 'Ascending', 'search-regex' ), 'desc' => __( 'Descending', 'search-regex' ) ), $orderby ); ?>
					</select>
				</td>
			</tr>
			<tr>
				<th width="150" valign="top"><?php _e( "Search pattern", 'search-regex' ) ?></th>
				<td>
				  <input class="term" type="text" name="search_pattern" value="<?php esc_attr_e( $search ) ?>"/><br/>
				</td>
			</tr>
			<tr>
			  <th width="150" valign="top"><?php _e( 'Replace pattern', 'search-regex' ) ?></th>
				<td>
				  <input class="term" type="text" name="replace_pattern" value="<?php esc_attr_e( $replace ) ?>"/><br/>
				</td>
			</tr>
			<tr>
				<th><label for="regex"><?php _e( 'Regex', 'search-regex' ); ?></label>:</th>
				<td>
					<input id="regex" type="checkbox" value="regex" name="regex"<?php if (isset ($_POST['regex'])) echo 'checked="checked"' ?>/>
			  	<span id="regex-options" <?php if (!isset ($_POST['regex'])) : ?>style="display: none"<?php endif; ?> class="sub">
						<label for="case"><?php _e( 'case-insensitive:', 'search-regex' ) ?></label> <input id="case" type="checkbox" name="regex_case" value="caseless"<?php if (isset ($_POST['regex_case'])) echo 'checked="checked"' ?>/>
				  	<label for="multi"><?php _e( 'multi-line:', 'search-regex' ) ?></label> <input id="multi" type="checkbox" name="regex_multi" value="multiline"<?php if (isset ($_POST['regex_multi'])) echo 'checked="checked"' ?>/>
				  	<label for="dotall"><?php _e( 'dot-all:', 'search-regex' ) ?></label> <input id="dotall" type="checkbox" name="regex_dot" value="dotall"<?php if (isset ($_POST['regex_dot'])) echo 'checked="checked"' ?>/>
						&mdash; <?php _e( 'remember to surround your regex with a delimiter!', 'search-regex' ); ?>
					</span>
			  </td>
			</tr>
			<tr>
			  <th width="150"></th>
				<td><p class="submit">
	      	<input type="submit" name="search" value="<?php esc_attr_e( 'Search', 'search-regex' )?> &raquo;" />

					<?php if (current_user_can( 'administrator' ) || current_user_can( 'search_regex_write' )) : ?>
		     	<input type="submit" name="replace" value="<?php esc_attr_e( 'Replace', 'search-regex' )?> &raquo;" />
					<input type="submit" name="replace_and_save" value="<?php esc_attr_e( 'Replace &amp; Save &raquo;', 'search-regex' ) ?>"/>
					<?php endif; ?>
	    		</p>
				</td>
			</tr>
		</table>
	</form>
</div>

<script type="text/javascript" charset="utf-8">
	var wp_loading = '<?php echo plugins_url( '/images/small.gif', $this->base_url() ); ?>';
</script>
