<?php if (!defined( 'ABSPATH' )) die( 'No direct access allowed' ); ?>
<div class="wrap">
	<h2><?php _e( 'Search Regex', 'search-regex' ) ?></h2>
	<p><?php _e( "Replacements will only be saved to the database if you click '<strong>Replace &amp; Save</strong>', otherwise you will get a preview of the results.", 'search-regex' ) ?></p>
	<p><?php _e( "NOTE: <strong>No liability</strong> is accepted for any damage caused.  You are strongly advised to backup your database before making any changes.", 'search-regex' ) ?></p>

	<form method="post" action="">
		<table class="searchargs">
			<tr>
			    <th width="150">
					<?php _e( "Source", 'search-regex' ) ?>
				</th>
			    <td>
					<select name="source">
						<?php foreach ( $searches AS $searcher ) : ?>
							<option value="<?php echo get_class( $searcher ) ?>" <?php if ( strcasecmp( $source, get_class( $searcher ) ) == 0 || ( $source == '' && strcasecmp( get_class( $searcher ), 'SearchPostContent' ) == 0 ) ) echo ' selected="selected"' ?>>
								<?php echo esc_attr( $searcher->name() ) ?>
							</option>
						<?php endforeach; ?>
					</select>

					<strong><?php _e( 'Limit to', 'search-regex' ); ?>:</strong>
					<?php $limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 0 ?>
					<select name="limit">
						<option <?php selected( $limit, 0 ); ?> value="0"><?php _e( 'No limit', 'search-regex' ); ?></option>
						<option <?php selected( $limit, 10 ); ?> value="10"><?php _e( '10', 'search-regex' ); ?></option>
						<option <?php selected( $limit, 25 ); ?> value="25"><?php _e( '25', 'search-regex' ); ?></option>
						<option <?php selected( $limit, 50 ); ?> value="50"><?php _e( '50', 'search-regex' ); ?></option>
						<option <?php selected( $limit, 100 ); ?> value="100"><?php _e( '100', 'search-regex' ); ?></option>
					</select>

					<strong><?php _e( 'Order By', 'search-regex' ); ?>:</strong>
					<?php $orderby = isset( $_POST['orderby'] ) ? $_POST['orderby'] : ''; ?>
					<select name="orderby">
						<option <?php selected( $orderby, 'asc' ); ?>value="asc"><?php _e( 'Ascending', 'search-regex' ); ?></option>
						<option <?php selected( $orderby, 'desc' ); ?>value="desc"><?php _e( 'Descending', 'search-regex' ); ?></option>
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

			  		<span id="regex-options" class="sub">
						<label>
							<?php _e( 'case-insensitive:', 'search-regex' ) ?>
							<input type="checkbox" name="regex_case" <?php checked( !empty( $_POST['regex_case'] ) ); ?>/>
						</label>
				  		<label>
							<?php _e( 'multi-line:', 'search-regex' ) ?>
							<input type="checkbox" name="regex_multi" <?php checked( !empty( $_POST['regex_multi'] ) ); ?>/>
						</label>
				  		<label>
							<?php _e( 'dot-all:', 'search-regex' ) ?>
							<input type="checkbox" name="regex_dot" <?php checked( !empty( $_POST['regex_dot'] ) ); ?>/>
						</label>

						&mdash; <?php _e( 'remember to surround your regex with a delimiter!', 'search-regex' ); ?>
					</span>
			  </td>
			</tr>
			<tr>
			  <th width="150"></th>
				<td><p class="submit">
	      			<input type="submit" class="button-primary" name="search" value="<?php esc_attr_e( 'Search', 'search-regex' )?> &raquo;" />

					<?php if (current_user_can( 'administrator' ) || current_user_can( 'search_regex_write' )) : ?>
		     			<input type="submit" class="button" name="replace" value="<?php esc_attr_e( 'Replace', 'search-regex' )?> &raquo;" />
						<input type="submit" class="button" name="replace_and_save" value="<?php esc_attr_e( 'Replace &amp; Save &raquo;', 'search-regex' ) ?>"/>
					<?php endif; ?>
	    		</p>
				</td>
			</tr>

			<?php wp_nonce_field( 'search', 'search-regex-nonce' ); ?>
		</table>
	</form>
</div>

<script type="text/javascript" charset="utf-8">
	var wp_loading = '<?php echo plugins_url( '/images/small.gif', dirname( __FILE__ ) ); ?>';
</script>
