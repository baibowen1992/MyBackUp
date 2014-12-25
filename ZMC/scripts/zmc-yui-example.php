<?













include '../Common/HeaderAndFooter.php';

ZMC_HeaderFooter::$instance->header("YUI Test", "Testing YUI in ZMC");



ZMC_HeaderFooter::$instance->addYui('zmc-utils', array('dom', 'event'));
ZMC_HeaderFooter::$instance->addYui('zmc-example', array('zmc-utils', 'json'));

?>
<div style="position:absolute; top:200px">
<h1>ZMC YUI Test</h1>
<p>There should be three pop-up windows:</p>
	<p>1. "zmc-utils.js: loaded ok"</p>
	<p>2. "zmc-example.js: loaded ok"</p>
	<p>3. "Json test successful"</p>
<p>After loading, clicking anywhere on the screen should move a small blue square (a small div) to the location of the mouse.</p>


Click to move the square .. using YUI with auto-loaded YUI modules dynamically calculated based on dependencies.
</div>
	<style type="text/css">
	#foo {width:10px; height:10px;background-color:#00f;}
	</style>
	<div id="foo" style="top:400px; position:absolute"></div>

// END TEST

<?
ZMC_HeaderFooter::$instance->close();
