<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\DropboxAuthWebclient\Classes;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @package Classes
 * @subpackage AuthIntegrator
 */
class Connector extends \Aurora\Modules\OAuthIntegratorWebclient\Classes\Connector
{
    protected $Name = 'dropbox';

    public function CreateClient($sId, $sSecret, $sScope)
    {
        if (empty($sId) || empty($sSecret)) {
            throw new \Aurora\Modules\OAuthIntegratorWebclient\Exceptions\NotConfigured(
                \Aurora\Modules\OAuthIntegratorWebclient\Enums\ErrorCodes::NotConfigured
            );
        }

        $sRedirectUrl = \rtrim(\MailSo\Base\Http::SingletonInstance()->GetFullUrl(), '\\/ ') . '/?oauth=' . $this->Name;
        if (!\strpos($sRedirectUrl, '://localhost')) {
            $sRedirectUrl = \str_replace('http:', 'https:', $sRedirectUrl);
        }

        $oClient = new \oauth_client_class();
        $oClient->debug = self::$Debug;
        $oClient->debug_http = self::$Debug;
        $oClient->server = 'Dropbox2v2';
        $oClient->redirect_uri = $sRedirectUrl;
        $oClient->client_id = $sId;
        $oClient->client_secret = $sSecret;
        $oOAuthIntegratorWebclientModule = \Aurora\System\Api::GetModule('OAuthIntegratorWebclient');
        if ($oOAuthIntegratorWebclientModule) {
            $oClient->configuration_file = $oOAuthIntegratorWebclientModule->GetPath() . '/Classes/OAuthClient/' . $oClient->configuration_file;
        }

        return $oClient;
    }

    public function Init($sId, $sSecret, $sScope = '')
    {
        $mResult = false;

        $oClient = $this->CreateClient($sId, $sSecret, $sScope);

        if ($oClient) {
            $oUser = null;
            if (($success = $oClient->Initialize())) {
                if (($success = $oClient->Process())) {
                    if (\strlen($oClient->access_token)) {
                        $success = $oClient->CallAPI(
                            'https://api.dropbox.com/2/users/get_current_account',
                            'POST',
                            [],
                            array(
                                'FailOnAccessError' => true,
                                'RequestBody' => '',
                                'RequestContentType' => ''
                            ),
                            $oUser
                        );
                    }
                }
                $success = $oClient->Finalize($success);
            }

            if ($oClient->exit) {
                exit;
            }

            $oTokenData = new \stdClass();
            $oTokenData->access_token = $oClient->access_token;
            $oTokenData->created = time();

            if (!empty($oClient->access_token_expiry)) {
                $date = new \DateTime($oClient->access_token_expiry);
                $date->setTimezone(new \DateTimeZone('GMT'));

                $now = new \DateTime();
                $now->setTimezone(new \DateTimeZone('GMT'));

                $oTokenData->expires_in = $date->getTimestamp() - $now->getTimestamp();
            }

            if ($success && $oUser) {
                $mResult = array(
                    'type' => $this->Name,
                    'id' => $oUser->account_id,
                    'name' => $oUser->name->display_name,
                    'email' => isset($oUser->email) ? $oUser->email : '',
                    'access_token' => \json_encode($oTokenData),
                    'refresh_token' => $oClient->refresh_token,
                    'scopes' => \explode('|', $sScope)
                );
            } else {
                $mResult = false;
                $oClient->ResetAccessToken();
            }
        }

        return $mResult;
    }
}
