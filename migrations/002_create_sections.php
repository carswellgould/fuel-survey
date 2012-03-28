<?php

namespace Fuel\Migrations;

class Create_sections
{
	public function up()
	{
		\DBUtil::create_table('sections', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true),
			'title' => array('type' => 'varchar', 'constraint' => 100),
			'description' => array('constraint' => 500, 'type' => 'varchar'),
			'position' => array('constraint' => 4, 'type' => 'int'),
			'survey_id' => array('constraint' => 11, 'type' => 'int'),
			'created_at' => array('constraint' => 11, 'type' => 'int'),
			'updated_at' => array('constraint' => 11, 'type' => 'int'),
		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('sections');
	}
}