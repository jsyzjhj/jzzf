<?php

bpBase::loadSysClass('model', '', 0);
class recognition_model extends model
{
	public function __construct()
	{
		$this->table_name = 'recognition';
		parent::__construct();
	}
}


?>