<?












echo "<style> 
.zmcMsgBox {
	    background-color: #CCCCCC;
		position: relative;
		display: none;
		float: left;
		width:100%;
}

.zmcMessageBox {
    background-color: #E3EADA;
	border: 0px solid #5C706E;
	margin: 0px;
	min-height: 400px;
	max-height: 100%;
	width: 100%;
}
,zmcMsgWarnErr{

}
.zmcUserMessages, .zmcUserInstructions, .zmcUserErrors, .zmcUserWarnings, .zmcUserDetails, .zmcUserInternalErrors {
    border:dotted #666666 0px;
	float:left;
	margin:0px;
	position:relative;
	width: 100% !important;
}

</style>";

global $pm;
if(!empty($_POST) && $_POST['Begin'] === "Ok"){
	if(isset($_POST['last_page']) && $_POST['last_page'] != '/ZMC_Installcheck'){
		if(preg_match('/\/ZMC_Installcheck/', $_POST['last_page']) || preg_match('/ZMC_Admin_Login/', $_POST['last_page']))
			ZMC::headerRedirect("/ZMC_Admin_BackupSets");
		ZMC::headerRedirect($_POST['last_page']);
	}
	else
		ZMC::headerRedirect("/ZMC_Admin_BackupSets");
}
echo "\n<form method='post' action='$pm->url'>\n";
?>

	</div><!-- zmcFormWrapper -->
	<div class="zmcButtonBar">
			<input type="hidden" name="last_page" id="last_page" value="<?=($_SESSION['last_page'] != null)? $_SESSION['last_page']: "/ZMC_Admin_BackupSets";?>" />
			<input style="width:75px" class="zmcCenter" type="submit" name="Begin" id="begin" value="Ok" />

	</div>
</div><!-- zmcLeftWindow -->

</form>
