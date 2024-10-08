<div class="wrap">
	<h1><?php _e('General Options', 'chained');?></h1>	
	
	<div class="postbox-container" style="width:73%;margin-right:2%;">

		<form method="post">
		<div class="postbox wp-admin"  style="padding:5px;">
			<h3 class="hndle"><span><?php _e('Roles', 'chained') ?></span></h3>
			<div class="inside">		
			<h4><?php _e('Roles that can manage quizzes', 'chained')?></h4>
			
			<p><?php _e('By default only Administrator and Super admin can manage the quizzes. You can enable other roles here.', 'chained')?></p>
			<p><?php foreach($roles as $key=>$r):
							if($key=='administrator') continue;
							$role = get_role($key);?>
							<input type="checkbox" name="manage_roles[]" value="<?php echo $key?>" <?php if($role->has_cap('chained_manage')) echo 'checked';?>> <?php echo $role->name?> &nbsp;
						<?php endforeach;?></p>	
			<p><?php _e('Only administrator or superadmin can change this!', 'chained')?></p>	
			</div>
			
			<h3 class="hndle"><span><?php _e('Email options', 'chained') ?></span></h3>
			<div class="inside">
				<h4><?php _e('Sender and subjects of the automated emails', 'chained')?></h4>
				
				<p><label><?php _e('Sender name:', 'chained');?></label> <input type="text" name="sender_name" value="<?php echo esc_attr(stripslashes(get_option('chained_sender_name')));?>"></p>
				<p><label><?php _e('Sender email:', 'chained');?></label> <input type="text" name="sender_email" value="<?php echo esc_attr(get_option('chained_sender_email'))?>"></p>
				<p><label><?php _e('Subject of email sent to admin:', 'chained');?></label> <input type="text" name="admin_subject" size="60" value="<?php echo esc_attr(stripslashes(get_option('chained_admin_subject')));?>"></p>
				<p><label><?php _e('Subject of email sent to quiz taker:', 'chained');?></label> <input type="text" name="user_subject" size="60" value="<?php echo esc_attr(stripslashes(get_option('chained_user_subject')));?>"></p>
			</div>			
			
			<h3 class="hndle"><span><?php _e('User interface options', 'chained') ?></span></h3>
			<div class="inside">
				<p><input type="checkbox" name="hide_go_ahead" value="1" <?php if(!empty($ui['hide_go_ahead'])) echo 'checked'?>> <?php _e('Automatically hide the "Go ahead" button when possible (typically on "single answer" questions).', 'chained');?> </p>

				<p><input type="checkbox" name="dont_autoscroll" value="1" <?php if(!empty($ui['dont_autoscroll'])) echo 'checked'?>> <?php _e('Do not auto-scroll when going to the next question.', 'chained');?> </p>				
				
				<p><?php _e('Text/Value of the "Go ahead" button:', 'chained');?> <input type="text" name="go_ahead_value" value="<?php echo empty($ui['go_ahead_value']) ?__('Go Ahead', 'chained') : esc_attr(stripslashes($ui['go_ahead_value'])) ?>"></p>
			</div>
			
			<h3 class="hndle"><span><?php _e('CSV Exports', 'chained') ?></span></h3>
			<div class="inside">
				<p><label><?php _e('Field separator:','chained')?></label> <select name="csv_delim">
					<option value="," <?php if($delim == ',') echo 'selected'?>><?php _e('Comma', 'chained');?></option>
					<option value=";" <?php if($delim == ';') echo 'selected'?>><?php _e('Semicolon', 'chained');?></option>
					<option value="tab" <?php if($delim == 'tab') echo 'selected'?>><?php _e('TAB', 'chained');?></option>
				</select></p>
				<input type="checkbox" name="csv_quotes" value="1" <?php if(get_option('chained_csv_quotes')) echo 'checked'?>> <?php _e('Add quotes around text fields (recommended)', 'chained')?>	
			</div>
			
			
            <h3><?php _e('Question based captcha', 'chained')?></h3>
            <div class="inside">   
                <p><?php _e("You can use a simple text-based captcha. We have loaded 3 basic questions but you can edit them and load your own. Make sure to enter only one question per line and use = to separate question from answer.", 'chained')?></p>
                
                <p><textarea name="text_captcha" rows="10" cols="70"><?php echo stripslashes($text_captcha);?></textarea></p>
                <div class="help"><?php _e('This question-based captcha can be enabled individually by selecting a checkbox in the quiz settings form. If you do not check the checkbox, the captcha question will not be generated.', 'chained');?></div>	
            </div>		
			
			<h3 class="hndle"><span><?php _e('Other options', 'chained') ?></span></h3>
			
			<p><input type="checkbox" name="gdpr_ips" value="1" <?php if(!empty($gdpr_ips)) echo 'checked'?>> <?php _e('Mask IP addresses for GDPR compliance.', 'chained');?></p>
			
			<p><input type="checkbox" name="debug_mode" value="1" <?php if(get_option('chained_debug_mode')) echo 'checked'?> /> <?php _e('Enable debug mode to see SQL errors. (Useful in case you have any problems)', 'chained')?></p>	
			
			<p><input type="submit" value="<?php _e('Save Options', 'chained')?>" name="ok" class="button button-primary"></p>
		</div>
		<?php wp_nonce_field('chained_options');?>
		</form>
		
	</div>
	<div id="chained-sidebar">
			<?php include(CHAINED_PATH."/views/sidebar.html.php");?>
	</div>		
</div>	
