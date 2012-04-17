<?php

namespace Fuel\Migrations;

class Create_responses
{
	public function up()
	{
		\DBUtil::create_table('responses', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'answer_id' => array('constraint' => 255, 'type' => 'varchar'),
			'user_id' => array('constraint' => 11, 'type' => 'int', 'null'=>true, 'default' => \DB::expr('null')),
			'created_at' => array('constraint' => 11, 'type' => 'int'),
			'updated_at' => array('constraint' => 11, 'type' => 'int'),
		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('responses');
	}
}