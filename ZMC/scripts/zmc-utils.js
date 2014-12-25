 //		Copyright (C) 2006-2013 Zmanda Incorporated.
 //				 All Rights Reserved.
 //
 // The software you have just accessed, its contents, output and underlying
 // programming code are the proprietary and confidential information of Zmanda
 // Incorporated.  Only specially authorized employees, agents or licensees of
 // Zmanda may access and use this software.  If you have not been given
 // specific written permission by Zmanda, any attempt to access, use, decode,
 // modify or otherwise tamper with this software will subject you to civil
 // liability and/or criminal prosecution to the fullest extent of the law.
 // $Id: zmc-utils.js 40320 2014-06-25 10:11:41Z qtran $ */

YAHOO.namespace('zmc.utils');
YAHOO.zmc.utils = {
selected_total : 0,

setEnableStatus : function(o, id, new_state)
{
	if (typeof id === 'object' && id instanceof Array) // then arg1 is a list, and arg2 is desired enable status
	{
		for(var i = 0; i < id.length; i++)
			this.setEnableStatus(o, id[i], new_state)
		return
	}

	//alert('setEnableStatus(id='+id+', id.value='+id.value+', new_state='+new_state+')')
	if (typeof id === 'string')
		id = gebi(id)
	if (!id || !YAHOO.util.Dom.isAncestor(o.parentNode, id))
		return

	var current = true
	if (id.disabled)
		current  = false

	if (new_state)
		id.disabled = false
	else
		id.disabled = 'disabled'

	var name = id.className.replace("Disabled", "")
	if (!new_state)
		name += 'Disabled'
	if (id.className != name)
		id.className = name
},

invert_datatable_checkboxes : function(data_table)
{
	this.enable_datatable_buttons(data_table, 'invert')
},

select_all_datatable_buttons : function(data_table)
{
	this.enable_datatable_buttons(data_table, 'all')
},

select : function(o, option)
{
	if (typeof o !== 'object')
		o=gebi(o)

	if (!o)
		return

	o = o.getElementsByTagName('input')
	var selected_total = 0
	var partial_medium_selected = false
	for(var i = 0; i < o.length; i++)
	{
		if (o[i].disabled)
			continue
		if (option === 'all' && !o.item(i).checked){
			o.item(i).checked = true
			boxClick(o.item(i), 'click')
		}
		if (option === 'none' && o.item(i).checked){
			o.item(i).checked = false
			boxClick(o.item(i), 'click')
		}
		if (option === 'invert'){
			o.item(i).checked = !o.item(i).checked
			boxClick(o.item(i), 'click')
		}

		if (o.item(i).checked) {
			selected_total++
			if (o.item(i).name.indexOf('selected_ids_mm') != -1 && o.item(i).value == 'PARTIAL')
				partial_medium_selected = true
		}
	}

	return [selected_total, partial_medium_selected]
},

enable_datatable_buttons : function _edb_recurse(data_table, option)
{
	if ((data_table === undefined) || (data_table === ''))
		return YAHOO.util.Dom.getElementsByClassName('dataTable', 'div', 'body', _edb_recurse, option)

	var pn
	do
	{
		pn = data_table.parentNode
		do
		{
			if (data_table.tagName === 'DIV' && YAHOO.util.Dom.hasClass(data_table, 'dataTable'))
				return YAHOO.zmc.utils._enable_datatable_buttons(data_table, option)
		}
		while (data_table = data_table.previousSibling)
	}
	while (data_table = pn)
},

_enable_datatable_buttons : function(data_table, option)
{
	var result = this.select(data_table, option)
	this.selected_total = result[0]
	var partial_medium_selected = result[1]
	if(partial_medium_selected){ //only "recycle_button" is enabled and disable everything else if at least one partial backup is selected
		this.setEnableStatus(data_table, ["recycle_button"], true)
		this.setEnableStatus(data_table, ["prune_button"], "vault_no_button", this.selected_total == 1) //"prune_button" and "vault_no_button" are enabled if exactly one partial backup is selected
		this.setEnableStatus(data_table,
			["merge_button", "edit_button", "open_button", "duplicat_button", "use_button", "use_with_button", "verify_i_button", "migrate_button",
			 "list_button", "disklist_button", "expert_button", "explore_button", "get_button", "delete_button", "check_ho_button", "move_button",
			 "copy_to_button", "activate_button", "deactiva_button", "abort_button", "comment_button", "drop_button", "archive_button", "save_lab_button", "start_ba_button"],
			false)
	} else {
		this.setEnableStatus(data_table, ["next_button", "edit_button", "open_button", "duplicat_button", "use_button", "use_with_button", "verify_i_button", "migrate_button", "list_button", "disklist_button", "expert_button", "explore_button", "vault_no_button", "start_ba_button"],
			this.selected_total == 1)
		this.setEnableStatus(data_table, ["get_button", "delete_button", "check_ho_button", "move_button", "copy_to_button", "activate_button", "deactiva_button", "abort_button", "comment_button", "recycle_button", "drop_button", "archive_button", "save_lab_button", "start_ba_button"],
			this.selected_total > 0)
		this.setEnableStatus(data_table, ["prune_button"], this.selected_total == 0)
		this.setEnableStatus(data_table, ["merge_button"], this.selected_total > 1)
	}
},

show_mt : function(device_name)
{
	if (device_name == '')
	{
		alert('Please select a changer device first.')
		return false
	}
	this.show_mtls_fix_height()
	var dev
	if (zmcRegistry['changerdev_list'] !== undefined)
		dev = zmcRegistry['changerdev_list'][device_name]
	if (dev === undefined && zmcRegistry['tapedev_list'] !== undefined)
		dev = zmcRegistry['tapedev_list'][device_name]
	if (dev === undefined)
	{
		return this.show_lsscsi()
	}
	gebi('li_lsscsi').className = ''
	gebi('li_mt').className = 'current'
	var o=gebi('ta_mt')
	o.innerHTML = dev['stderr'] + '\n' + dev['stdout']
	gebi('ta_lsscsi').style.display = 'none'
	o.style.display = 'block'
	return false
},

show_lsscsi : function()
{
	this.show_mtls_fix_height()
	gebi('li_mt').className = ''
	gebi('li_lsscsi').className = 'current'
	var o=gebi('ta_lsscsi')
	gebi('ta_mt').style.display = 'none'
	o.style.display = 'block'
	return false
},

show_mtls_fix_height : function()
{
	var d = gebi('deviceFormWrapper')
	var h = (d.clientHeight - 98) + 'px'
	gebi('ta_lsscsi').style.height = h
	gebi('ta_mt').style.height = h
},

update_space_needed : function()
{
	var o = gebi('tapetype:length')
	if (o == undefined)
		return
	var length = parseInt(o.value)
	var units = gebi('tapetype:length_display')
	var text = ''
	if (units.options == undefined)
		text = units.value
	else
		for(var i=0; i< units.options.length; i++)
			if (units.options[i].value === units.value)
				text = units.options[i].text

	var slots = gebi('changer:slots')
	alert(slots.value);
	if (slots != undefined)
		slots = slots.value
	else
	{
		var o = gebi('changer:slotrange')
		if (o == undefined)
			return
		slots = gebi('changer:slotrange').value
	}
	var display = gebi('space_needed')
	display.innerHTML = ' About ' + (length * slots * 0.75) + text + ' needed,<br />&nbsp; if backup runs average ' + (length * 0.75) + text + '.'
},

data_table_button_redirect : function(url)
{
	var b = ''
	var o=gebi('dataTable')
	if (o == undefined)
		alert('data_table_button_redirect internal error')
	o = o.getElementsByTagName('input')
	for(var i = 0; i < o.length; i++)
	{
		b=o.item(i)
		if (b.checked)
		{
			b=b.name.substr(13, b.name.length -14)
			break
		}
	}
	window.location.href=url+b
	return false
},

basename : function(path)
{
	return path.substr(0, 1 + path.lastIndexOf("/"))
},

// Ajax <method> to <url> followed by insert results into <target> (message box if null).
// If not inserting into message box, ask all JS scripts on page to "redraw".
// target - Dom object or object id receiving 
// url - the URI of the resource
// method OPTIONAL - post or get
// form OPTIONAL - a DOM form to submit
ajaxUpdate : function(target, url, method, form)
{
	var callback =
	{
		timeout: 3000,
		success: function(result) { this.updateRefresh(result, target); },
		failure: function(result) { YAHOO.zmc.utils.genericFailure(result); }
	}
	if (form)
		YAHOO.util.Connect.setForm(form)
	YAHOO.util.Connect.asyncRequest((method ? method:'GET'), url, callback)
},

updateRefresh : function (target, result, append)
{
	var o = gebi(target)
	if (!o)
		return YAHOO.zmc.messageBox.append('message', result.responseText)

	if (append == true)
		o.innerHTML += result.responseText
	else
		o.innerHTML = result.responseText

	var scripts = o.getElementsByTagName("SCRIPT")
	for(var i=0; i < scripts.length; i++)
		eval(scripts[i].text)
},

genericFailure : function(result)
{
	if (result.status == "401")
		return window.location.reload()

	if (result.responseText && result.responseText.length > 0)
		YAHOO.zmc.messageBox.append('errors', result.responseText)
},

mb2units: function(mbs)
{
	if (typeof mbs === 'string')
		mbs = parseInt(mbs)
	var units = ['MB', 'GB', 'TB', 'PB']
	while(mbs > 1024)
	{
		mbs = mbs / 1024
		units.shift()
	}
	return mbs.toString() + " " + units.shift()
},

timestamp2locale: function(timestamp)
{
	var dateObject = new Date(timestamp * 1000)
	if (0 == timestamp)
		return "Never"

	return dateObject.toLocaleString()
},

twirl : function(twirlImage, target, open)
{
	var image = gebi(twirlImage)
	var o = gebi(target)
	var fvalue = 'off';
	if (!o)
		return;

	if (	((open === undefined) && o.style.display == "none")
		||	((open !== undefined) && open))
	{
		o.style.display = 'block'
		fvalue = 'on'
		image.src = "/images/global/twirl-down-arrow.png"
	}
	else
	{
		o.style.display = 'none'
		image.src = "/images/global/twirl-up-arrow.png"
	}
	o=gebi('private:zmc_show_advanced');
	if (o) o.value = fvalue
	o=gebi('property_list:zmc_show_advanced');
	if (o) o.value = fvalue
},

openOutputWindow : function(text)
{
	var newWin = window.open()
	newWin.document.write('<pre>'+text+'</pre>')
	newWin.document.close()
},

queryString : function(url, params)
{
	if(!params)
		params = new Array()
	var func = function(key, value, acc){ return acc + "&"+key+"="+value }
	paramString = params.injectWithProperties(func, "")
	return url+"?"+paramString
},

monitorFailureCount:0,

monitorStart:function()
{
	this.transaction = YAHOO.util.Connect.asyncRequest('GET', this.restUrl, this.callback, null)
},

monitor:function(target, taskName, restUrl, delay, frequency, statusChangeUrl)
{
	this.frequency = frequency
	this.statusChangeUrl = statusChangeUrl
	this.taskName = taskName
	this.restUrl = restUrl
	var fn = this.monitorStart
	var args = this
	if (zmcRegistry['debug'])
	{
		this.frequency = this.frequency / 2;
		delay = delay / 2;
		YAHOO.zmc.utils.callback.timeout = 1500;
	}
	this.callback.argument = target
	setTimeout(function () { fn.apply(args) }, delay)
},

handleSuccess:function(o)
{
	var oJSON = eval('(' + o.responseText + ')')
	if (typeof oJSON !== 'object') alert('reply was not JSON')
	var status = oJSON['status']
	var dateNow = new Date().toLocaleString() + '<br />'
	var outputDiv
	if (o.argument)
	{
		outputDiv = gebi(o.argument)
		if (outputDiv)
			outputDiv.innerHTML = oJSON['output']
	}
	
	if (!outputDiv)
		YAHOO.zmc.messageBox.append('message', result.responseText)

	if (status == 'Finished')
		return setTimeout('window.location.replace("' + this.statusChangeUrl + '")', zmcRegistry['debug'] ? 3000:0)

	var fn = this.monitorStart
	var args = this
	setTimeout(function () { fn.apply(args) }, this.frequency)
},

handleFailure:function(o)
{
	if (result.status == "401")
		return window.location.reload()

	if (result.responseText && result.responseText.length > 0)
		YAHOO.zmc.messageBox.append('errors', result.responseText)

	this.monitorFailureCount++
	if (this.monitorFailureCount > 10)
		console.log('Unable to retrieve task "' + this.taskName + '" status.')
	else
	{
		if (this.frequency < 10000)
			this.frequency += 500
		var fn = this.monitorStart
		var args = this
		setTimeout(function () { fn.apply(args) }, this.frequency)
	}
}
};

