<?


















class ZMC_Splash
{
	







	public static function splashRedirect()
	{
		$box = new ZMC_Registry_MessageBox();
		$logos = array();
		foreach(glob("/opt/zmanda/*/apache2/conf/ssl.conf") as $filename)
		{
			if (strpos($filename, 'zrm'))
			{
				$app = 'zrm';
				$logoFile = '/opt/zmanda/zrm/apache2/htdocs/zmanda-zrm/ZMC';
			}
			else
			{
				$app = 'aee';
				$logoFile = '/opt/zmanda/amanda/ZMC';
			}
			$logoUrl = "/images/login/logo_$app.gif";
			$logoFile .= $logoUrl;
			if (is_readable($logoFile))
			{
				if (ZMC_Startup::getApachePort($box, $overrides, $port, $http, $ignored1, $ignored2, $filename))
					continue;
				if (!empty($port))
					$port = ":$port";

				
				
				$host = explode(':', $_SERVER['HTTP_HOST']); 
				if ($_SERVER['HTTPS'] === 'on') 
					$key = "https://$host[0]$port/";
				else
					$key = "$http://$host[0]$port/";
				$key .= strpos($filename, 'zrm') ? 'Admin/LogInView.php' : "ZMC_Admin_Login?login=$app";
				if (!empty($_REQUEST['last_page'])) 
					$key .= '&last_page=' . ZMC::escape($_REQUEST['last_page']);
				$logos[$key] = $logoUrl;
			}
		}

		if (count($logos) === 1)
		{
			$url = key($logos);
			header("Location: $url");
			setcookie('zmc_cookies_enabled', 'true', 0, '/ZMC_Admin_Login');
			exit;
		}

		$exception = null;
		$processStatus = array();
		ZMC_Loader::renderTemplate('Header', array(
			'tombstone' => 'Splash',
			'subnav' => '',
			'product_datestamp' => ZMC::dateNow(true),
			'title' => 'Welcome to the Zmanda Management Console',
			'short_name' => 'AEE',
		));
		ZMC_Loader::renderTemplate('Login', $box->merge(array(
			'logos' => $logos,
			'url' => '/',
			'app' => 'zmc',
			'last_page' => '',
			'tombstone' => 'Splash',
			'subnav' => '',
		)));
		require 'HeaderFooter.php';
		$hf = new ZMC_HeaderFooter();
		$hf->close();
	}
}
