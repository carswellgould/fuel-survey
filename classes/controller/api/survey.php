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
			$out[] = array(
				'id' => $question->id,
				'question' => $question->question,
				'type' => $question->type,
				'answers' => $answers
			);
		}

		$this->response($out);
	}
}