<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\DropboxAuthWebclient\Classes;


/**
 *
 * @package Classes
 * @subpackage AuthIntegrator
 */
class Connector extends \Aurora\Modules\OAuthIntegratorWebclient\Classes\Connector
{
	protected $Name = 'dropbox';
	
	public function CreateClient($sId, $sSecret, $sScope)
	{
		$sRedirectUrl = \rtrim(\MailSo\Base\Http::SingletonInstance()->GetFullUrl(), '\\/ ').'/?oauth=' . $this->Name;
		if (!\strpos($sRedirectUrl, '://localhost'))
		{
			$sRedirectUrl = \str_replace('http:', 'https:', $sRedirectUrl);
		}

		$oClient = new \oauth_client_class;
		$oClient->debug = self::$Debug;
		$oClient->debug_http = self::$Debug;
		$oClient->server = 'Dropbox2';
		$oClient->redirect_uri = $sRedirectUrl;
		$oClient->client_id = $sId;
		$oClient->client_secret = $sSecret;
		$oOAuthIntegratorWebclientModule = \Aurora\System\Api::GetModule('OAuthIntegratorWebclient');
		if ($oOAuthIntegratorWebclientModule)
		{
			$oClient->configuration_file = $oOAuthIntegratorWebclientModule->GetPath() .'/classes/OAuthClient/'.$oClient->configuration_file;
		}
		
		return $oClient;
	}
	
	public function Init($sId, $sSecret, $sScope = '')
	{
		$mResult = false;

		$oClient = $this->CreateClient($sId, $sSecret, $sScope);
				
		if($oClient)
		{
			$oUser = null;
			if(($success = $oClient->Initialize()))
			{
				if(($success = $oClient->Process()))
				{
					if(\strlen($oClient->access_token))
					{
						$success = $oClient->CallAPI(
							'https://api.dropbox.com/1/account/info', 
							'GET', 
							array(), 
							array(
								'FailOnAccessError' => true
							), 
							$oUser
						);
					}
				}
				$success = $oClient->Finalize($success);
			}

			if($oClient->exit)
			{
				exit;
			}

			if($success && $oUser)
			{
				$mResult = array(
					'type' => $this->Name,
					'id' => $oUser->uid,
					'name' => $oUser->display_name,
					'email' => isset($oUser->email) ? $oUser->email : '',
					'access_token' => $oClient->access_token,
					'scopes' => \explode('|', $sScope)
						
				);
			}
			else
			{
				$mResult = false;
				$oClient->ResetAccessToken();
			}
		}
		
		return $mResult;
	}
}