YAHOO.zmc.utils.callback =
{ 
	failure: YAHOO.zmc.utils.handleFailure,
	scope:   YAHOO.zmc.utils,
	success: YAHOO.zmc.utils.handleSuccess,
	timeout: 3000
};

zmcRegistry.app_set_recommended = function()
{
	var ah=gebi('property_list:zmc_amanda_app')
	var ad=gebi('property_list:zmc_amanda_app_disabled')
	var e=gebi('property_list:zmc_extended_attributes')
	if (e.checked)
	{
		ad.value=ah.value=zmcRegistry.app_set_recommended_checked()
		gebi('property_list:zmc_custom_app').value = ''
		var o=gebi('property_list:zmc_custom_app_div')
		if (o) o.style.visibility = 'hidden'
		o=gebi('property_list:zmc_override_app_div')
		if (o) o.style.visibility = 'hidden'
	}
	else
	{
		ad.value=ah.value=zmcRegistry.app_set_recommended_unchecked()
		gebi('property_list:zmc_custom_app_div').style.visibility = 'visible'
	}
	var s=gebi('star_note')
	if(s) s.style.visibility = ((ad.value === 'star') ? 'visible' : 'hidden')
}

zmcRegistry.app_highlight_recommended = function()
{
	var ad=gebi('property_list:zmc_amanda_app_disabled')
	var ac=gebi('property_list:zmc_custom_app')
	var s=gebi('star_note')
	if (s) s.style.visibility = ((ad.value === 'star' || (ac && ac.value === 'star')) ? 'visible' : 'hidden')
	if (ac && ad)
	{
		if (ac.value != '' && ac.value != ad.value)
			ad.style.backgroundColor='#FFFF66'
		else
			ad.style.backgroundColor=''
	}
	o=gebi('property_list:zmc_override_app')
	if (o){
		if(ac.value === '0'){
			gebi('property_list:zmc_override_app_div').style.visibility = 'visible'
			o.style.visibility = 'visible'
			if(!gebi('msgBoxWarnings_customApp')){ //the warning message hasn't been added
				var warningMsg = "A dumptype named  \"zmc_&#60;CUSTOM APPLICATION&#62;_app\" must be defined in<br>" +
					"/etc/amanda/&#60;CONFIG NAME&#62;/zmc_backupset_dumptypes.<br>Please contact Zmanda Support for more information."
				var htmlStr = "<div class=\"zmcMessages zmcUserWarnings\" id=\"msgBoxWarnings_customApp\" style=\"float:left\">" + 
					"<div class=\"zmcMsgBox\" onclick=\"this.parentNode.style.display='none'\">X</div>" +
					"<div class=\"zmcMsgWarnErr\"><img onclick=\"this.parentNode.style.display='none'\" " +
					"style=\"cursor:pointer\" src=\"/images/global/calendar/icon_calendar_warning.gif\" " +
					"alt=\"Warnings\">&nbsp;"+ warningMsg + "</div></div>"
				gebi('zmcMessageBox0').innerHTML += htmlStr
			}
		} else {
			gebi('property_list:zmc_override_app_div').style.visibility = 'hidden'
			o.style.visibility = 'hidden'
			customAppWarnMsg = gebi('msgBoxWarnings_customApp')
			if(customAppWarnMsg) //delete the custom application warning message
				gebi('zmcMessageBox0').removeChild(customAppWarnMsg)
		}
	}
}

