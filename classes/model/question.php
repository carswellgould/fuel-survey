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
}