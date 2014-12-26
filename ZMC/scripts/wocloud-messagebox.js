 //
 // The software you have just accessed, its contents, output and underlying
 // programming code are the proprietary and confidential information
 // Incorporated.  Only specially authorized employees, agents or licensees
 // may access and use this software.  If you have not been given
 // specific written permission by Zmanda, any attempt to access, use, decode,
 // modify or otherwise tamper with this software will subject you to civil
 // liability and/or criminal prosecution to the fullest extent of the law.
 // $Id: wocloud-messagebox.js 35641 2013-02-25 21:33:37Z anuj $ */

YAHOO.namespace('zmc.messageBox');
YAHOO.zmc.messageBox =
{
map:{
		instructions:"wocloudUserInstructions",
		escapedInstructions:"wocloudUserInstructions",
		messages:"wocloudUserMessages",
		escapedMessages:"wocloudUserMessages",
		warnings:"wocloudUserWarnings",
		escapedWarnings:"wocloudUserWarnings",
		errors:"wocloudUserErrors",
		escapedErrors:"wocloudUserErrors",
		internals:"wocloudUserInternalErrors",
		escapedInternals:"wocloudUserInternalErrors",
		details:"wocloudUserDetails",
		escapedDetails:"wocloudUserDetails"
},

iconMap:{
		instructions:"",
		escapedInstructions:"",
		messages:"icon_calendar_success",
		escapedMessages:"icon_calendar_success",
		warnings:"icon_calendar_warning",
		escapedWarnings:"icon_calendar_warning",
		errors:"icon_calendar_failure",
		escapedErrors:"icon_calendar_failure",
		internals:"icon_calendar_failure",
		escapedInternals:"icon_calendar_failure",
		details:"",
		escapedDetails:""
},

isEmpty:function()
{
	alert('If this method (YAHOO.zmc.messageBox.isEmpty) is used, then fix it ..')
	var messageBox = gebi('wocloudMessageBox0');
	var noInstructions = (YAHOO.util.Dom.getElementsByClassName("instructions", null, messageBox, null).length == 0);
	var noUserErrors = (YAHOO.util.Dom.getElementsByClassName("wocloudUserErrors", null, messageBox, null).length == 0);
	var noProgramErrors = (YAHOO.util.Dom.getElementsByClassName("wocloudUserWarnings", null, messageBox, null).length == 0);
	var retval = noInstructions && noUserErrors && noProgramErrors; 
	return retval;

},

appendZMCMessageBox:function(pm, clearFirst)
{
	if (clearFirst)
		this.clear()

	if (!pm)
		return

	if (pm.details) // => rest exception, so swap for escaped errors so append renders correctly
	{
		pm.escapedErrors = [pm.details]
		pm.details=""
	}

	for(var type in pm)
		// if it's a type we recognise, spit out all messages of that type
		if (typeof(pm[type]) != 'function')
			for(var i = 0; i < pm[type].length; i++)
				YAHOO.zmc.messageBox.append(type, pm[type][i])
},

renderRestException:function(level, message)
{
	var fields = message.split("~");
	var userMsg = fields[7];
	var output = '<div class="'+level+'">'+userMsg+'</div>';
	output += '<div class="instructions" id="showDetails">(<a href="javascript:\
		gebi(\'errorDetails\').style.display=\'block\';\
		gebi(\'showDetails\').style.display=\'none\';\
		void(\'\');">Show Details</a>)</div>';
	output += '<div id="errorDetails" class="wocloudUserDetails" style="display:none">'+message+'</div>';
	output += '<div style="clear:both"></div>';
	return output;
},

/* an event, for now, consists of a level and a string message */
append:function(type, message)
{
	if (!YAHOO.zmc.messageBox.map[type])
		type = 'errors'

	var messageBox = gebi('wocloudMessageBox0')
	if (!messageBox)
		return alert(type + ': ' + message)

	//check to see if it matches a rest exception pretty print, but only if we're not going to choke on too much input
	var fields = message.split("~")
	if (fields.length == 8) //the pretty message is in position 8 (7 0 indexed)
		messageBox.innerHTML += renderRestException(type, message)
	else //normal append
		messageBox.innerHTML += '\
			<div class="' + YAHOO.zmc.messageBox.map[type] + '">\
				<div onclick="this.parentNode.style.display=\'none\';" class="wocloudMsgBox">X</div>\
					<div class="wocloudMsgWarnErr" style="">\
						<img style="cursor: pointer;" src="/images/global/calendar/' + YAHOO.zmc.messageBox.iconMap[type] + '.gif" onclick="this.parentNode.style.display=\'none\'">&nbsp;'
						+ message + '\
					</div>\
				</div>\
			</div>\
			<div style="clear:both"></div>\
'

	messageBox.style.display = 'block'
},

clear:function()
{
	var messageBox = gebi('wocloudMessageBox0');
	if (messageBox)
		messageBox.innerHTML = '';
}
};

YAHOO.register("wocloud-messagebox", YAHOO.zmc.messageBox, {version: "2.9.0", build: "$Revision: 35641 $".substr(11,5)})
