<?php

Class fieldStatus extends Field
{
	function __construct(&$parent){
		parent::__construct($parent);
		$this->_name = __('Status');
		
		// Set default
		$this->set('show_column', 'yes');			
	}
	
	function canToggle(){
		return true;
	}
	
	function allowDatasourceOutputGrouping(){
		return true;
	}
	
	function allowDatasourceParamOutput(){
		return true;
	}		
	
	function canFilter(){
		return true;
	}
	
	public function canImport(){
		return true;
	}
	
	function canPrePopulate(){
		return true;
	}	

	function isSortable(){
		return true;
	}
	
	
	// Show the settings panel (at the sections screen):
	public function displaySettingsPanel(&$wrapper, $errors = null) {
		parent::displaySettingsPanel($wrapper, $errors);
		
		$options = array();
		$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
		
		$label = Widget::Label(__('Statuses'));
		$input = Widget::Input('fields['.$this->get('sortorder').'][options]', General::sanitize($this->get('options')));
		$label->appendChild($input);
		$wrapper->appendChild($label);
		
		$label = Widget::Label();
		$label->setAttribute('class', 'meta');
		$input = Widget::Input('fields['.$this->get('sortorder').'][valid_until]', 'yes', 'checkbox');
		if ($this->get('valid_until') == 'yes') $input->setAttribute('checked', 'checked');
		
		$label->setValue(__('%s Show \'valid until\'', array($input->generate())));
		$wrapper->appendChild($label);
		
		$this->appendShowColumnCheckbox($wrapper);
	}
	
	
	// Store the field (at the sections screen):
	function commit(){
		if(!parent::commit()) return false;
		
		$id = $this->get('id');
		if($id === false) return false;
		
		$fields = array();
		
		$fields['field_id'] = $id;
		if($this->get('options') != '') $fields['options'] = $this->get('options');
		if($this->get('valid_until') != '') $fields['valid_until'] = $this->get('valid_until');

		$this->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
		if(!$this->Database->insert($fields, 'tbl_fields_' . $this->handle())) return false;
		
		return true;
	}
	
	
	// Show the publish panel:
	function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL, $entry_id=NULL){
		// Get the toggle states:
		$states = $this->getToggleStates();
		natsort($states);
		if(!is_array($data['value'])) $data['value'] = array($data['value']);
		
		$options = array();
		$options[] = array(0, true, __('Set new status...'));
		
		foreach($states as $handle => $v){
			$options[] = array(General::sanitize($v), false, $v);
		}
		
		$label = Widget::Label($this->get('label'));
		$table = new XMLElement('table', null, array('class'=>'status'));
		
		// Show the headers:
		$row = new XMLElement('tr');
		$row->appendChild(new XMLElement('th', __('Date'), array('class'=>'date')));
		$row->appendChild(new XMLElement('th', __('Status')));
		if($this->get('valid_until') == 'yes')
		{
			$row->appendChild(new XMLElement('th', __('Valid Until'), array('class'=>'date')));
		}
		
		$table->appendChild($row);
		
		// Show the different states:
		$fieldId = $this->get('id');
		
		if($entry_id != NULL)
		{
			$results = Symphony::Database()->fetch('SELECT `date`, `status`, `valid_until` FROM `tbl_fields_status_statuses` WHERE `field_id` = '.$fieldId.' AND `entry_id` = '.$entry_id.' ORDER BY `date`, `id`;');
			foreach($results as $result)
			{
				$row = new XMLElement('tr');
				$row->appendChild(new XMLElement('td', DateTimeObj::get('d F Y', $result['date'])));
				$row->appendChild(new XMLElement('td', $result['status']));
				if($this->get('valid_until') == 'yes')
				{
					if($result['valid_until'] == null)
					{
						$valid_until = '-';
					} else {
						$valid_until = DateTimeObj::get('d F Y', $result['valid_until']);
					}
					$row->appendChild(new XMLElement('td', $valid_until));
				}
				$table->appendChild($row);
			}
		}
		
		// Show the footer (option to set new status):
		$row = new XMLElement('tr', null, array('class'=>'footer'));
		$row->appendChild(new XMLElement('td', DateTimeObj::get('d F Y'), array('class' => 'inactive')));
		$td = new XMLElement('td', null);
		if($this->get('valid_until') == 'yes')
		{
			$td->setAttribute('colspan', 2);
		}
		$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
		$td->appendChild(Widget::Select($fieldname, $options));
		$row->appendChild($td);
		$table->appendChild($row);
		
		if($this->get('valid_until') == 'yes')
		{
			$row = new XMLElement('tr', null, array('class'=>'valid'));
			$row->appendChild(new XMLElement('td', __('Valid Until')));
			$td = new XMLElement('td', null, array('colspan'=>2));
			$fieldname = 'fields['.$this->get('element_name').'-until]';
			$td->appendChild(Widget::Input($fieldname, DateTimeObj::get('d F Y', strtotime('next year'))));
			$row->appendChild($td);
			$table->appendChild($row);
		}
		
		if($flagWithError != null)
		{
			$wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			$wrapper->appendChild(Widget::wrapFormElementWithError($table, $flagWithError));
		} else {
			$wrapper->appendChild($label);
			$wrapper->appendChild($table);
		}
	}
	
	
	// Store the new status:
	public function processRawFieldData($data, &$status, $simulate=false, $entry_id=null) {	
		// Set the status:			
		$status  = self::__OK__;
		$fieldId = $this->get('id');
		$entryId = $entry_id;
		
		if($data != '0')
		{
			// Store new status:
			$dateNow = date('Y-m-d');
			if(isset($_POST['fields'][$this->get('element_name').'-until']))
			{
				$dateUntil = $_POST['fields'][$this->get('element_name').'-until'];
			} else {
				$dateUntil = '';	
			}
			
			// $statusStr = $_POST['fields'][$this->get('element_name')];
			$statusStr = $data;
			
			if($dateUntil != __('YYYY-MM-DD') && !empty($dateUntil))
			{
				$dateUntil = '\''.DateTimeObj::get('Y-m-d', strtotime($dateUntil)).'\'';
			} else {
				$dateUntil = 'NULL';
			}
			// Don't insert if there is no entry_id:
			if($entry_id != null)
			{
				Symphony::Database()->query('INSERT INTO `tbl_fields_status_statuses`
					(`field_id`, `entry_id`, `date`, `status`, `valid_until`) VALUES
					('.$fieldId.', '.$entryId.', \''.$dateNow.'\', \''.$statusStr.'\', '.$dateUntil.');');
			}
			// Return the new status:
			return array(
				'value' => $statusStr,
			);
		} else {
			// Return the current status:
			if($entry_id != null)
			{
				// There can only be a value returned if there is an entry_id:
				return array(
					'value' => Symphony::Database()->fetchVar('status', 0, 'SELECT `status` FROM `tbl_fields_status_statuses` WHERE `field_id` = '.$fieldId.' AND `entry_id` = '.$entryId.' ORDER BY `date` DESC, `id` DESC;')
				);
			} else {
				// Is this the right way to do this?
				return false;
			}
		}
	}
	
	
	// Datasource output:
	public function appendFormattedElement(&$wrapper, $data, $encode = false) {
		if (!is_array($data) or empty($data)) return;
		
		$list = new XMLElement($this->get('element_name'));
		$attributes = $wrapper->getAttributes();
		$entryId = $attributes['id'];
		$fieldId = $this->get('id');
		$results = Symphony::Database()->fetch('SELECT `date`, `status`, `valid_until` FROM `tbl_fields_status_statuses` WHERE `field_id` = '.$fieldId.' AND `entry_id` = '.$entryId.' ORDER BY `date`, `id`;');
		foreach($results as $result)
		{
			$status = new XMLElement('status', General::sanitize($result['status']), array('date'=>$result['date']));
			if($result['valid_until'] != null) {
				$status->setAttribute('valid-until', $result['valid_until']);
			} 
			$list->appendChild($status);
		}
		$wrapper->appendChild($list);
	}
	
	
	// Delete the entry and the associated statuses:
	public function entryDataCleanup($entry_id, $data=NULL)
	{
		$this->Database->delete('tbl_entries_data_' . $this->get('id'), " `entry_id` = '$entry_id' ");
		$this->Database->delete('tbl_fields_status_statuses', ' `entry_id` = '.$entry_id);
		return true;
	}
	
	
	// Get the values to populate the dropdown-list with:
	public function getToggleStates() {
		$values = preg_split('/,\s*/i', $this->get('options'), -1, PREG_SPLIT_NO_EMPTY);
		
		$values = array_map('trim', $values);
		$states = array();
		
		foreach ($values as $value) {
			$value = $value;
			$states[$value] = $value;
		}
		
		return $states;
	}
	
	
	function displayDatasourceFilterPanel(&$wrapper, $data=NULL, $errors=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
		parent::displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);
		
		$data = preg_split('/,\s*/i', $data);
		$data = array_map('trim', $data);
		
		$existing_options = $this->getToggleStates();
		
		if(is_array($existing_options) && !empty($existing_options)){
			$optionlist = new XMLElement('ul');
			$optionlist->setAttribute('class', 'tags');
			foreach($existing_options as $option) $optionlist->appendChild(new XMLElement('li', $option));
			$wrapper->appendChild($optionlist);
		}
	}
	
	
	// Toggle the field
	function toggleFieldData($data, $newState, $entry_id=NULL)
	{
		$status = ''; // dummy variable
		$data = $this->processRawFieldData($newState, $status, FALSE, $entry_id);
		return $data;
	}
		
}