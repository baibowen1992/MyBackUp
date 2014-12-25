<?













global $pm;
ZMC_Loader::renderTemplate('MessageBox', $pm);
?>

<div class="contentContainer">
	<div id="adminAddUser" class="exteriorContainer">
		<div class="headingBackground">
			<div class="contentHeadingTitle">
				Install Amazon S3 AWS Certificate
			</div>
		</div>
		<div class="contentHeadingHelp">
			<a href="<?= ZMC::$registry->wiki ?>AWS_Certificate.php" target="_blank">
			<img border="0" alt="Edit ZMC User Help" title="Edit ZMC User Help" src="/images/icons/icon_help.png"/>
			</a>
		</div>
		<div class="formWrapper">
			<form method="post" action="<?= $pm->url ?>">
			<br/>
			<p>
				<label for="downloadCheckbox">
					<input type="radio" name="install" value="download" checked id="downloadCheckbox" />
					Download from Zmanda Network
				</label>
			</p>
			<div style='margin:15px'>
				<p>If the above does not succeed,</p>
				<p>you may login to Zmanda Network,</p>
				<p>manually download the certificate,</p>
				<p>and then upload it to your ZMC server:</p>
			</div>
			<p>
				<label for="uploadCheckbox">
					<input type="radio" name="install" value="upload" id="uploadCheckbox" /> Upload AWS Certificate: 
					<input type="hidden" name="MAX_FILE_SIZE" value="64000" />
					<input name="certificate" type="file" onClick="gebi('uploadCheckbox').checked = true " />
				</label>
			</p>
			<div id="adminButtonBarLeft" class="buttonBar">
				<input type="submit" name="submit" value="Install" />
			</div>
			</form>
		</div><!-- formWrapper -->
	</div><!-- exteriorContainer -->
</div><!-- contentContainer -->
