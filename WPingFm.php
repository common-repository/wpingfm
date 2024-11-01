<?php
/*
Plugin Name: WPing.FM
Plugin URI: http://wordpress.org/extend/plugins/wpingfm/
Description: Updates Ping.FM when you create, edit, or update a blog post. Based upon the Microblog Updater and inspired by <a href="http://www.soldoutactivist.com/project-pingpressfm">PingPress.fm</a>.
Version: 1.0
Author: Eddy De Clercq
Author URI: http://www.grumpyoldman.be

Copyright 2008

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

http://www.gnu.org/licenses/gpl.txt

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
include_once 'PHPingFM.php';
// Library needed for easy post to Ping.FM, check http://dmitri.nfshost.com/phpingfm/ for details

function doPingFMAPIPost($status, $link) {
	global $debug;
	$developer_key = '6e48010489e954d7b42c6e018d15d0fb';
	$user_app_key = get_option('wpingfm-pingfmkey'); 
	$PHPingFM = new PHPingFM($developer_key, $user_app_key);
	$message = $status;
	if ($link != '') {
		if (strlen($message . $link) + 1 > 140) {
	   	 $message .= ' ' . file_get_contents("http://tinyurl.com/api-create.php?url=" . $link);
		} else {
			$message .= ' ' . $link;
		}
		}
	$posted = $PHPingFM->post("status", $message);
}

function doPingFMPost($status, $link) {
	if ($status != '') {
			doPingFMAPIPost($status, $link);
	}
}

function wpingfm_transition_post($new_status, $old_status, $post)  {

	$status = '';
	$link = '';

	if (get_option('wpingfm-newpost-created-update') == '1' && $post->post_type == 'post' && $old_status == 'new' && $new_status == 'draft') {
		// Update Ping.fm when a new post is created (saved but not published)
		$status = str_replace('#title#', $post->post_title, get_option('wpingfm-newpost-created-text'));
	}

	if (get_option('wpingfm-newpost-published-update') == '1' && $post->post_type == 'post' && ($old_status == 'new' || $old_status == 'draft' || $old_status == 'future') && $new_status == 'publish') {
		// Update Ping.fm when the new post is published
		$status = str_replace('#title#', $post->post_title, get_option('wpingfm-newpost-published-text'));
		if(get_option('wpingfm-newpost-published-showlink') == '1') {
			$link = get_permalink($post->ID);
		}
	}

	doPingFMPost($status, $link);

}

function wpingfm_edit_post($post_ID, $post) {

	$status = '';
	$link = '';

	if (get_option('wpingfm-newpost-edited-update') == '1' && $post->post_type == 'post' && $post->post_status == 'draft') {
		// Update Ping.fm when the new post is edited (re-saved but not published)
		$status = str_replace('#title#', $post->post_title, get_option('wpingfm-newpost-edited-text'));
	}

	if (get_option('wpingfm-oldpost-edited-update') == '1' && $post->post_type == 'post' && $post->post_status == 'publish' && ($_POST['save'] != '' || $_POST['submit'] != '')) {
		// Update Ping.fm when an old post has been edited
		$status = str_replace('#title#', $post->post_title, get_option('wpingfm-oldpost-edited-text'));
		if(get_option('wpingfm-oldpost-edited-showlink') == '1') {
			$link = get_permalink($post->ID);
		}
	}

	$link = get_permalink($post->ID);

	doPingFMPost($status, $link);

}

add_action('admin_menu', 'wpingfm_add_options_page');
add_action('edit_post', 'wpingfm_edit_post', 5, 2);
add_action('transition_post_status', 'wpingfm_transition_post', 5, 3);

function wpingfm_add_options_page() {
	if (function_exists('add_options_page')) {
		add_options_page('WPing.FM', 'WPing.FM', 8, basename(__FILE__), 'wpingfm_show_options_page');
	}
}

function setCheckbox($theFieldname) {
	return get_option($theFieldname) == '1' ? 'checked="true"' : '';
}

function wpingfm_show_options_page() {

	if(get_option('wpingfm-init') != '1' || $_POST['submit'] == 'reset options'){

		update_option('wpingfm-newpost-created-update', '1');
		update_option('wpingfm-newpost-created-text', 'Writing a new blog post!');
		
		update_option('wpingfm-newpost-edited-update', '1');
		update_option('wpingfm-newpost-edited-text', 'Still writing the new blog post..');

		update_option('wpingfm-newpost-published-update', '1');
		update_option('wpingfm-newpost-published-text', 'Published a new post: #title#');
		update_option('wpingfm-newpost-published-showlink', '1');

		update_option('wpingfm-oldpost-edited-update', '1');
		update_option('wpingfm-oldpost-edited-text', 'Updated an old post: #title#');
		update_option('wpingfm-oldpost-edited-showlink', '1');

		update_option('wpingfm-init', '1');

	}

	if($_POST['submit'] == 'save options'){

		update_option('wpingfm-newpost-created-update', $_POST['wpingfm-newpost-created-update']);
		update_option('wpingfm-newpost-created-text', $_POST['wpingfm-newpost-created-text']);
		
		update_option('wpingfm-newpost-edited-update', $_POST['wpingfm-newpost-edited-update']);
		update_option('wpingfm-newpost-edited-text', $_POST['wpingfm-newpost-edited-text']);

		update_option('wpingfm-newpost-published-update', $_POST['wpingfm-newpost-published-update']);
		update_option('wpingfm-newpost-published-text', $_POST['wpingfm-newpost-published-text']);
		update_option('wpingfm-newpost-published-showlink', $_POST['wpingfm-newpost-published-showlink']);

		update_option('wpingfm-oldpost-edited-update', $_POST['wpingfm-oldpost-edited-update']);
		update_option('wpingfm-oldpost-edited-text', $_POST['wpingfm-oldpost-edited-text']);
		update_option('wpingfm-oldpost-edited-showlink', $_POST['wpingfm-oldpost-edited-showlink']);

	} else if ($_POST['submit-type'] == 'wpingfm-login-pingfm'){
		if($_POST['wpingfm-pingfmkey'] != ''){
			update_option('wpingfm-pingfmkey', $_POST['wpingfm-pingfmkey']);
		}else{
			echo("<div style='border:1px solid red; padding:20px; margin:20px; color:red;'>You need to provide your Ping.fm API key!</div>");
		}
	}

	if($_POST['submit'] == 'send update'){
		doPingFMPost($_POST['wpingfm-status'],$_POST['wpingfm-link']);
	}

	echo '<style type="text/css">
		fieldset{ margin:20px 0; 
		border:1px solid #cecece;
		padding:15px;
		}
	</style>
	
	<div class="wrap">

		<h2>Send status update</h2>

		<form method="post">
		<div>
			<p>
				<label for="wpingfm-status">Message</label><br/>
				<textarea rows="3" cols="60" name="wpingfm-status"></textarea>
			</p>
			<p>
				<label for="wpingfm-status">Link (optional)</label><br/>
				<input type="text" name="wpingfm-link" id="wpingfm-link" value="" />
			</p>
			<p>
				<input type="submit" name="submit" value="send update" />
			</p>
		</div>
		</form>

	</div>
	
	<div class="wrap">

		<h2>WPing.FM update options</h2>

		<form method="post">
		<div>
			<fieldset>
				<legend>New post created</legend>
				<p>
					<input type="checkbox" name="wpingfm-newpost-created-update" id="wpingfm-newpost-created-update" value="1" ' . setCheckbox('wpingfm-newpost-created-update') .' />
					<label for="wpingfm-newpost-created-update">Update Ping.FM when a new post is created (saved but not published)</label>
				</p>
				<p>
					<label for="wpingfm-newpost-created-text">Text for this Ping.FM update (use #title# as placeholder for page title)</label><br />
					<input type="text" name="wpingfm-newpost-created-text" id="wpingfm-newpost-created-text" size="60" maxlength="146" value="' . get_option('wpingfm-newpost-created-text') . '" />
				</p>
			</fieldset>

			<fieldset>
				<legend>New post edited</legend>
				<p>
					<input type="checkbox" name="wpingfm-newpost-edited-update" id="wpingfm-newpost-edited-update" value="1" ' . setCheckbox('wpingfm-newpost-edited-update') .' />
					<label for="wpingfm-newpost-edited-update">Update Ping.FM when the new post is edited (re-saved but not published)</label>
				</p>
				<p>
					<label for="wpingfm-newpost-edited-text">Text for this Ping.FM update (use #title# as placeholder for page title)</label><br />
					<input type="text" name="wpingfm-newpost-edited-text" id="wpingfm-newpost-edited-text" size="60" maxlength="146" value="'. get_option('wpingfm-newpost-edited-text') .'" />
				</p>
			</fieldset>

			<fieldset>
				<legend>New post published</legend>
				<p>
					<input type="checkbox" name="wpingfm-newpost-published-update" id="wpingfm-newpost-published-update" value="1" ' . setCheckbox('wpingfm-newpost-published-update') .' />
					<label for="wpingfm-newpost-published-update">Update Ping.FM when the new post is published</label>
				</p>
				<p>
					<label for="wpingfm-newpost-published-text">Text for this Ping.FM update (use #title# as placeholder for page title)</label><br />
					<input type="text" name="wpingfm-newpost-published-text" id="wpingfm-newpost-published-text" size="60" maxlength="146" value="'. get_option('wpingfm-newpost-published-text') .'" />
					&nbsp;&nbsp;
					<input type="checkbox" name="wpingfm-newpost-published-showlink" id="wpingfm-newpost-published-showlink" value="1" '. setCheckbox('wpingfm-newpost-published-showlink').' />
					<label for="wpingfm-newpost-published-showlink">Link title to blog?</label>
				</p>
			</fieldset>

			<fieldset>
				<legend>Existing posts</legend>
				<p>
					<input type="checkbox" name="wpingfm-oldpost-edited-update" id="wpingfm-oldpost-edited-update" value="1" ' . setCheckbox('wpingfm-oldpost-edited-update') .' />
					<label for="wpingfm-oldpost-edited-update">Update Ping.FM when the an old post has been edited</label>
				</p>
				<p>
					<label for="wpingfm-oldpost-edited-text">Text for this Ping.FM update (use #title# as placeholder for page title)</label><br />
					<input type="text" name="wpingfm-oldpost-edited-text" id="wpingfm-oldpost-edited-text" size="60" maxlength="146" value="'. get_option('wpingfm-oldpost-edited-text') .'" />
					&nbsp;&nbsp;
					<input type="checkbox" name="wpingfm-oldpost-edited-showlink" id="wpingfm-oldpost-edited-showlink" value="1" '. setCheckbox('wpingfm-oldpost-edited-showlink').' />
					<label for="wpingfm-oldpost-edited-showlink">Link title to blog?</label>
				</p>
			</fieldset>

			<input type="submit" name="submit" value="save options" />
			<input type="submit" name="submit" value="reset options" />

		</div>
		</form>
	</div>

	<div class="wrap">

		<h2>Ping.fm Account Information</h2>
		In order to function, you need a <a href="http://www.ping.fm">Ping.fm account</a> and an <a href="http://ping.fm/key/">API key</a>. 
		<form method="post" >
		<div>
			<p>
				<label for="wpingfm-pingfmkey">Your API key:</label>
				<input type="text" name="wpingfm-pingfmkey" id="wpingfm-pingfmkey" value="'. get_option('wpingfm-pingfmkey') .'" />
			</p>
			<input type="hidden" name="submit-type" value="wpingfm-login-pingfm">
			<p><input type="submit" name="submit" value="save key" />
		</div>
		</form>

	</div>';


}
?>