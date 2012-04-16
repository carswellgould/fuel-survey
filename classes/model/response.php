<?php
namespace Survey;

class Model_Response extends \Orm\Model
{
	protected static $_properties = array('id', 'question_id', 'answer_id', 'user_id');
}