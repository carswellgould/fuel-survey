<?php

namespace Survey;

class Model_Question extends \Orm\Model
{

	protected static $_properties = array(
			'id',
			'question',
			'type',
			'section_id',
			'parent_id',
			'parent_value'
	);

	protected static $_has_many = array(
			'answers',
			'subquestions' => array(
					'key_from' => 'id',
					'model_to' => 'Survey\\Model_Question',
					'key_to' => 'parent_id',
			),
	);

	public $value = null;

	/**
	 * Finds the survey this question is a part of and returns it
	 */
	public function get_survey_id()
	{
		$id = \DB::query('
			SELECT sections.survey_id
			FROM
				questions INNER JOIN sections
				ON (questions.section_id=sections.id)
			WHERE questions.id=' . \DB::escape($this->id)
		)->execute()->as_array();
		$id = current($id);
		$id = (int)$id['survey_id'];

		return $id;

	}
}