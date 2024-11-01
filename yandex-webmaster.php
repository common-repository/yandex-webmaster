<?php
/*
Plugin Name: Yandex WebMaster
Plugin URI: http://tigor.org.ua/yandex-webmaster/
Description: Shows information from Yandex Webmaster Console
Version: DEV
Author: TIgor
Author URI: http://tigor.org.ua
License: GPL2
*/


/*  Copyright 2012 Tesliuk Igor  (email : tigor@tigor.org.ua)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Hooks registration
require_once('WMYauth.php');
require_once('WMlogic.php');
register_activation_hook(__FILE__,'ya_wm_activator');
add_action("plugins_loaded", "ya_wm_init");
add_action('admin_menu',"ya_wm_menu");




	// This is function for options page
function options_ya_wm() {

	
	?> <form method="post" action="options.php"> 
	<h2>Yandex Webmaster (plugin for WordPress)</h2><?php 
	settings_fields('ya_wm_group');
	$options = get_option('ya_wm_options');
	$auth = get_option('ya_wm_auth');
	$object = get_option('ya_wm_object');
	
	
	if ($options['clear'])
		{
		echo 'Starting WIPING<br />';
		$auth->clear();
		echo 'Auth Clear<br />';
		$object->clear();
		echo 'Object Clear<br />';
		$options['clear'] = false;
		preg_match('@^(?:http://)?([^/]+)@i',home_url('/'), $matches);
		$host = $matches[1];
		$options['host'] = $host;
		echo 'Saving to database.';
		update_option('ya_wm_auth', $auth);
		echo '.';
		update_option('ya_wm_options', $options);
		echo '.';
		update_option('ya_wm_object', $object);
		echo '.<br />Done!';

		}
	

	if ($options['code']!='')
		{
		$auth->bycode($options['code']);
		
		$object->auth($auth->get_token());
		if (!$object->set_ids())
			{
			$auth->set_error($object->get_error());
			}
		update_option('ya_wm_auth', $auth);
		if (!$auth->check_token())
			{
			echo $auth->name_error();
			} else {
			update_option('ya_wm_object', $object);
			}
		$options['code'] = '';
		update_option('ya_wm_options', $options);
		} 
		
		
	
	
	if (!$auth->check_token()){
		
		// IF token is not valid anymore show authorization screen
		
		?>
			
			
			
			
			
			
			<h2>User Auth</h2>
				<script type="text/javascript">
				var newwindow;
				function poptastic(url)
				{
					newwindow=window.open(url,'name','height=480,width=640');
					if (window.focus) {newwindow.focus()}
				}
				</script>
				
				
				<table class="form-table">
					<tr>
						<th>1. Go to Yandex and Allow access. Copy code.</th>
						<td><a href="javascript:poptastic('https://oauth.yandex.ru/authorize?response_type=code&display=popup&client_id=a2c8d9e225144dfbab2b4f7fbd6a2e81');">Popup window</a>. DO NOT PRESS GET TOKEN THERE!</td>
					</tr>
					<tr valign="top"><th scope="row">2. Paste code here:</th>

						<td><input type="text" name="ya_wm_options[code]" value="<?php echo $options['code']; ?>" /></td>

					</tr>
					<tr>
						<th>3. Click submit.</th>
						<td><input type="submit" class="button-primary" value="Submit" /></td>
					
					</tr>
					
				</table>
				<input type="hidden" name="ya_wm_options[host]" value="<?php echo $options['host']; ?>" />
				<?php
				
		} else {
		

		
		if ($object->set_hostid($options['host']))
			{
				update_option('ya_wm_object', $object);	
				// If everything is fine
				echo '<div style="width:480px;">';
				ya_wm_dashboard_widget_function();
				echo '</div>';
				
			} else {
				if ( '' == $object->hostlist) 
				{
				$auth->clear();
				update_option('ya_wm_auth', $auth);
				echo 'Token has error please get New.';
				?>
				
				<script type="text/javascript">
				var newwindow;
				function poptastic(url)
				{
					newwindow=window.open(url,'name','height=480,width=640');
					if (window.focus) {newwindow.focus()}
				}
				</script>
				
				
				<table class="form-table">
					<tr>
						<th>1. Go to Yandex and Allow access. Copy code.</th>
						<td><a href="javascript:poptastic('https://oauth.yandex.ru/authorize?response_type=code&display=popup&client_id=a2c8d9e225144dfbab2b4f7fbd6a2e81');">Popup window</a>. DO NOT PRESS GET TOKEN THERE!</td>
					</tr>
					<tr valign="top"><th scope="row">2. Paste code here:</th>

						<td><input type="text" name="ya_wm_options[code]" value="<?php echo $options['code']; ?>" /></td>

					</tr>
					<tr>
						<th>3. Click submit.</th>
						<td><input type="submit" class="button-primary" value="Submit" /></td>
					
					</tr>
					
				</table>
				<input type="hidden" name="ya_wm_options[host]" value="<?php echo $options['host']; ?>" />
				<?php
				
				}else{
					// If not posible to get hostid
					?>
					<hr />
					We were unable to automaticly set your blog hostname. Choose:
					<br />
					<select name="ya_wm_options[host]">
					<?php foreach ($object->hostlist as $host) { ?>
						<option <?php if ($host == $options['host']) {echo 'selected';} ?> value="<?php echo $host; ?>"><?php echo $host; ?></option>
								
					<?php } ?>				
					</select>
					<?php
				}
			
			
			}
		
		
		
	
		// echo $object->show_error();
		}
			
			
			
			
	update_option('ya_wm_object', $object);	
	
	?>
	<hr />
	<input type="checkbox" value="true" name="ya_wm_options[clear]" /> Wipe data?
	<p class="submit">
    <input type="submit" class="button-primary" value="Save Changes" />
    </p>
	</form><?php
		
}

	
// This functios for dashboard widget
function ya_wm_dashboard_widget_function(){
	$options = get_option('ya_wm_options');
	$auth = get_option('ya_wm_auth');
	$object = get_option('ya_wm_object');
	
	if (60*60 < $object->time_since_last_update())
	{
		if ($object->update()) {
		update_option('ya_wm_object', $object);
		}
		
	}
	
	
	if (($object->is_verified()) and ($object->is_indexed()))
	{
	
		// Show information about dangerous code
		if ($object->is_virused())
		{?>
			<div style="background-color: #ee3333;">
			Your site has dangerous code!!!
			</div>
		
		<?php } else { ?>
			<div style="background-color: #33ee33;">
			No viruses.
			</div>
		
		<?php }	?> 
		<div>
		Quoting index (tYC): <?php echo $object->get_tyc(); ?>
		</div>
		<div>
		Pages in index: <?php echo $object->get_index_count(); ?> (crawled: <?php echo $object->get_url_count(); ?>)
		</div>
		<div>Your site was last indexed:<?php echo $object->accessed(); 
		echo ' ('.round(((time() - strtotime($object->accessed()))/(60*60)));
	
		?> hours ago).
		</div>
		<div>
		Last update: <?php echo round($object->time_since_last_update()/60); ?> minutes ago.
		</div>
		<?php
	} else {
		echo $object->verification_info();
		echo $object->indexed_info();
	}
	
	// Check token expiration time
	$exp_time = $auth->expires_in();
	if ($exp_time > 0) 
	{ ?>
		<div style="font-size:75%; text-align:right;">
		Plugin access will be expired: <?php echo date('d M Y',time()+$exp_time); ?>.
		</div>
	<?php } else { ?>
		<div style="background-color: #ee3333;">
		Plugin access token has expired. <a href="options-general.php?page=ya_wm">Get new</a>!
		</div>
	<?php }  ?>
	
	<div style="background-color:#ffcc00;text-align:right;">
	service by <span style="color:#ff0000">Y</span>andex <a href="http://webmaster.yandex.ru/">Webmaster</a>
	</div>
	<?php 

}


function ya_wm_add_dashboard_widgets(){
	
	wp_add_dashboard_widget('ya_wm_dashboard_widget', 'Yandex Webmaster', 'ya_wm_dashboard_widget_function');	
 
	
	}

function ya_wm_activator()	{

	if (!get_option('ya_wm_auth'))
	{
		$auth = new WMauth('a2c8d9e225144dfbab2b4f7fbd6a2e81', 'ecf9b56384c74be491dc29d5f4aafba5');
		add_option('ya_wm_auth', $auth,'', 'no' );
	}
	
	
	$logic = new YWM();
	add_option('ya_wm_object', $logic,'', 'no' );

	if (!get_option('ya_wm_options'))
	{
		$options['host'] = home_url('/');	
		add_option('ya_wm_options', $options );
	}
}
	

	
function register_ya_wm_settings() {
	register_setting('ya_wm_group','ya_wm_options');
}

function ya_wm_menu() {
	add_options_page('Yandex WebMater', 'Yandex WM', 'manage_options', 'ya_wm', 'options_ya_wm');
	add_action( 'admin_init', 'register_ya_wm_settings' );
}

function ya_wm_init() {
	add_action('wp_dashboard_setup', 'ya_wm_add_dashboard_widgets' );
}





?>