zmcRegistry.adjust_custom_compress = function()
{
	var compress=gebi('compress')
	var customCompress=gebi('property_list:zmc_custom_compress')

	if (customCompress){
		if(compress.value === 'client custom' || compress.value === 'server custom'){
			gebi('property_list:zmc_custom_compress_div').style.visibility = 'visible'
			customCompress.style.visibility = 'visible'
		} else {
			gebi('property_list:zmc_custom_compress_div').style.visibility = 'hidden'
			customCompress.style.visibility = 'hidden'
		}
	}
}


zmcRegistry.adjust_zmc_windowssqlserver = function()
{
	var dataSource = gebi('data_source')

	if(!dataSource) return
	
 if(dataSource.value === 'manually_type_in'){
		var o = gebi('application_version_div')
		if(o) o.style.visibility = 'visible'
		o = gebi('server_name_div')
		if(o) o.style.visibility = 'visible'
		o = gebi('instance_name_div')
		if(o) o.style.visibility = 'visible'
		o = gebi('database_name_div')
		if(o) o.style.visibility = 'visible'
	} else {
		var o = gebi('application_version_div')
		if(o) o.style.visibility = 'hidden'
		o = gebi('server_name_div')
		if(o) o.style.visibility = 'hidden'
		o = gebi('instance_name_div')
		if(o) o.style.visibility = 'hidden'
		o = gebi('database_name_div')
		if(o) o.style.visibility = 'hidden'
	}
}

