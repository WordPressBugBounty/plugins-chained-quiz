<?php // requires login?
if(!empty($quiz->require_login) and !is_user_logged_in()) {
	$current_url = is_ssl() ? 'https://' : 'http://';
	$current_url .= ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
	echo "<p><b>".__('You need to be registered and logged in to take this quiz.', 'chained') . 
		" <a href='".esc_url(wp_login_url($current_url))."'>".__('Log in', 'chained')."</a>";
	if(get_option("users_can_register")) {
		echo " ".__('or', 'chained')." <a href='".esc_url(site_url("/wp-login.php?watu_register=1&action=register&redirect_to=".urlencode(get_permalink( $post->ID ))))."'>".__('Register', 'chained')."</a></b>";        
	}
	echo "</p>";
	return false;
}
// can re-take?
if(!empty($quiz->require_login) and !empty($quiz->times_to_take)) {
	$cnt_takings=$wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM ".CHAINED_COMPLETED."
				WHERE quiz_id=%d AND user_id=%d", $quiz->id, $user_ID)); 
	
	// multiple times allowed, but number is specified	
	if($quiz->times_to_take and $cnt_takings >= $quiz->times_to_take) {
		echo "<p><b>";
		printf(__("Sorry, you can take this quiz only %d times.", 'chained'), intval($quiz->times_to_take));
		echo "</b></p>";
		return false;
	}			
}
?>
<?php if(!empty($first_load)):?><div class="chained-quiz" id="chained-quiz-div-<?php echo intval($quiz->id)?>"><?php endif;?>
<form method="post" id="chained-quiz-form-<?php echo intval($quiz->id)?>">
	<?php wp_nonce_field('chainedquiz_nonce', 'chainedquiz_nonce'); ?>
	<div class="chained-quiz-area" id="chained-quiz-wrap-<?php echo intval($quiz->id)?>">
		<?php if(!empty($quiz->email_user) and !is_user_logged_in()):?>
			<div class="chained-quiz-email" <?php if($quiz->hide_email_field and !empty($_POST['chained_email']) and preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $_POST['chained_email'])):?>style="display:none;"<?php endif;?>>
				<p><label><?php _e('Your email address:', 'chained');?></label> <input type="text" name="chained_email" value="<?php echo esc_attr(sanitize_email($_POST['chained_email'] ?? ''))?>" id="chainedUserEmail" class="chained-quiz-email <?php if(!empty($quiz->email_required)) echo 'required';?>"></p>
			</div>
		<?php endif;?> 
		<div class="chained-quiz-question" id="chained-quiz-question-<?php echo intval($question->id)?>">
			<?php echo $_question->display_question($question);?>
		</div>
		
		<div class="chained-quiz-choices" id="chained-quiz-choices-<?php echo intval($question->id)?>">
				<?php echo $_question->display_choices($question, $choices);?>
		</div>
		
		<?php if(!empty($question->accept_comments)):?>
		<div class="chained-quiz-comments" id="chained-quiz-comments-<?php echo intval($question->id)?>">
				<label><?php echo esc_html(stripslashes($question->accept_comments_label));?></label>
				<input class='chained-quiz-frontend chained-quiz-comment' type='text' name='comments'>
		</div>
		<?php endif;?>
		
		<?php echo $text_captcha_html ?? '' ?>
		
		<div class="chained-quiz-action" style='display:<?php echo ($question->autocontinue and $question->qtype == 'radio') ? 'none' : 'block';?>'>
			<input type="button" id="chained-quiz-action-<?php echo intval($quiz->id)?>" value="<?php echo empty($ui['go_ahead_value']) ? esc_attr(__('Go ahead', 'chained')) : esc_attr($ui['go_ahead_value']) ?>" onclick="chainedQuiz.goon(<?php echo intval($quiz->id)?>, '<?php echo esc_url(admin_url('admin-ajax.php'))?>');" disabled="true">
			<img src="<?php echo esc_url(CHAINED_URL.'img/loading.gif')?>" width="32" height="32" alt="Loading..." style="display: none;" id="chainedLoading">
		</div>
	</div>
	<input type="hidden" name="question_id" value="<?php echo intval($question->id)?>">
	<input type="hidden" name="quiz_id" value="<?php echo intval($quiz->id)?>">
	<input type="hidden" name="post_id" value="<?php echo intval($post->ID)?>">
	<input type="hidden" name="question_type" value="<?php echo esc_attr($question->qtype)?>">
	<input type="hidden" name="points" value="0">
</form>
<?php if(!empty($first_load)):?>
</div>
<script type="text/javascript" >
jQuery(function(){
	chainedQuiz.initializeQuestion(<?php echo $quiz->id?>);	
});

// prevent enter key from reloading the form
jQuery(document).ready(function() {
  jQuery(window).keydown(function(event){
    if(event.keyCode == 13) {
      event.preventDefault();
      return false;
    }
  });
});
</script><?php endif;?>
