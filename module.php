<?php

class DropBoxAuthModule extends AApiModule
{
	protected $sService = 'dropbox';
	
	protected $aSettingsMap = array(
		'Id' => array('', 'string'),
		'Secret' => array('', 'string')
	);
	
	protected $aRequireModules = array(
		'ExternalServices', 'GoogleAuth'
	);
	
	public function init() 
	{
		$this->incClass('connector');
		$this->subscribeEvent('ExternalServicesAction', array($this, 'onExternalServicesAction'));
		$this->subscribeEvent('GetServices', array($this, 'onGetServices'));
	}
	
	/**
	 * Adds service name to array passed by reference.
	 * 
	 * @param array $aServices Array with services names passed by reference.
	 */
	public function onGetServices(&$aServices)
	{
		$aServices[] = $this->sService;
	}
	
	public function onExternalServicesAction($sService, &$mResult)
	{
		if ($sService === $this->sService)
		{
			$mResult = false;
			$oConnector = new CExternalServicesConnectorDropbox($this);
			if ($oConnector)
			{
				$mResult = $oConnector->Init();
			}
		}
	}
}
