<?php

bpBase::loadSysClass('model', '', 0);
class cashier_bank_model extends model
{
	public function __construct()
	{
		$this->table_name = 'cashier_bank';
		parent::__construct();
	}
}


?>