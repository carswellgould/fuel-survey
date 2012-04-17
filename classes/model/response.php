<?php
namespace Survey;

class Model_Response extends \Orm\Model
{
	protected static $_properties = array('id', 'answer_id', 'user_id', 'created_at', 'updated_at');

	protected static $_observers = array(
			'Orm\Observer_CreatedAt' => array('events' => array('before_insert')),
			'Orm\Observer_UpdatedAt' => array('events' => array('before_save')),
	);
}