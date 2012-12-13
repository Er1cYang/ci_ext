<?php
namespace ci_ext\web\widgets\gridview;
class DataColumn extends GridColumn {
	public function renderBody($row) {
		return $row->{$this->name};
	}
}

?>