<?php

namespace Fuel\Migrations;

class Create_questions
{
	public function up()
	{
		\DBUtil::create_table('questions', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'question' => array('constraint' => 500, 'type' => 'varchar'),
			'section_id' => array('constraint' => 11, 'type' => 'int'),
			'type' => array('constraint' => '"RADIO","SELECT"', 'type' => 'enum'),
			'created_at' => array('constraint' => 11, 'type' => 'int'),
			'updated_at' => array('constraint' => 11, 'type' => 'int'),
		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('questions');
	}
}