<?php
namespace Survey;

class Model_Question extends \Orm\Model {

	protected static $_properties = array('id', 'question', 'type', 'section_id');

	protected static $_has_many = array('answers');

	public $value = null;
}