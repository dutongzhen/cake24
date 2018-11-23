<?php
class PublicTel extends AppModel {
	public $useDbConfig = 'default';
	public $useTable = 'public_tel';
	public $primaryKey = 'id';
	
	public function findAsMember($type, $conditions) {
		$hasOne = array(
				'Member' => array(
						'type'       => 'right',
						'className'  => 'Member',
						'foreignkey' => 'public_tel_id',
				)			
		);
		$this->bindModel(array('hasOne' => $hasOne));
		return $this->find($type, $conditions);
	}	
}