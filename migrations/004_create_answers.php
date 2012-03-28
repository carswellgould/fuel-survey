<?php

namespace Fuel\Migrations;

class Create_answers
{
	public function up()
	{
		\DBUtil::create_table('answers', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'answer' => array('constraint' => 255, 'type' => 'varchar'),
			'question_id' => array('constraint' => 11, 'type' => 'int'),
			'value' => array('constraint' => 100, 'type' => 'varchar'),
			'created_at' => array('constraint' => 11, 'type' => 'int'),
			'updated_at' => array('constraint' => 11, 'type' => 'int'),
		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('answers');
	}
}