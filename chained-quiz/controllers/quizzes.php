<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class ChainedQuizQuizzes {
	static function manage() {
 		$action = empty($_GET['action']) ? 'list' : $_GET['action']; 
		switch($action) {
			case 'add':
				self :: add_quiz();
			break;
			case 'edit': 
				self :: edit_quiz();
			break;
			case 'list':
			default:
				self :: list_quizzes();	 
			break;
		}
	} // end manage()
	
	static function add_quiz() {
		$_quiz = new ChainedQuizQuiz();
		
		if(!empty($_POST['ok']) and check_admin_referer('chained_quiz')) {
			try {
				$qid = $_quiz->add($_POST);			
				chained_redirect("admin.php?page=chainedquiz_results&quiz_id=".$qid);
			}
			catch(Exception $e) {
				$error = $e->getMessage();
			}
		}
		
		$output = __('Congratulations, you completed the <span>quiz!</span>
<h2>{{result-title}}</h2>
{{result-text}}

You achieved {{points}} points from {{questions}} questions.', 'chained');
		$is_published = false;
		include(CHAINED_PATH.'/views/quiz.html.php');
	} // end add_quiz
	
	static function edit_quiz() {
		global $wpdb;
		$_quiz = new ChainedQuizQuiz();
		
		if(!empty($_POST['ok']) and check_admin_referer('chained_quiz')) {
			try {
				$_quiz->save($_POST, $_GET['id']);			
				chained_redirect("admin.php?page=chained_quizzes");
			}
			catch(Exception $e) {
				$error = $e->getMessage();
			}
		}
		
		// select the quiz
		$quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".CHAINED_QUIZZES." WHERE id=%d", intval($_GET['id'])));
	   $output = stripslashes($quiz->output); 
	   
	   // is this quiz currently published?
		$is_published = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[chained-quiz ".intval($_GET['id'])."]%' 
				AND post_status='publish' AND post_title!=''");	
		include(CHAINED_PATH.'/views/quiz.html.php');
	} // end edit_quiz
	
	// list and delete quizzes
	static function list_quizzes() {
		global $wpdb;
		$_quiz = new ChainedQuizQuiz();
		
		if(!empty($_GET['del']) and wp_verify_nonce($_GET['chained_delnonce'], 'chained_delete' ) ) {
			$_quiz->delete($_GET['id']);
			chained_redirect("admin.php?page=chained_quizzes");
		}
		
		if(!empty($_GET['copy']) and wp_verify_nonce($_GET['chained_nonce'], 'chained_copy' ) ) {
		   $_quiz->copy($_GET['id']);
		   chained_redirect("admin.php?page=chained_quizzes");
		}
		
		// select quizzes
		$quizzes = $wpdb->get_results("SELECT tQ.*, COUNT(tC.id) as submissions 
			FROM ".CHAINED_QUIZZES." tQ LEFT JOIN ".CHAINED_COMPLETED." tC ON tC.quiz_id = tQ.id AND tC.not_empty=1
			GROUP BY tQ.id ORDER BY tQ.id DESC");
		
		// now select all posts that have watu shortcode in them
		$posts=$wpdb->get_results("SELECT * FROM {$wpdb->posts} 
		WHERE post_content LIKE '%[chained-quiz %]%' AND post_title!=''
		AND post_status='publish' ORDER BY post_date DESC");	
		
		// match posts to exams
		foreach($quizzes as $cnt=>$quiz) {
			foreach($posts as $post) {
				if(strstr($post->post_content,"[chained-quiz ".$quiz->id."]")) {
					$quizzes[$cnt]->post=$post;			
					break;
				}
			}
		}
		include(CHAINED_PATH."/views/chained-quizzes.html.php");
	} // end list_quizzes	
	
	// displays a quiz
	static function display($quiz_id) {
	   global $wpdb, $user_ID, $post;
	   $_question = new ChainedQuizQuestion();
	   
	   // select the quiz
	   $quiz = $wpdb -> get_row($wpdb->prepare("SELECT * FROM ".CHAINED_QUIZZES." WHERE id=%d", $quiz_id));
	   if(empty($quiz->id)) die(__('Quiz not found', 'chained'));
	   
	   // completion ID already created?
		if(empty($_COOKIE['chained_completion_id'.$quiz->id])) {			
			$wpdb->query( $wpdb->prepare("INSERT INTO ".CHAINED_COMPLETED." SET
		 		quiz_id = %d, datetime = NOW(), ip = %s, user_id = %d",
		 		$quiz->id, chained_user_ip(), $user_ID));
		 	?>
		 		<script type="text/javascript" >
			  	var d = new Date();
				d.setTime(d.getTime() + (24*3600*1000));
				var expires = "expires="+ d.toUTCString();     				
			  	document.cookie = "chained_completion_id<?php echo $quiz->id?>=<?php echo $wpdb->insert_id;?>;" + expires + ";path=/";
			  	</script>
			<?php  		 	
		}
	   
		 // select the first question
		 $question = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".CHAINED_QUESTIONS." WHERE quiz_id=%d
		 	ORDER BY sort_order, id LIMIT 1", $quiz->id));
		 if(empty($question->id)) {
		 	 _e('This quiz has no questions.', 'chained');
		 	 return false;
		 }	
		 
		 // select possible answers
		 $choices = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".CHAINED_CHOICES." 
		 	WHERE quiz_id=%d AND question_id=%d ORDER BY id", $quiz->id, $question->id));
		
		 // text captcha?
        if(!empty($quiz->require_text_captcha)) {	
            $text_captcha_html = ChainedTextCaptcha :: generate( $quiz_id );
            $textcaptca_style = $exam->single_page==1?"":"style='display:none;'";
            $text_captcha_html = "<div id='ChainedTextCaptcha' $textcaptca_style>".$text_captcha_html."</div>";	
        }
		 			 	
		 $first_load = true;			 	
		 $ui = get_option('chained_ui');
		 include(CHAINED_PATH."/views/display-quiz.html.php");
	}

	// answer a question or complete the quiz
	static function answer_question() {
		global $wpdb, $user_ID;
		$_quiz = new ChainedQuizQuiz();
		$_question = new ChainedQuizQuestion();
		
		$post = get_post($_POST['post_id']);
		
		// select quiz
		$quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".CHAINED_QUIZZES." WHERE id=%d", intval($_POST['quiz_id'])));
		
		 // text captcha?
        if(!empty($quiz->require_text_captcha)) {	
            // verify captcha
            if(!empty($_POST['chained_text_captcha_answer'])) {
                if(!ChainedTextCaptcha :: verify($_POST['chained_text_captcha_question'], $_POST['chained_text_captcha_answer'])) die('CHAINED_CAPTCHA:::'.__('Wrong answer to the verification question.', 'chained'));	
            }
        }
		
		// select question
		$question = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".CHAINED_QUESTIONS." WHERE id=%d", intval($_POST['question_id'])));
		
		// prepare $answer var		
		$answer = ($question->qtype == 'checkbox') ? @$_POST['answers'] : @$_POST['answer'];		
		if(empty($answer)) $answer = 0;
		$answer = esc_sql($answer);
						
		// calculate points
		$points = $_question->calculate_points($question, $answer);
		echo $points."|CHAINEDQUIZ|";
		
		// figure out next question
		$next_question = $_question->next($question, $answer);
		
		// store the answer
		if(!empty($_COOKIE['chained_completion_id'.$quiz->id])) {			
			if(is_array($answer)) {
				$answer = chained_int_array($answer);
				$answer = implode(",", $answer);
			}
			
			$comments = empty($_POST['comments']) ? '' : sanitize_text_field($_POST['comments']);

			// make sure to avoid duplicates and only update the answer if it already exists
			$exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM ".CHAINED_USER_ANSWERS."
				WHERE quiz_id=%d AND completion_id=%d AND question_id=%d", 
				$quiz->id, intval($_COOKIE['chained_completion_id'.$quiz->id]), $question->id));		
			
			$choice_choice = $choice_correct = '';
			
			// if the question is radio, find $choice_choice and $choice_correct
			if(is_numeric($answer)) {
				$choice = $wpdb->get_row($wpdb->prepare("SELECT choice, is_correct FROM ".CHAINED_CHOICES." 
					WHERE question_id=%d AND id=%d", $question->id, intval($answer)));
				$choice_choice = $choice->choice;
				$choice_correct = $choice->is_correct;
			}
			
			$answer_obj = (object)array('answer' => $answer, 'choice' => $choice_choice, 'choice_correct' => $choice_correct, 
				'question_id' => $question->id, 'qtype' => $question->qtype);	
			list($is_correct, $user_answer, $correct_var) = ChainedQuizQuestion :: calc_answer($answer_obj);		
			
			if($exists) {
				$wpdb->query($wpdb->prepare("UPDATE ".CHAINED_USER_ANSWERS." SET
					answer=%s, points=%f, comments=%s, is_correct=%d 
					WHERE quiz_id=%d AND completion_id=%d AND question_id=%d", 
					$answer, $points, $comments, $correct_var, $quiz->id, intval($_COOKIE['chained_completion_id'.$quiz->id]), $question->id));
			}
			else {				
				$wpdb->query($wpdb->prepare("INSERT INTO ".CHAINED_USER_ANSWERS." SET
					quiz_id=%d, completion_id=%d, question_id=%d, answer=%s, points=%f, comments=%s, is_correct=%d",
					$quiz->id, intval($_COOKIE['chained_completion_id'.$quiz->id]), $question->id, $answer, $points, $comments, $correct_var));
			}		
			
			// update the "completed" record as non empty
			$wpdb->query($wpdb->prepare("UPDATE ".CHAINED_COMPLETED." SET not_empty=1 WHERE id=%d", intval($_COOKIE['chained_completion_id'.$quiz->id])));
		}
		
		if(!empty($next_question->id)) {
			$question = $next_question;
			$choices = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".CHAINED_CHOICES." 
		 	WHERE quiz_id=%d AND question_id=%d ORDER BY id", $quiz->id, $question->id));
		 	$ui = get_option('chained_ui');
			include(CHAINED_PATH."/views/display-quiz.html.php");
		}
		else {
			 // add to points
			 $points += floatval($_POST['points']);
			 echo $_quiz->finalize($quiz, $points); // if none, submit the quiz
		}	 		
	}
}
