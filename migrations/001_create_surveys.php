<?php

namespace Fuel\Migrations;

class Create_surveys
{
	public function up()
	{
		\DBUtil::create_table('surveys', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'title' => array('constraint' => 100, 'type' => 'varchar'),
			'description' => array('constraint' => 500, 'type' => 'varchar'),
			'created_at' => array('constraint' => 11, 'type' => 'int'),
			'updated_at' => array('constraint' => 11, 'type' => 'int'),
		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('surveys');
	}
}