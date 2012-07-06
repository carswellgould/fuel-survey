<?php
namespace Survey;

class Controller_Api_Survey extends \Controller_Rest {
	public function get_subquestions()
	{
		$answer_id = \Input::param('answer');
		$parent_id = \Input::param('parent');

		$questions = Model_Question::find()
						->related('answers')
						->where('parent_id', $parent_id)
						->where('parent_value', $answer_id)
						->get();

		if (count($questions))
		{
			$survey_id = current($questions)->get_survey_id();

			//you/they stuff
			//not meant to be in here but in here anyway.
			if($survey_id == 1)
			{
				$use_you = \Session::get('survey.use_you');
			}


			$prev_shown = \Session::get('survey.'.$survey_id.'.questions_shown', array());
			$prev_shown = $prev_shown ?: array();



			$questions_added = array();
			foreach ($questions as $q)
			{
				$questions_added[] = $q->id;
			}

			\Session::set(
				'survey.'.$survey_id.'.questions_shown',
				array_merge($prev_shown, $questions_added)
			);
		}
		reset($questions);


		$out = array();

		foreach ($questions as $question)
		{
			$answers = array();

			foreach ($question->answers as $answer) {
				$answers[] = array(
					'id' => $answer->id,
					'answer' => $answer->answer,
					'value' => $answer->value
				);
			}

			//you/they stuff
			//not meant to be in here but in here anyway
			$filtered_question = $question->question;
			if (isset($use_you))
			{
				if($use_you)
				{
					$filtered_question = str_replace("your/their", "your", $filtered_question);
					$filtered_question = str_replace("you/them", "you", $filtered_question);
					$filtered_question = str_replace("you/they", "you", $filtered_question);
					$filtered_question = str_replace("yourself/themselves", "yourself", $filtered_question);
					$filtered_question = str_replace("their/your", "their", $filtered_question);
					$filtered_question = str_replace("them/you", "them", $filtered_question);
					$filtered_question = str_replace("they/you", "they", $filtered_question);
					$filtered_question = str_replace("themselves/yourself", "themselves", $filtered_question);

				}
				else
				{
					$filtered_question = str_replace("your/their", "their", $filtered_question);
					$filtered_question = str_replace("you/them", "them", $filtered_question);
					$filtered_question = str_replace("you/they", "they", $filtered_question);
					$filtered_question = str_replace("yourself/themselves", "themselves", $filtered_question);
					$filtered_question = str_replace("their/you", "you", $filtered_question);
					$filtered_question = str_replace("them/you", "you", $filtered_question);
					$filtered_question = str_replace("they/you", "you", $filtered_question);
					$filtered_question = str_replace("themselves/yourself", "yourself", $filtered_question);

				}
			}

			$out[] = array(
				'id' => $question->id,
				'question' => $filtered_question,
				'type' => $question->type,
				'answers' => $answers
			);
		}

		$this->response($out);
	}
}