zmcRegistry.adjust_windowstemplate = function()
{
	if(gebi('property_list:zmc_type').value == 'windowstemplate'){
		if(gebi('all_local_drives').checked == true)
			gebi('disk_device').disabled = true
		else
			gebi('disk_device').disabled = false	
	}
}

zmcRegistry.adjust_zmc_windowsexchange = function()
{
	var dataSource = gebi('data_source')
	
	if(!dataSource) return
	
	if(dataSource.value === 'manually_type_in'){
		var o = gebi('application_version_div')
		if(o) o.style.visibility = 'visible'
		o = gebi('server_name_div')
		if(o) o.style.visibility = 'visible'
		o = gebi('database_name_div')
		if(o) o.style.visibility = 'visible'
	} else {
		var o = gebi('application_version_div')
		if(o) o.style.visibility = 'hidden'
		o = gebi('server_name_div')
		if(o) o.style.visibility = 'hidden'
		o = gebi('database_name_div')
		if(o) o.style.visibility = 'hidden'
	}
}

zmcRegistry.adjust_zmc_windowshyperv = function()
{
	var dataSource = gebi('data_source')
	
	if(!dataSource) return
	
	if(dataSource.value === 'manually_type_in'){
		var o = gebi('application_version_div')
		if(o) o.style.visibility = 'visible'
		o = gebi('server_name_div')
		if(o) o.style.visibility = 'visible'
		o = gebi('instance_name_div')
		if(o) o.style.visibility = 'visible'
		o = gebi('database_name_div')
		if(o) o.style.visibility = 'visible'
	} else {
		var o = gebi('application_version_div')
		if(o) o.style.visibility = 'hidden'
		o = gebi('server_name_div')
		if(o) o.style.visibility = 'hidden'
		o = gebi('instance_name_div')
		if(o) o.style.visibility = 'hidden'
		o = gebi('database_name_div')
		if(o) o.style.visibility = 'hidden'
	}
}

