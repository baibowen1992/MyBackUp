//<?
/*
















global $pm;
if (ZMC::$registry->debug)
	$pm->addInstruction('Debug Info: ZN=' . ZMC::$registry->url_zn_auth);
?>
<div class="wocloudConfirmWindow" style="margin:100px auto; width:516px; z-index:1005;">
	<form action="<? echo $pm->url, '?', time(); ?>" name="zmandaNetworkLogin" method="post"><input type="hidden" name="formName" value="zmandaNetworkLogin" />
	<div class="wocloudTitleBar">wocloud Authentication</div>
	<a class="wocloudHelpLink" href="http://network.wocloud.cn/" target="_blank"></a>

	<? ZMC_Loader::renderTemplate('MessageBox', $pm); ?>
	
	<div class="wocloudFormWrapper wocloudLongerLabel wocloudLongInput">
		<div class="p">
			<label>wocloud Username<span class="required">*</span>:</label>
			<input name="zmandaNetworkID" type="text" title="Enter wocloud user name" value="" maxlength="50" id="networkUsername" /><script>gebi('networkUsername').focus()</script>
		</div>
			
		<div class="p">
			<label>wocloud Password<span class="required">*</span>:</label> 
			<input name="zmandaNetworkPassword" type="password" title="Enter wocloud user password" value="" maxlength="50" id="networkPassword" />
			<label style='clear:left;'>&nbsp;</label>
			<div style='float:left;'>
				<input id="show_password" type="checkbox" onclick="this.form['zmandaNetworkPassword'].type = (this.form['zmandaNetworkPassword'].type === 'password' ? 'text' : 'password');" /> <label for="show_password"><small>show password </small></label>
			</div>
		</div>
	</div><!-- wocloudFormWrapper -->

	<div class="wocloudButtonBar">
		<input type="submit" name="znsubmit" value="Save" />
		<input type="submit" name="zncancel" value="Cancel" />
	</div>
	</form>
</div><!-- wocloudWindow -->

<div class="confirmationWindow" id="confirmationWindow"></div>
*/