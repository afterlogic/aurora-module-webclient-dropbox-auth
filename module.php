<?php

class DropBoxAuthModule extends AApiModule
{
	public $oApiSocialManager = null;
	
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
		$this->oApiSocialManager = $this->GetManager('social');
		$this->includeTemplate('BasicAuthClient_LoginView', 'Login-After', 'templates/button.html');
		$this->subscribeEvent('ExternalServicesAction', array($this, 'onExternalServicesAction'));
	}
	
	public function onExternalServicesAction($sService, &$mResult)
	{
		if ($sService === 'dropbox')
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
