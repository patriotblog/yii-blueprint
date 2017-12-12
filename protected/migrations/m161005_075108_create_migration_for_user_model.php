<?php

class m161005_075108_create_migration_for_user_model extends CDbMigration
{
//	public function up()
//	{
//
//	}
//
//	public function down()
//	{
//		echo "m161005_075108_create_migration_for_user_model does not support migration down.\n";
//		return false;
//	}


	// Use safeUp/safeDown to do migration with transaction
	public function safeUp()
	{

        $this->createTable('user', array(
            'id' => 'pk',
//            'device_gg_id' => 'int(11) NOT NULL',
            'email' => 'varchar(64) NOT NULL',
            'username' => 'varchar(16) NOT NULL',
            'password' => 'varchar(255) NOT NULL',
            'created_time' => 'int(11) NOT NULL',
            'updated_time' => 'int(11)',
            'lastlogin' => 'int(11)',
            'phonenumber' => 'varchar(16)',
            'role' => 'int(11)',


        ));

        //$this->addForeignKey("FK_customer_queue_project_customer", "customer_queue", "project_customer_id", "project_customer", "id", "RESTRICT", "RESTRICT");


    }

	public function safeDown()
	{
	    $this->dropTable('user');
	}

}