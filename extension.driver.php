<?php
Class extension_statusfield extends Extension
{
	// About this extension:
	public function about()
	{
		return array(
			'name' => 'Field: Status',
			'version' => '0.1',
			'release-date' => '2010-10-20',
			'author' => array(
				'name' => 'Giel Berkers',
				'website' => 'http://www.gielberkers.com',
				'email' => 'info@gielberkers.com'),
			'description' => 'Store the status and hold a history of previous statuses.'
		);
	}
	
	// Set the delegates:
	public function getSubscribedDelegates()
	{
		return array(
			array(
				'page' => '/backend/',
				'delegate' => 'InitaliseAdminPageHead',
				'callback' => 'initialiseHead'
			),
		);
	}
	
	public function initialiseHead($context)
	{
		$page = $context['parent']->Page;
		if ($page instanceof ContentPublish && in_array($page->_context['page'], array('new', 'edit')))
		{
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/statusfield/assets/statusfield.publish.js', 101, false);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/statusfield/assets/statusfield.publish.css', 'screen', 101, false);
		}
	}
	
	public function uninstall()
	{
		Symphony::Database()->query("DROP TABLE `tbl_fields_status`");
		Symphony::Database()->query("DROP TABLE `tbl_fields_status_statuses`");
	}

	public function install()
	{
		Symphony::Database()->query("CREATE TABLE `tbl_fields_status` (
			`id` int(11) unsigned NOT NULL auto_increment,
			`field_id` int(11) unsigned NOT NULL,
			`options` TEXT default NULL,
			`valid_until` TINYTEXT default NULL,
			PRIMARY KEY  (`id`),
			UNIQUE KEY `field_id` (`field_id`)
		)");
		Symphony::Database()->query("CREATE TABLE `tbl_fields_status_statuses` (
			`id` int(11) unsigned NOT NULL auto_increment,
			`field_id` int(11) unsigned NOT NULL,
			`entry_id` int(11) unsigned NOT NULL,
			`date` DATE,
			`status` TEXT,
			`valid_until` DATE,
			PRIMARY KEY  (`id`)
		)");
	}
	
	public function update()
	{
		try{
			if(version_compare($previousVersion, '0.2', '<')){
				Symphony::Database()->query(
					"RENAME `tbl_fields_status_statusses` TO `tbl_fields_status_statuses`"
				);
			}
		}
		catch(Exception $e){
			
		}
	}
}
