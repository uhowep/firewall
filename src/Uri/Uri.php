<?php 

namespace Uhowep\Firewall\Uri;


class Uri
{

	// ['interface' => 'group']
	protected $interfaceGroupRelation = [];

	// config of interface group
	protected $interfaceGroups = [];


	public function __construct()
	{
		$this->interfaceGroups = config('firewall.interface_group');
		if (!empty($this->interfaceGroups)) {
			$this->parseRelation();
		}
	}


	/**
	 * transfer interface to group
	 *
	 * @param string $interface
	 *
	 * @return string $group
	 */
	public function transferInterfaceToGroup(string $interface)
	{
		return $this->interfaceGroupRelation[$interface] ?? null;
	}


	/**
	 * get interface group config 
	 *
	 * @param string $group
	 *
	 * @return array $interfaceGroups
	 */
	public function getInterfaceGroup(string $group='')
	{
		return $this->interfaceGroups[$group] ?? null;
	}


	/**
	 * get interface relate group
	 * 
	 * @return array $groups
	 */
	public function getInterfaceGroupRelation(string $interface='')
	{
		return $this->interfaceGroupRelation[$interface] ?? null;
	}


	/**
	 * parse interface relate with group into interface to group array
	 *
	 * @return void
	 */
	protected function parseRelation()
	{
		foreach ($this->interfaceGroups as $group => $value) {
			if ( isset($value['interface'])==false ) {
				continue;
			}
            foreach ($value['interface'] as $interface) {
                $this->interfaceGroupRelation[$interface] = $group;
            }
        }
	}



}