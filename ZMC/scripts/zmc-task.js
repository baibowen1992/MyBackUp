 //		Copyright (C) 2006-2013 Zmanda Incorporated.
 //			     All Rights Reserved.
 //
 // The software you have just accessed, its contents, output and underlying
 // programming code are the proprietary and confidential information of Zmanda
 // Incorporated.  Only specially authorized employees, agents or licensees of
 // Zmanda may access and use this software.  If you have not been given
 // specific written permission by Zmanda, any attempt to access, use, decode,
 // modify or otherwise tamper with this software will subject you to civil
 // liability and/or criminal prosecution to the fullest extent of the law.
 // $Id: zmc-task.js 35641 2013-02-25 21:33:37Z anuj $ */

YAHOO.namespace('zmc.task');
YAHOO.zmc.task = {
num:2,
failureCount:0,

start:function()
{
	this.transaction = YAHOO.util.Connect.asyncRequest('GET', this.restUrl, this.callback, null)
},

monitor:function(jobType, jobName, startState, statusChangeUrl, delay, frequency)
{
	this.frequency = frequency
	this.statusChangeUrl = statusChangeUrl
	this.startState = startState
	this.jobName = jobName
	this.jobType = jobType
	this.restUrl = '/Common/job.php?type=' + jobType + '&job_name=' + jobName
	var fn = this.start
	var args = this
	setTimeout(function () { fn.apply(args) }, delay)
},

handleSuccess:function(result)
{
	if (this.num > 15)
		this.num = 1

	var outputDiv = gebi('job_output')
	var statusDiv = gebi('job_status')
	var reply = YAHOO.lang.JSON.parse(result.responseText)
	if (!reply)
	{
		if (zmcRegistry['debug']) alert('Disabling job status polling because response was invalid/empty.')
		return
	}

	reply=reply.state
	if (zmcRegistry['debug']) YAHOO.zmc.messageBox.append('messages', 'reply.running=' + (reply.running ? 'yes':'no') + '; zmcRegistry.was_restoring=' + (zmcRegistry.was_restoring ? 'yes':'no'));
	if (reply.running)
	{
		this.frequency = 2000;
		var progressDots = '.'.repeat(this.num++) + "\n<br />"
		var o = gebi('progress_dots')
		if (o) o.innerHTML = progressDots 
		else YAHOO.zmc.messageBox.append('messages', 'Restoring ' + progressDots)
		if (statusDiv) statusDiv.innerHTML = reply['status'].join('<br />\n')
		var o = gebi('duration')
		if (o) o.innerHTML = reply['duration']
	}
	else if (this.frequency < 60000)
		this.frequency += 500

	if (!reply.running && zmcRegistry.was_restoring)
	{
		alert('Job finished. Refreshing page.') // this suspends Ajax job status polling until user responds (prevents browser memory leak bugs from crashing customer's browser
		setTimeout('window.location.replace("' + this.statusChangeUrl + '")', zmcRegistry['debug'] ? 0:0)
	}

	if (zmcRegistry['debug']) YAHOO.zmc.messageBox.append('messages', 'zmcRegistry.was_restoring = reply.running');
	zmcRegistry.was_restoring = reply.running
	var fn = this.start
	var args = this
	setTimeout(function () { fn.apply(args) }, this.frequency)
},

handleFailure:function(o)
{
	this.failureCount++
	if (this.failureCount > 10)
		alert('Unable to retrieve task "' + this.jobName + '" (' + this.taskId + ') status.')
	else
	{
		var fn = this.start
		var args = this
		if (this.frequency < 10000)
			this.frequency += 500
		setTimeout(function () { fn.apply(args) }, this.frequency)
	}
}
};

YAHOO.zmc.task.callback =
{
	failure: YAHOO.zmc.task.handleFailure,
	scope:   YAHOO.zmc.task,
	success: YAHOO.zmc.task.handleSuccess,
	timeout: 3000
};

YAHOO.register("zmc-task", YAHOO.zmc.task, {version: "2.9.0", build: "$Revision: 35641 $".substr(11,5)})
