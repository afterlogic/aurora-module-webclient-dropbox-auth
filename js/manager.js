'use strict';

module.exports = function (oAppData, iUserRole, bPublic) {
	var
		_ = require('underscore'),
		
		TextUtils = require('modules/CoreClient/js/utils/Text.js'),
				
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		oSettings = _.extend({}, oAppData[Settings.ServerModuleName] || {}, oAppData['%ModuleName%'] || {}),
		
		bAdminUser = iUserRole === Enums.UserRole.SuperAdmin
	;

	Settings.init(oSettings);
	
	return {
		start: function (ModulesManager) {
			if (bAdminUser)
			{
				ModulesManager.run('AdminPanelClient', 'registerAdminPanelTab', [
					function () { return require('modules/%ModuleName%/js/views/AdminSettingsView.js'); },
					Settings.HashModuleName,
					TextUtils.i18n('%MODULENAME%/LABEL_SETTINGS_TAB')
				]);
			}
		}
	};
};
