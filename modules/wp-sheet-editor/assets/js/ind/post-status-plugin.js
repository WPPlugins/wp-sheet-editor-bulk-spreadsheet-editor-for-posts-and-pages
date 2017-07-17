jQuery(document).ready(function () {

	if( typeof hot === 'undefined' ){
		return true;
	}
	/**
	 * Disable post status cells that contain readonly statuses.
	 * ex. scheduled posts
	 */
	hot.updateSettings({
		afterLoadData: function (firstTime) {

		},
		cells: function (row, col, prop) {
			var cellProperties = {};

			if (jQuery('#post-data').data('post-type') === 'product' || prop === 'status' ) {
					var cellData = hot.getDataAtCell(row, col);
					if (cellData && typeof cellData === 'string' && cellData.indexOf('vg-cell-blocked') > -1) {
						cellProperties.readOnly = true;
						cellProperties.editor = false;
						cellProperties.renderer = 'html';
						cellProperties.fillHandle = false;
					}
				}


			return cellProperties;
		}
	});
});

