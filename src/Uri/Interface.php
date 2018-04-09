<?php 

namespace Uhowep\Firewall\Uri;


class Interface
{

	// ['interface' => 'group']
	protected $interfaceGroupRelation = [];

	// config of interface group
	protected $interfaceGroups = [];


	public function __construct()
	{
		$this->interfaceGroups = config('firewall.interface_group');
		$this->parseRelation();
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
		return $this->interfaceGroups[$group] ?? $this->interfaceGroups;
	}


	/**
	 * get interface relate group
	 * 
	 * @return array $groups
	 */
	public function getInterfaceGroupRelation(string $interface='')
	{
		return $this->interfaceGroupRelation[$interface] ?? $this->interfaceGroupRelation;
	}


	/**
	 * parse interface relate with group into interface to group array
	 *
	 * @return void
	 */
	protected function parseRelation()
	{
		foreach ($this->interfaceGroups as $group => $value) {
            foreach ($value['interface'] as $interface) {
                $this->interfaceGroupRelation[$interface] = $group;
            }
        }
	}



}