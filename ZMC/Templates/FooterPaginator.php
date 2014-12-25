<?



















global $pm;
?>

<div class="footerPagination">
<?
echo '&nbsp;(', $pm->row_count, '/', $pm->found_count, ')';
if ($pm->page_count > 1)
{
	if (!$pm->short)
	{
		if ($pm->currentPage != 1) 
			echo '<a href="', $pm->url, '?offset_' . $pm->sortKey . '=' . ($pm->offset - $pm->rows_per_page) .
				'&amp;np=' . $pm->page_count . '">&lt;&lt;&nbsp;Previous</a> ';

		echo " (page $pm->currentPage of $pm->page_count) ";

		if ($pm->currentPage != $pm->page_count) 
			echo '<a href="', $pm->url, '?offset_' . $pm->sortKey . '=' . ($pm->offset + $pm->rows_per_page) .
				'&amp;np=' . $pm->page_count . '">Next&nbsp;&gt;&gt;</a> ';
	}
?>
	Page:
	<select
		name='goto_page_<?= $pm->sortKey ?>'
		style='width:42px; float:none;'
		onchange='this.form.submit()'
	>
<?
	for ($pageIndex = 1; $pageIndex <= $pm->page_count; $pageIndex++)
	{
		echo "<option value='$pageIndex' ";
		if ($pageIndex == $pm->currentPage)
			echo " selected='selected' ";
		echo ">$pageIndex</option>";
	}
?>
	</select>
<?}?>
	Rows:
	<select
		name='rows_per_page_<?= $pm->sortKey ?>'
		style='width:58px; float:none;'
		onchange='this.form.submit()'
	>
<?
	foreach(array(5, 10, 15, 20, 25, 50, 100, 200, 500, 1000) as $count)
	{
		echo "<option value='$count' ";
		if ($pm->rows_per_page == $count)
			echo " selected='selected' ";
		echo ">$count</option>";
	}
?>
	</select>
	<input type="hidden" name="rows_per_page_orig_<?= $pm->sortKey ?>" value="<?= $pm->rows_per_page ?>" />
</div>
