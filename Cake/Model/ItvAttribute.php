<?php

class ItvAttribute extends AppModel {
    //public $useDbConfig = 'youjk';
    public $tablePrefix = '';
    public $useTable = "itv_attribute";
    public $primaryKey = "id";

    public function findAsFirms($type = null, $conditions = null) 
    {
        $this->virtualFields = array(
          'itv_balance' => '(select itv_balance from firms where id = ItvAttribute.firm_id)',

        );
        return $this->find($type, $conditions);
    }
}
