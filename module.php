<?php

class DropBoxAuthWebclientModule extends AApiModule
{
	protected $sService = 'dropbox';
	
	protected $aSettingsMap = array(
		'EnableModule' => array(false, 'bool'),
		'Id' => array('', 'string'),
		'Secret' => array('', 'string')
	);
	
	protected $aRequireModules = array(
		'OAuthIntegratorWebclient', 'GoogleAuthWebclient'
	);
	
	public function init() 
	{
		$this->incClass('connector');
		$this->subscribeEvent('OAuthIntegratorAction', array($this, 'onOAuthIntegratorAction'));
		$this->subscribeEvent('GetServices', array($this, 'onGetServices'));
		$this->subscribeEvent('GetServicesSettings', array($this, 'onGetServicesSettings'));
		$this->subscribeEvent('UpdateServicesSettings', array($this, 'onUpdateServicesSettings'));
	}
	
	/**
	 * Adds service name to array passed by reference.
	 * 
	 * @param array $aServices Array with services names passed by reference.
	 */
	public function onGetServices(&$aServices)
	{
		if ($this->getConfig('EnableModule', false))
		{
			$aServices[] = $this->sService;
		}
	}
	
	/**
	 * Adds service settings to array passed by reference.
	 * 
	 * @param array $aServices Array with services settings passed by reference.
	 */
	public function onGetServicesSettings(&$aServices)
	{
		$aSettings = $this->GetAppData();
		if (!empty($aSettings))
		{
			$aServices[] = $aSettings;
		}
	}
	
	/**
	 * Returns module settings.
	 * 
	 * @return array
	 */
	public function GetAppData()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		$oUser = \CApi::getAuthenticatedUser();
		if (!empty($oUser) && $oUser->Role === \EUserRole::SuperAdmin)
		{
			return array(
				'Name' => $this->sService,
				'DisplayName' => $this->GetName(),
				'EnableModule' => $this->getConfig('EnableModule', false),
				'Id' => $this->getConfig('Id', ''),
				'Secret' => $this->getConfig('Secret', '')
			);
		}
		
		if (!empty($oUser) && $oUser->Role === \EUserRole::NormalUser)
		{
			$oAccount = null;
			$oOAuthIntegratorWebclientDecorator = \CApi::GetModuleDecorator('OAuthIntegratorWebclient');
			if ($oOAuthIntegratorWebclientDecorator)
			{
				$oAccount = $oOAuthIntegratorWebclientDecorator->GetAccount($this->sService);
			}
			return array(
				'Connected' => $oAccount ? true : false
			);

		}
		
		return array();
	}
	
	/**
	 * Updates service settings.
	 * 
	 * @param array $aServices Array with new values for service settings.
	 * 
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function onUpdateServicesSettings($aServices)
	{
		$aSettings = $aServices[$this->sService];
		
		if (is_array($aSettings))
		{
			$this->UpdateSettings($aSettings['EnableModule'], $aSettings['Id'], $aSettings['Secret']);
		}
	}
	
	/**
	 * Updates service settings.
	 * 
	 * @param boolean $EnableModule
	 * @param string $Id
	 * @param string $Secret
	 * 
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function UpdateSettings($EnableModule, $Id, $Secret)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::TenantAdmin);
		
		try
		{
			$this->setConfig('EnableModule', $EnableModule);
			$this->setConfig('Id', $Id);
			$this->setConfig('Secret', $Secret);
			$this->saveModuleConfig();
		}
		catch (Exception $ex)
		{
			throw new \System\Exceptions\AuroraApiException(\System\Notifications::CanNotSaveSettings);
		}
		
		return true;
	}
	
	public function onOAuthIntegratorAction($sService, &$mResult)
	{
		if ($sService === $this->sService)
		{
			$mResult = false;
			$oConnector = new COAuthIntegratorConnectorDropbox($this);
			if ($oConnector)
			{
				$mResult = $oConnector->Init();
			}
		}
	}
	
	public function DeleteAccount()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$bResult = false;
		$oOAuthIntegratorWebclientDecorator = \CApi::GetModuleDecorator('OAuthIntegratorWebclient');
		if ($oOAuthIntegratorWebclientDecorator)
		{
			$bResult = $oOAuthIntegratorWebclientDecorator->DeleteAccount($this->sService);
		}		
		
		return $bResult;
	}		
}
