'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	$ = require('jquery'),
	Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
	WindowOpener = require('%PathToCoreWebclientModule%/js/WindowOpener.js'),
	UrlUtils = require('%PathToCoreWebclientModule%/js/utils/Url.js'),
	Screens = require('%PathToCoreWebclientModule%/js/Screens.js'),	
	
	ModulesManager = require('%PathToCoreWebclientModule%/js/ModulesManager.js'),
	CAbstractSettingsFormView = ModulesManager.run('SettingsWebclient', 'getAbstractSettingsFormViewClass'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
* @constructor
*/
function CUserSettingsView()
{
	CAbstractSettingsFormView.call(this, Settings.ServerModuleName);
	
	this.connected = ko.observable(Settings.Connected);
	this.bRunCallback = false;

	window.dropboxConnectCallback = _.bind(function (bResult, sMessage, sModule) {
		this.bRunCallback = true;
		
		if (!bResult) 
		{
			Screens.showError(sMessage);
		}
		else
		{
			this.connected(true);
		}
	}, this);				

}

_.extendOwn(CUserSettingsView.prototype, CAbstractSettingsFormView.prototype);

CUserSettingsView.prototype.ViewTemplate = '%ModuleName%_UserSettingsView';

CUserSettingsView.prototype.connect = function ()
{
	$.cookie('oauth-redirect', 'connect');
	var 
		self = this,
		oWin = WindowOpener.open(UrlUtils.getAppPath() + '?oauth=dropbox', 'Dropbox'),
		intervalID = setInterval(
			function() { 
				if (oWin.closed)
				{
					if (!self.bRunCallback)
					{
						window.location.reload();
					}
					else
					{
						clearInterval(intervalID);
					}
				}
			}, 1000
		)
	;
};

CUserSettingsView.prototype.disconnect = function ()
{
	Ajax.send(
		Settings.ServerModuleName, 
		'DeleteAccount', 
		null, 
		function (oResponse) {
			if (oResponse.Result)
			{
				this.connected(false);			
			}
			else
			{
				Screens.showError('error');
			}
		}, 
		this
	);
};

module.exports = new CUserSettingsView();
