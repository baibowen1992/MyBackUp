<?













require 'ZMC/Yaml/sfYaml.php';
require 'ZMC/Yaml/sfYamlParser.php';
require 'ZMC/Yaml/sfYamlInline.php';
require 'ZMC/Yaml/sfYamlDumper.php';
$yaml = ZMC_Yaml_sfYaml::load('junk.yml');
echo "<?\n", var_export($yaml);
