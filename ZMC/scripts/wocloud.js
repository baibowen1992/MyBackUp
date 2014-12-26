 //
 // The software you have just accessed, its contents, output and underlying
 // programming code are the proprietary and confidential information
 // Incorporated.  Only specially authorized employees, agents or licensees
 // may access and use this software.  If you have not been given
 // specific written permission by Zmanda, any attempt to access, use, decode,
 // modify or otherwise tamper with this software will subject you to civil
 // liability and/or criminal prosecution to the fullest extent of the law.
 // $Id: wocloud.js 36080 2013-03-26 16:52:58Z anuj $ */

// magic allowing use of CSS selectors like ".ieonly selector { css style; }"
if (/MSIE\s(\d)/.test(navigator.userAgent)) document.documentElement.className += ' ' + 'ieonly';

function submit()
{
	document.form1.submit();
}

function MM_swapImgRestore() { //v3.0
	var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_preloadImages() { //v3.0
		var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
	var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
	if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_findObj(n, d) { //v4.01
		var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
		d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
		if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
		for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
	if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
	var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
	if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}

MM_preloadImages(
	'/images/navigation/backup_over.gif',
	'/images/navigation/backup_down.gif',
	'/images/navigation/verify_over.gif',
	'/images/navigation/verify_down.gif',
	'/images/navigation/monitor_over.gif',
	'/images/navigation/monitor_down.gif',
	'/images/navigation/report_over.gif',
	'/images/navigation/report_down.gif',
	'/images/navigation/admin_over.gif',
	'/images/navigation/admin_down.gif',
	'/images/navigation/restore_over.gif',
	'/images/navigation/restore_down.gif',
	'/images/icons/icon_download_up.gif',
	'/images/icons/icon_download_over.gif'
)

function gebi(o)
{
	if (typeof(o) == 'string')
		return document.getElementById(o)
	if (o.hasChildNodes)
		return o;
	if ((typeof(zmcRegistry) != 'undefined') && zmcRegistry['debug'])
		alert("ZMC Debug: gebi() argument not string or object: " + zmc_printr(o));
	return null;
}

function zmc_print(o)
{
	alert(zmc_printr(o, 1))
}

function zmc_display(o, d)
{
	gebi(d).innerHTML = zmc_printr(o, 1)
}

function zmc_printr(obj_or_id, d)
{
	if (d == 3) return;
	if (obj_or_id === null)
		return 'null'
	var o = gebi(obj_or_id)
	if (o === null)
		return 'null'
	var i
	var msg = ''
	var rs = '\t  \t'
	if (typeof o === 'string')
		return 'string = ' + o
	if (typeof o == 'HTMLCollection' || typeof o === 'array' || typeof o === 'NodeList')
		for(i=0; i < o.length; i++)
		{
			msg = msg + (o.item(i) + '').substring(0,24) + '\t= ' + i + rs
			if (rs == '\t  \t')
				rs = ' ; \n<br />'
			else
				rs = '\t  \t'
		}
	else
		for(i in o)
		{
			if ((typeof o[i]) === 'object')
				msg = msg + (typeof o[i]) + '\t = ' + i + rs
				//msg = msg + zmc_printr(o[i], d+1) + '\t= object ' + i + '\n'
			else if ((typeof o[i]) != 'function' && i.indexOf('DOM_') != 0)
				//msg = msg + i + '\t= ' + '\n'
				msg = msg + (o[i] + '').substring(0,24) + '\t= ' + i + rs
			//if (rs == '\t  \t')
				rs = ' ; \n<br />'
			//else rs = '\t  \t'
		}
	return msg
}

function noBubble(e)
{
	e = e||window.event;
	if (e == undefined)
		return
	if (e.stopPropagation)
		e.stopPropagation();
	e.cancelBubble = true;
}

function disableButton(o, text)
{
	o.style.backgroundColor = 'darkgray'
	o.style.color = '#D1D1D1';
	if (text)
		setTimeout(function() { o.value = text }, 1000);
}

function tdBoxClick(o, event)
{
	noBubble(event)
	var list = o.getElementsByTagName('input')
	list[0].checked = !list[0].checked
	boxClick(list[0])
}

function boxClick(o, event)
{
	if (o && o.name && (o.name.indexOf('selected_ids_lm') != -1))
	{
		var label = gebi((o.name.toString()).replace('selected_ids_lm', 'label'))
		if (o.checked)
		{
			label.origValue = label.value
			var akey = o.name.toString();

			if (zmcRegistry['barcodes_enabled']){
				label.value = zmcRegistry['backupSetName'] + '-' + gebi('slot_barcode_'+akey.slice(akey.lastIndexOf('[') +1, -1)).innerHTML
			}else
				label.value = zmcRegistry['backupSetName'] + '-' + akey.slice(akey.lastIndexOf('[') +1, -1)
		}
		else
			label.value = label.origValue
	}
	noBubble(event)
	YAHOO.zmc.utils.enable_datatable_buttons(o)
}

function onEnterSubmit(button, e)
{
	var keycode = 0;
	if (e)
		keycode = e.which;
	else if (window.event)
		keycode = window.event.keyCode;
	if (keycode == 13)
	{
		gebi(button).click();
		return false;
	}
	return true;
}

String.prototype.repeat = function(str) {return new Array(str+1).join(this);}

if (!Array.prototype.forEach)
{
	Array.prototype.forEach = function(fun, thisp)
	{
		var len = this.length >>> 0;
		if (typeof fun != "function")
			throw new TypeError();

		var thisp = arguments[1];
		for (var i = 0; i < len; i++)
		{
			if (i in this)
				fun.call(thisp, this[i], i, this);
		}
	};
}

//only works on base 10
Number.prototype.base10PaddedString = function(width)
{
	var stringForm = this.toString();
	while(stringForm.length < width)
			stringForm = "0" + stringForm;

	return stringForm;
}

/* Converts an object to an html form with the properties of the objects as input boxes */
Object.prototype.toForm = function(action, method, prefix)
{
	var elementBuilder = function(property, value, form)
	{
		var element = document.createElement('input');
		var name = property;
		element.setAttribute("type", "hidden");
		element.setAttribute("value", value);
		if (prefix)
			name = prefix+"["+name+"]";
		element.setAttribute("name", name);
		form.appendChild(element);
		return form;
	}
	var form = this.injectWithProperties(elementBuilder, document.createElement("form"));
	form.action = action;
	form.method = method;
	return form;
}

Object.prototype.toJSON = function()
{
	return YAHOO.lang.JSON.stringify(this);
}

/* Iterates over the non-function properties of an object and
	passes them to the passed in function. The function is responsible for adding to the
	initial accumulator that is passed in. The current value of the accumulator must be returned by the function */
Object.prototype.injectWithProperties = function(func, accInitial)
{
	var acc = accInitial;
	for(var property in this)
		if (typeof(this[property]) != 'function')
			acc = func(property, this[property], acc);

	return acc;
};

Array.prototype.has=function(element)
{
	for(var i=0; i< this.length; i++)
		if(element == this[i])
			return true;

	return false;
};

/* iterates over the elements of the array passing the element and its index to the function provided as an argument */  
Array.prototype.eachWithIndex = function(func)
{
	for(var i = 0; i < this.length; i++)
		func(this[i], i);
}

/* Iterates over the elements of the array  and passes them to the function
 * specified in the arguments. The function is responsible for adding to the
 * initial accumulator that is passed in. The current value of the accumulator
 * must be returned by the function */
Array.prototype.inject = function(func, accInitial)
{
	/* since each is defined in terms of each with index, slightly faster to use
		each with index directly, and throw away the index */
	var acc = accInitial;
	var injector = function(item, index) { acc = func(item, acc); }
	this.eachWithIndex(injector);
	return acc;
}

/* iterates over the elements of the array passing the element to the function provided as an argument */  
Array.prototype.each = function(func)
{
	var curry = function(item, index) { func(item); }
	this.eachWithIndex(curry)
};

/* iterates over the elements of the array and passes them to the function specified in the argument. The return value of the function
	will be added to an initialy empty array. The output array will ahve the same number of elements as the input array, I.E. the function
	maps(See wikipedia for the mathematical def of a map) the input array onto the output array */

Array.prototype.map = function(func)
{
	var acc = new Array();
	/* we could use each here. It'd be almost identical, however */
	var injector = function(element, acc)
	{
		acc.push(func(element))
		return acc;
	}
	return this.inject(injector, acc);
};

//tells whether or not an item occurs in the array, simple linear search
Array.prototype.has = function(object)
{
	var injector = function(element, acc)
	{
		acc = acc || (element == object);
		return acc;
	}
	return this.inject(injector, false);
};

//iterates over the array, returning an array consisting of those elements for which the passed in function returns true
Array.prototype.find = function(func)
{
	var acc = new Array();
	var injector = function(element, acc)
	{
		if(func(element))
			acc.push(element);

		return acc;
	}
	return this.inject(injector, acc);
};

function mcountdown(secs)
{
	var o = gebi('monitor_countdown')
	if (o) o.innerHTML = '&bull;'.repeat(zmcRegistry['monitor_countdown']--)
	if (zmcRegistry['monitor_countdown'] < 1)
		zmcRegistry['monitor_countdown'] = secs
	setTimeout('mcountdown(' + secs + ')', 1000 )
}

function bandwidth_toggle(){
	var o =gebi('private:bandwidth_toggle');
	if(o.checked == true){  
		gebi('device_property_list:MAX_SEND_SPEED').setAttribute('disabled','disabled');
	    gebi('device_property_list:MAX_RECV_SPEED').setAttribute('disabled','disabled');
		gebi('device_property_list:NB_THREADS_BACKUP').disabled = true;
		gebi('device_property_list:NB_THREADS_RECOVERY').disabled = true;;
	}else{
		gebi('device_property_list:MAX_SEND_SPEED').removeAttribute('disabled');
	    gebi('device_property_list:MAX_RECV_SPEED').removeAttribute('disabled');
		gebi('device_property_list:NB_THREADS_BACKUP').disabled = false;
		gebi('device_property_list:NB_THREADS_RECOVERY').disabled = false;
	}

}

