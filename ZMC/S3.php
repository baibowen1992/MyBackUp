<?













class ZMC_S3
{
	




	public static function check($row, ZMC_Registry_MessageBox $pm)
	{
		$s3dir = ZMC::$registry->s3certs_path;
		$s3fn = $row['devpay_activation_key'];

		if (!file_exists($s3dir) && !mkdir($s3dir))
			throw new ZMC_Exception('Check permissions and ownership of "' . dirname($s3dir) . '".  Unable to create directory: ' . $s3dir);

		if (!empty($s3fn) && (dirname($s3fn) != $s3dir)) 
		{
			
			$s3newfn = $s3dir . DIRECTORY_SEPARATOR . basename($s3fn);
			if (rename($s3fn, $s3newfn) === false)
				return $pm->addError("Check permissions and ownership and move the file '$s3fn' to '$s3newfn'");
			else
				self::saveNew($row, $pm, $s3newfn);
		}
		elseif (!empty($s3fn) && !is_readable($s3fn))
			$pm->addError("Unable to read the S3 certificate '$s3fn' currently used by this backup set.  Please select or upload a different certificate.");
		$certs = glob("$s3dir/*");
		if (isset($_REQUEST['certificate']) || empty($certs) || !empty($_POST['install']))
		{
			self::getCert($pm); 
			self::saveNew($row, $pm);
		}
	}

	public static function getCerts()
	{
		$certs = array();
		foreach(glob(ZMC::$registry->s3certs_path . '/*') as $path)
			$certs[$path] = substr($path, strrpos($path, DIRECTORY_SEPARATOR) +1);
		








		return $certs;
	}

	private static function getS3fn()
	{
		return ZMC::$registry->s3certs_path . DIRECTORY_SEPARATOR . ZMC_BackupSet::getName() . date('.Y-m-d-h-i');
	}

	


	public static function getCert(ZMC_Registry_MessageBox $pm)
	{
		if (!empty($_POST['install']))
		{
			if ($_POST['install'] === 'upload')
			{
				$success = self::uploadCert($pm);
				if ($success !== false)
				{
					$pm->addMessage('整数上传到 "' . self::getS3fn() . '"');
					return;
				}
			}
/*			else
			{
				ZMC_ZmandaNetwork::getAndSave($pm, 'S3', self::getS3fn(), "Unable to install certificate at "
					. ZMC::$registry->s3certs_path . '.  Check directory and file permissions.  Then try again.');
				if (empty($results['errors']))
				{
					if (is_array($results['data'])) 
					{
						foreach($results['data'] as $key => $value)
						{
							if (false === file_put_contents(self::getS3fn() . $key, $value)) 
								return $pm->addError('Unable to save certificate to "' . self::getS3fn() . '"');
						}
					}
					elseif (false === file_put_contents(self::getS3fn(), $results['data']))
						return $pm->addError('Unable to save certificate to "' . self::getS3fn() . '"');
					if (empty($results['errors']))
					{
						$pm->addMessage('Certificate downloaded from wocloud and saved as "' . self::getS3fn() . '"');
						return;
					}
				}
			}
*/
		}
		ZMC_Loader::renderTemplate('AWS_Certificate', $pm);
		ZMC_HeaderFooter::$instance->footer(); 
	}

	




	protected static function uploadCert(ZMC_Registry_MessageBox $pm)
	{
		if ($_FILES['certificate']['size'] > 64000) 
			return $pm->addError('File too large');

		switch ($_FILES['certificate']['error'])
		{
			case UPLOAD_ERR_OK:
				$s3fn = self::getS3fn();
				if (move_uploaded_file($_FILES['certificate']['tmp_name'], "$s3fn.new"))
				{
					if (file_exists($s3fn) && !rename($s3fn, $s3fn . time() . '.bak'))
						return $pm->addError("Unable to archive currently installed certificate. Please rename existing file ($s3fn) and then try again.");
					if (rename("$s3fn.new", $s3fn))
					{
						$pm->addMessage("Installed S3 certificate at $s3fn");
						return true;
					}
					else
						$pm->addError("Unable to install uploaded certificate at $s3fn.  Check directory and file permissions.  Then try again.");
				}
				else
					$pm->addError("Unable to relocate uploaded certificate to install location ($s3fn)");
				break;
			case UPLOAD_ERR_INI_SIZE:
				$pm->addError('The uploaded file exceeds the upload_max_filesize directive ('.ini_get('upload_max_filesize').') in php.ini.');
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$pm->addError('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
				break;
			case UPLOAD_ERR_PARTIAL:
				$pm->addError('The uploaded file was only partially uploaded.');
				break;
			case UPLOAD_ERR_NO_FILE:
				$pm->addError('No file was uploaded.');
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$pm->addError('Missing a temporary folder.');
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$pm->addError('Failed to write file to disk');
				break;
			case UPLOAD_ERR_EXTENSION:
				$pm->addError('File upload stopped by extension');
				break;
			default:
				$pm->addError('Unknown file upload error');
		}
		return false;
	}

	


	private static function saveNew(array &$row, ZMC_Registry_MessageBox $pm, $s3fn = null)
	{
		$row['devpay_activation_key'] = ($s3fn ? $s3fn : self::getS3fn());
		if (self::set($row, $pm)) 
			updateBackupWhereInDatabase($row);
	}
	
	
	public static function set(array $row, ZMC_Registry_MessageBox $pm, $deviceName)
	{
throw new ZMC_Exception(__FILE__ . __LINE__ . "review problem code in this function - when/is it invoked? for both old and new style S3 certs? new style are not saved to /etc/zmanda/zmc/s3certs/*!");
		$service = new ZMC_YasumiService_Device();
		$device = $service->read($deviceName);
		$s3fn = $row['devpay_activation_key'];
		if (empty($s3fn))
			return $pm->addError('Unable to save. S3 Amazon Activation Key is not set.');

		
		
		
		if (!is_readable("$s3fn"))
			return $pm->addError("Unable to save. Amazon Web Services certificate file $s3fn does not exist or not readable by amandabackup user");
			

		$lines = file("$s3fn");
		




		if (count($lines) != 3)
		{
			$pm->addError("Unable to save. Certificate ($s3fn) appears to be corrupt (not 3 lines). Content follows:");
			$pm->addMessage(join("\n", $lines));
			unlink($s3fn); 
			return false;
		}

		$token = array();
		foreach ($lines as $i => $line)
			$tokens[$i] = preg_split('/\s+/', $line);

		foreach (array('"S3_USER_TOKEN"', '"S3_ACCESS_KEY"', '"S3_SECRET_KEY"') as $prop)
		{
			$token = array_shift($tokens);
			if (($token[0] != 'device_property') || ($token[1] != $prop) || (empty($token[2])))
			{
				$pm->addError("Unable to save. Certificate ($s3fn) appears to be corrupt.  Content follows:");
				$pm->addMessage(join("\n", $token));
				return false;
			}
			ZMC::quit($device);
			
		}
		$service->merge($deviceName, $device);
		return true;
	}
}
