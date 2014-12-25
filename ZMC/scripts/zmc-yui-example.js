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
 // $Id: zmc-yui-example.js 35641 2013-02-25 21:33:37Z anuj $ */

YAHOO.namespace('zmc');
YAHOO.zmc.example = {
	install: function() {
		var move = function(e) {
		YAHOO.util.Dom.setXY('foo', YAHOO.util.Event.getXY(e));
		};

		YAHOO.util.Event.on(document, "click", move);

		YAHOO.log("The example has finished loading; as you interact with it, you'll see log messages appearing here.", "info", "example");
	}
}

YAHOO.zmc.example.install();

var jsonString = '{"productId":1234,"price":24.5,"inStock":true,"bananas":null}';
var prod;
try {
    prod = YAHOO.lang.JSON.parse(jsonString);
	window.alert('Json test successful');
}
catch (e) {
	window.alert('Json test failed');
}

YAHOO.register("zmc-yui-example", YAHOO.zmc.example, {version: "2.5.2", build: "$Revision: 35641 $".substr(10,5)})
d=new Date();
alert('zmc-yui-example.js: loaded ok '+d.toTimeString());