zmcRegistry.initialize_data_source_selection = function()
{
	var diskDevice = gebi('disk_device')
	var dataSource = gebi('data_source')
	if(diskDevice && dataSource){
		if(diskDevice.value === 'ZMC_MSExchange'){
			zmcRegistry.adjust_zmc_windowsexchange()
			dataSource.value = 'all'
		} else if(diskDevice.value === 'ZMC_MSHyperV'){
			zmcRegistry.adjust_zmc_windowshyperv()
			dataSource.value = 'all'
		} else if(diskDevice.value === 'ZMC_MSSQL') {
			zmcRegistry.adjust_zmc_windowssqlserver()
			dataSource.value = 'all'
		} else {
			dataSource.value = 'manually_type_in'
		}
	}
}

zmcRegistry.adjust_discover_button = function ()
{
	var discoverButton = gebi('discoverButton')
	if(gebi('property_list:zmc_type').value === 'windowsexchange'
		|| gebi('property_list:zmc_type').value === 'windowshyperv'
		|| gebi('property_list:zmc_type').value === 'windowssqlserver')
		discoverButton.disabled = false
	else
		discoverButton.disabled = true
	var submitButton = gebi('zmcSubmitButton')
	if(submitButton.value === 'Add')
		discoverButton.value = "Discover"
	else
		discoverButton.value = "Rediscover"
}

zmcRegistry.adjust_exchange_level_1_backup = function()
{
	if(gebi('property_list:zmc_type').value === 'windowsexchange'){
		var submitButton = gebi('zmcSubmitButton')
		if(submitButton && submitButton.value === 'Update'){
			gebi('level_1_backup_disabled').disabled = true
		}
	}
}

