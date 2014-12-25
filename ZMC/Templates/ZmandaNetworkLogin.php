<?

















global $pm;
if (ZMC::$registry->debug)
	$pm->addInstruction('Debug Info: ZN=' . ZMC::$registry->url_zn_auth);
?>
<div class="zmcConfirmWindow" style="margin:100px auto; width:516px; z-index:1005;">
	<form action="<? echo $pm->url, '?', time(); ?>" name="zmandaNetworkLogin" method="post"><input type="hidden" name="formName" value="zmandaNetworkLogin" />
	<div class="zmcTitleBar">Zmanda Network Authentication</div>
	<a class="zmcHelpLink" href="http://network.zmanda.com/" target="_blank"></a>

	<? ZMC_Loader::renderTemplate('MessageBox', $pm); ?>
	
	<div class="zmcFormWrapper zmcLongerLabel zmcLongInput">
		<div class="p">
			<label>Zmanda Network Username<span class="required">*</span>:</label>
			<input name="zmandaNetworkID" type="text" title="Enter Zmanda Network user name" value="" maxlength="50" id="networkUsername" /><script>gebi('networkUsername').focus()</script>
		</div>
			
		<div class="p">
			<label>Zmanda Network Password<span class="required">*</span>:</label> 
			<input name="zmandaNetworkPassword" type="password" title="Enter Zmanda Network user password" value="" maxlength="50" id="networkPassword" />
			<label style='clear:left;'>&nbsp;</label>
			<div style='float:left;'>
				<input id="show_password" type="checkbox" onclick="this.form['zmandaNetworkPassword'].type = (this.form['zmandaNetworkPassword'].type === 'password' ? 'text' : 'password');" /> <label for="show_password"><small>show password </small></label>
			</div>
		</div>
	</div><!-- zmcFormWrapper -->

	<div class="zmcButtonBar">
		<input type="submit" name="znsubmit" value="Save" />
		<input type="submit" name="zncancel" value="Cancel" />
	</div>
	</form>
</div><!-- zmcWindow -->

<div class="confirmationWindow" id="confirmationWindow"></div>
