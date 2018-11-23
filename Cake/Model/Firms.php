<?php
class Firms extends AppModel {
    public $tablePrefix = '';
    public $useTable = 'firms';

    public function findHasCases($type = null, $conditions = null) {
        $hasMany = array(
            'FirmCases' => array(
                'type'       => 'inner',
                'className'  => 'FirmCases',
                'foreignKey' => 'firm_id',
                'limit'      => 1,
            ),
        );
        $this->bindModel(array('hasMany' => $hasMany));
        return $this->find($type, $conditions);
    }
}
