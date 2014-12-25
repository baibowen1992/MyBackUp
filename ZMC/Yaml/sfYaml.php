<?
















class ZMC_Yaml_sfYaml
{
  static protected
    $spec = '1.2';

  




  static public function setSpecVersion($version)
  {
    if (!in_array($version, array('1.1', '1.2')))
    {
      throw new InvalidArgumentException(sprintf('Version %s of the YAML specifications is not supported', $version));
    }

    self::$spec = $version;
  }

  




  static public function getSpecVersion()
  {
    return self::$spec;
  }

  

















  public static function load($input)
  {
    $file = '';

    
    if (strpos($input, "\n") === false && is_file($input))
    {
      $file = $input;

      ob_start();
      $retval = include($input);
      $content = ob_get_clean();

      
      $input = is_array($retval) ? $retval : $content;
    }

    
    if (is_array($input))
    {
      return $input;
    }

    $yaml = new ZMC_Yaml_sfYamlParser();

    try
    {
      $ret = $yaml->parse($input);
    }
    catch (Exception $e)
    {
      throw new InvalidArgumentException(sprintf('Unable to parse %s: %s', $file ? sprintf('file "%s"', $file) : 'string', $e->getMessage()));
    }

    return $ret;
  }

  










  public static function dump($array, $inline = 2)
  {
    $yaml = new ZMC_Yaml_sfYamlDumper();

    return $yaml->dump($array, $inline);
  }
}






function echoln($string)
{
  echo $string."\n";
}
