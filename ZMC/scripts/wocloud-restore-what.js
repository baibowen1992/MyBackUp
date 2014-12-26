 //
 // The software you have just accessed, its contents, output and underlying
 // programming code are the proprietary and confidential information
 // Incorporated.  Only specially authorized employees, agents or licensees
 //  may access and use this software.  If you have not been given
 // specific written permission by Zmanda, any attempt to access, use, decode,
 // modify or otherwise tamper with this software will subject you to civil
 // liability and/or criminal prosecution to the fullest extent of the law.
 // $Id: wocloud-restore-what.js 39213 2014-02-26 19:50:40Z qtran $ */

YAHOO.namespace('zmc.restore.what');
YAHOO.zmc.restore.what = 
{
	showPaths:function(hostname)
	{
		for(var o in zmcRegistry['dles'][hostname])
			p.add(new Option(zmcRegistry['dles'][hostname][o], zmcRegistry['dles'][hostname][o]), null)
	},

	selectHost:function(hostname)
	{
		hostname = hostname.value
		gebi('client').value = hostname
		this.swapPopOn('Path', 300, 600, true)
		gebi('disk_name').select()
	},

	selectPath:function(o)
	{
		gebi('disk_name').value = o.value
		if(o.value == "ZMC_MSExchange"){
			gebi('Restore All').disabled = true
			if(gebi('Restore All').checked)
				gebi('Explore & Select').checked = true
		} else {
			gebi('Restore All').disabled = false
		}
		this.swapPopOff(300, 600)
	},

	swapPopOn:function(id, delayOff, delayOn, toggle)
	{
		var o = gebi('restoreWhatLeftContainer')
		if (o) o.style.display = 'none'
		o = gebi('restoreWhatRightContainer')
		if (o) o.style.display = 'none'
		o = gebi('swapButtons')
		if (o) o.style.display = 'none'
		o = gebi('nextStep')
		if (o) o.style.display = 'none'
		//gebi('leftContainerContents').style.display = 'none'
		if (typeof zmcRegistry == 'undefined' || typeof zmcRegistry["dles"] == 'undefined') // on first invocation, initialize
		{
			alert('ZMC Internal Error: no dles found in registry')
		}

		if (id == 'Path')
		{
			hostname = gebi('client').value
			if (typeof zmcRegistry['dles'][hostname] != 'undefined')
			{
				//zmcRegistry['dles'][hostname].unshift('Other')
				var length = zmcRegistry['dles'][hostname].length
				for(var o=0, s=''; o < length; o++)
					s += '<input id="dd' + o + '" name="p" type="radio" value="' + zmcRegistry['dles'][hostname][o]
					  + '" onclick="YAHOO.zmc.restore.what.selectPath(this)" /><label for="dd' + o + '">&nbsp;' + zmcRegistry['dles'][hostname][o] + '</label><br style="clear:left;" />'

				gebi('restoreWhatRightInteriorContainerPath').innerHTML =
					'<input id="ddother" name="p" type="radio" onclick="YAHOO.zmc.restore.what.swapPopOff(300,600); gebi(' + "'" + 'disk_name' + "'" + ').select()" /><label for="ddother">&nbsp;Other</label><br style="clear:left;" />' + s
			}
		}

		id = 'restoreWhatRightContainer'+id
		if (gebi(id).style.display == 'block')
		{
			if (toggle)
				this.swapPopOff(delayOff, delayOn)
		}
		else
		{
			window.setTimeout("YAHOO.zmc.restore.what.swapHide()", delayOff)
			window.setTimeout("YAHOO.zmc.restore.what.swapShow('"+id+"')", delayOn)
		}
	},

	swapPopOff:function(delayOff, delayOn)
	{
		//alert('off'+delayOff+'; on'+delayOn)
		window.setTimeout("YAHOO.zmc.restore.what.swapHide()", delayOff)
		window.setTimeout("YAHOO.zmc.restore.what.swapShow('restoreWhatRightContainer')", delayOn)
	},

	swapHide:function()
	{
		var o = gebi('restoreWhatRightContainer')
		if (o)
			o.style.display = 'none'
		gebi('restoreWhatRightContainerPath').style.display = 'none'
		gebi('restoreWhatRightContainerHost').style.display = 'none'
	},

	swapShow:function(id)
	{
		var o = gebi(id)
		if (o) o.style.display = 'block'
	},

	indexListener:
	{
		receive:function(message)
		{
			if (0 == message.code)
				location.reload(true)
			else
				YAHOO.zmc.messageBox.append('internals', message.errors)
		}
	},

	indexStateCallback: 
	{
		timeout: 3000,
		cache: false,
		success: function(result)
		{
			var reply = YAHOO.lang.JSON.parse(result.responseText)
			if (!reply)
				return

			reply=reply.state
			if (reply.date_started_timestamp == 0)
			{
				if (zmcRegistry.explore_status_count > 1)
				{
					alert('The explored results have been cleared by another ZMC user.')
					return window.location.pathname = '/ZMC_Restore_What'
				}
			}
			else if (zmcRegistry.explore_date_started_timestamp != reply.date_started_timestamp)
			{
				zmcRegistry.explore_date_started_timestamp = reply.date_started_timestamp
				zmcRegistry.explore_status_count = 0
				if (reply.running)
				{
					zmcRegistry.was_exploring = true
					if (zmcRegistry.i_pushed_explore_button)
						; //alert('Your explore job has started.  Please wait ..');
					else
						alert('An explore job has started.  Please wait ..')

					YAHOO.zmc.restore.what.indexStateTransitioned(true)
				}
				else if (zmcRegistry.i_pushed_explore_button)
				{
					alert('Your explore job has completed for this backup set.\nLoading results ..')
					return window.location.pathname = '/ZMC_Restore_What' // the current ZMC user pushed the button, so just refresh page without warning
				}
				else
				{
					alert('An explore job has completed for this backup set.\nLoading results ..')
					return window.location.pathname = '/ZMC_Restore_What' // the current ZMC user pushed the button, so just refresh page without warning
				}
			}

			if (zmcRegistry.was_exploring && !reply.running)
			{
				zmcRegistry.was_exploring = false
				if (zmcRegistry.i_pushed_explore_button)
				{
					alert('Your explore job has completed for this backup set.\nLoading results ...')
					return window.location.pathname = '/ZMC_Restore_What' // the current ZMC user pushed the button, so just refresh page without warning
				}
				else
				{
					alert('An explore job has completed for this backup set.\nLoading results ...')
					return window.location.pathname = '/ZMC_Restore_What' // the current ZMC user pushed the button, so just refresh page without warning
				}
			}

			//zmc_print(reply)
			while(zmcRegistry.explore_status_count < reply.status.length)
			{
				var where='messages'
				var msg=reply.status[reply.status.length - ++zmcRegistry.explore_status_count]
				if (msg.indexOf('Aborted') != -1 || msg.indexOf('Crashed') != -1 || msg.indexOf('Failed') != -1)
					where='errors'
				if (msg.indexOf('Cancelled') != -1)
					where='warnings'
				YAHOO.zmc.messageBox.append(where, msg)
			}

		},
		failure: YAHOO.zmc.utils.genericFailure
	},

	indexStateTransitioned:function(loading)
	{
		var o = gebi('rbox_select_all_none_invert')
		if (o) o.style.display = loading ? 'none' : 'block'
		o = gebi('restoreLoaderID')
		if (o) o.style.display = loading ? 'block' : 'none'
		o = gebi('restoreWhatLeftInteriorContainer')
		if (o) o.style.display = loading ? 'none' : 'block'
	},

	indexStatePoller:function()
	{
		YAHOO.util.Connect.asyncRequest('GET', '/Common/job.php?type=amgetindex', YAHOO.zmc.restore.what.indexStateCallback, null)
		setTimeout("YAHOO.zmc.restore.what.indexStatePoller()", zmcRegistry.pollFreq)
	},

	abort:function()
	{
		YAHOO.util.Connect.asyncRequest('GET', '/Common/job.php?type=amgetindex&abort=1', YAHOO.zmc.restore.what.indexStateCallback, null)
	},

	loadSuccess:function()
	{
		setTimeout("YAHOO.zmc.restore.what.indexStatePoller()", zmcRegistry.pollFreq)
	}
};

YAHOO.register("wocloud-restore-what", YAHOO.zmc.restore.what, {version: "2.9.0", build: "$Revision: 39213 $".substr(11,5)})
