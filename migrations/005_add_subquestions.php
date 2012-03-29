<?php

namespace Fuel\Migrations;

class Add_subquestions
{
	public function up()
	{
		\DBUtil::add_fields('questions', array(
			'parent_id' => array('constraint' => 11, 'type' => 'int', 'null' => true),
			'parent_value' => array('constraint' => 100, 'type' => 'varchar', 'null' => true),
		));
	}

	public function down()
	{
		\DBUtil::drop_fields('questions', array('parent_id', 'parent_value'));
	}
}