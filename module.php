<?php
/**
 * @copyright Copyright (c) 2016, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 * @package Modules
 */

class DropboxAuthWebclientModule extends AApiModule
{
	protected $sService = 'dropbox';
	
	protected $aRequireModules = array(
		'OAuthIntegratorWebclient', 
		'Dropbox'
	);
	
	protected function issetScope($sScope)
	{
		return in_array($sScope, explode(' ', $this->getConfig('Scopes')));
	}

	
	/***** private functions *****/
	/**
	 * Initializes DropBoxAuthWebclient Module.
	 * 
	 * @ignore
	 */
	public function init()
	{
		$this->incClass('connector');
		$this->subscribeEvent('OAuthIntegratorWebclient::GetServices::after', array($this, 'onAfterGetServices'));
		$this->subscribeEvent('OAuthIntegratorAction', array($this, 'onOAuthIntegratorAction'));
		$this->subscribeEvent('Dropbox::GetSettings', array($this, 'onGetSettings'));
	}
	
	/**
	 * Adds service name to array passed by reference.
	 * 
	 * @ignore
	 * @param array $aServices Array with services names passed by reference.
	 */
	public function onAfterGetServices($aArgs, &$aServices)
	{
		$oeModule = \CApi::GetModule('Dropbox'); 
		
		if ($oeModule->getConfig('EnableModule', false) &&
			!empty($oeModule->getConfig('Id', '')) && !empty($oeModule->getConfig('Secret', '')))
		{
			$aServices[] = $this->sService;
		}
	}	
	
	/**
	 * Passes data to connect to service.
	 * 
	 * @ignore
	 * @param string $aArgs Service type to verify if data should be passed.
	 * @param boolean|array $mResult variable passed by reference to take the result.
	 */
	public function onOAuthIntegratorAction($aArgs, &$mResult)
	{
		if ($aArgs['Service'] === $this->sService)
		{
			$aScopes = $_COOKIE['oauth-scopes'];
			$mResult = false;
			$oConnector = new COAuthIntegratorConnectorDropbox($this, $this->sService);
			if ($oConnector)
			{
				$mResult = $oConnector->Init(
					\CApi::GetModule('Dropbox')->getConfig('Id'), 
					\CApi::GetModule('Dropbox')->getConfig('Secret'),
					$aScopes
				);
			}
			
			return true;
		}
	}
	
	/**
	 * Passes data to connect to service.
	 * 
	 * @ignore
	 * @param string $aArgs Service type to verify if data should be passed.
	 * @param boolean|array $mResult variable passed by reference to take the result.
	 */
	public function onGetSettings($aArgs, &$mResult)
	{
		$iUserId = \CApi::getAuthenticatedUserId();
		
		$aScope = array(
			'Name' => 'auth',
			'Description' => $this->i18N('SCOPE_AUTH', $iUserId),
			'Value' => false
		);
		if ($aArgs['OAuthAccount'] instanceof \COAuthAccount)
		{
			$aScope['Value'] = $aArgs['OAuthAccount']->issetScope('auth');
		}
		$mResult['Scopes'][] = $aScope;
	}		
}