zmcRegistry.adjust_sqlserver_level_1_backup = function()
{
	if(gebi('property_list:zmc_type').value === 'windowssqlserver'){
		var submitButton = gebi('zmcSubmitButton')
		if(submitButton && submitButton.value === 'Update'){
			gebi('level_1_backup_disabled').disabled = true
		}
	}
}

zmcRegistry.update_discover_result = function ()
{
	var discoveredComponents = gebi('discovered_components')
	if(!discoveredComponents) return

	var componentsList = discoveredComponents.value.split(';')
	var dataSource = gebi('data_source')
	var selectedOption = dataSource.value
	var addedElements = ""
	var found = false
	for(var i = 0; i < componentsList.length; i++){
		if(componentsList[i]) {//check empty string
			var newOp = document.createElement("option");
			newOp.value = componentsList[i];
			newOp.title = componentsList[i];
			newOp.text = componentsList[i];
			dataSource.options.add(newOp);
			if(!found && componentsList[i] == gebi('disk_device').value){
				selectedOption = componentsList[i]
				found = true
			}
		}
	}
	dataSource.value = selectedOption
	if(found) {
		var type = gebi('property_list:zmc_type')
		if(type.value == 'windowsexchange'){
			zmcRegistry.adjust_zmc_windowsexchange()
		}
		
		if(type.value == 'windowshyperv'){
			zmcRegistry.adjust_zmc_windowshyperv()
		}
		
		if(type.value == 'windowssqlserver'){
			zmcRegistry.adjust_zmc_windowssqlserver()
		}
	}
		
}

zmcRegistry.adjust_zmc_ndmpDataPathChanged = function()
{
	var dataPath = gebi('data_path');
	if(!dataPath) return;
	
	if(dataPath.value === 'amanda'){
		var o = gebi('holdingdisk');
		if(o) o.checked = false;
		o = gebi('encrypt_div');
		if(o) o.style.visibility = 'visible';
		o = gebi('compress_div');
		if(o) o.style.visibility = 'visible';
		o = gebi('holdingdisk_div');
		if(o) o.style.visibility = 'visible';
		encrypt_keys_link();
	} else if (dataPath.value === 'directtcp'){
		var o = gebi('holdingdisk');
		if(o) o.checked = true;
		o = gebi('encrypt_div');
		if(o) o.style.visibility = 'hidden';
		o = gebi('encrypt');
		if(o) o.value = 'none';
		o = gebi('compress_div');
		if(o) o.style.visibility = 'hidden';
		o = gebi('compress');
		if(o) o.value = 'none';
		o = gebi('holdingdisk_div');
		if(o) o.style.visibility = 'hidden';
		encrypt_keys_link();
	}
}

zmcRegistry.adjust_vault_datetime_pickers = function()
{
	var isDisabled = !gebi('vault_time_frame_radio_button').checked;
	var o = gebi('start_date_picker').disabled = isDisabled;
	o = gebi('start_time_picker').disabled = isDisabled;
	o = gebi('end_date_picker').disabled = isDisabled;
	o = gebi('end_time_picker').disabled = isDisabled;
}

zmcRegistry.adjust_vault_backup_run_range_div = function()
{
	var o = gebi('latest_full_backup_radio_button');
	if(o.checked)
		gebi('backup_run_range_div').style.visibility = 'hidden';
	else
		gebi('backup_run_range_div').style.visibility = 'visible';

}

YAHOO.zmc.utils.setCheckedByIdPrefix = function(prefix, value, root)
{
	var checkBoxes = YAHOO.util.Dom.getElementGroupByIdPrefix(prefix, root)
	for(var i = 0; i < checkBoxes.length; i++)
		checkBoxes[i].checked = value
}

/* returns an array of descendants of a given node */
YAHOO.util.Dom.getElementGroupByIdPrefix = function(prefix, root)
{
	return YAHOO.util.Dom.getElementsBy(
			function(element){ return (element.id.indexOf(prefix) == 0) }, null, root)
}

YAHOO.register("zmc-utils", YAHOO.zmc.utils, {version: "2.9.0", build: "$Revision: 40320 $".substr(11,5)})
