<?













global $pm;
echo "\n<form method='post' action='$pm->url'>\n";
?>
<div class="zmcContentContainer" style="background-color:#E3EADA">
	<? ZMC::titleHelpBar($pm, 'Getting started with Amanda Enterprise', 'Backup_Set-the_Backbone_of_ZMC"'); ?>
	<div id='zmcLoginContent' class="zmcContentContainer">
		<center>
		<div pane12 style="width:525px; margin:0; float:left; border-right: 1px solid #ADAA9C;">
			<div pane1 style="padding:15px;">
				<fieldset style='margin:20px;'><legend>Create a Storage Device</legend>
					<img src="/images/starter/admin_devices_navigation_aee.png"
						alt="Backup What"
						width="400"
						height="52"
						style="padding:10px;"
					/>
					<div style="padding:15px;">
						<img src="/images/starter/starter-step2.png"
							alt="Create backup set" 
							width="356" 
							height="107" 
							border="0" 
							title="Create backup set" 
							style="padding-right:15px;"
						/>
					</div>
					<p>Create or use an existing device.<br />Backup sets use storage devices to save backups.</p>
				</fieldset>
				<div style='clear:left;'></div>
			</div><!-- pane1 -->
			
			<div pane2 style="border-top: 1px solid #ADAA9C;">
				<fieldset style='margin:20px;'><legend>Create or open an existing backup set</legend>
					<img src="/images/starter/admin_backup_sets_navigation_aee.png"
						alt="Backup What"
						width="400"
						height="52"
						style="padding:10px;"
					/>

					<p>The next step in the backup process requires creating an Amanda backup set.</p>
					<img src="/images/starter/starter-step1.png"
						alt="Create backup set" 
						width="356" 
						height="160" 
						border="0" 
						title="Create backup set" 
						style="padding-top:15px;"
					/>
				</fieldset>

				<div style="clear:left; height:1px; background-color:#ADAA9C;"></div>

				<fieldset style='margin:20px;'><legend>Verification</legend>
					<p>See the &quot;Admin|backup sets&quot; page for a bird's eye view of the health of all backup sets.</p>
					<p>See the &quot;Report|data integrity&quot; page to verify the integrity of complete backups.</p>
					<p>See the &quot;Backup|what&quot; page to verify the health of each backup client.</p>
					<p>The Amanda installation, backup server, backup client configurations, and the backup data can be independently verified. 
						You should verify the Amanda installation and backup set configuration, before activating the backup set, by visiting the Admin|backup sets page.  This page automatically checks for unusual conditions, and displays errors or warnings as needed.
					</p>
				</fieldset>
				<div style='clear:left;'></div>
			</div><!-- pane2 -->
		</div><!-- pane12 -->
		
	  	<div pane3 style="float:right; height:890px; width:443px; padding:15px; border-left: 1px solid white;">
	  		<h2>Edit the backup set</h2>

			<p>A backup set consists of &quot;what&quot;, &quot;where&quot;, &quot;media&quot;, &quot;how&quot;, and &quot;when&quot; you would like to backup your data.</p>

			<fieldset><legend>What to backup?</legend>
				<img src="/images/starter/backup_what_navigation_aee.png"
					alt="Backup What"
					width="400"
					height="52"
					style="padding:10px;"
				/>
				<p><?= ZMC::$registry->tips['backup_what'] ?></p>
			</fieldset>

			<fieldset><legend>Where to store backups?</legend>
				<img src="/images/starter/backup_where_navigation_aee.png"
					alt="Backup What"
					width="400"
					height="52"
					style="padding:10px;"
				/>
				<p>Specify a previously created storage device for your backup set, like a disk, changer, or cloud storage service.</p>
			</fieldset>

			<fieldset><legend>When to start backups?</legend>
				<img src="/images/starter/backup_when_navigation_aee.png"
					alt="Backup Verify"
					width="400"
					height="52"
					style="padding:10px;"
				/>
				<p>Specify the frequency and time to initiate backups for each backup set.</p>
			</fieldset>
	
			<fieldset><legend>Activating Automatic Backups</legend>
				<img src="/images/starter/backup_activation_navigation_aee.png"
					alt="Backup Activate"
					width="400"
					height="52"
					style="padding:10px;"
				/>
				<p>When configuration is complete, and a manually started backup completes successfully, activate the backup set to schedule automatic backups.</p>
			</fieldset>
	
  		</div><!-- pane3 -->

		<div class="zmcButtonBar">
			<div style="position:absolute; left:10px; top:7px;">
				<input name="Dismiss" type="checkbox" value="Dismiss" id="dismissstarter" <? if ($pm->show) echo 'checked="checked"'; ?> />
				<label accesskey="b" for="dismissstarter">Show this dialog at startup</label>
			</div>
			<!-- width is workaround for bug in IE8 -->
			<input style="width:75px" class="zmcCenter" type="submit" name="Begin" id="begin" value="Begin" />
		</div>
	 	</center>
	</div><!-- innerContent zmcContentContainer -->
</div><!-- outerContent zmcContentContainer -->
</form>
