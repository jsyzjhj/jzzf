<?php

bpBase::loadSysClass('model', '', 0);
class cashier_employees_model extends model
{
	public function __construct()
	{
		$this->table_name = 'cashier_employees';
		parent::__construct();
	}
}


?>