jQuery(document).ready(function (e) {

	if (!jQuery('.be-spreadsheet-wrapper').length) {
		return true;
	}

	/**
	 * Fix toolbar on scroll
	 */
	function sticky_relocate() {
//		console.log('scrolled');
		var window_top = jQuery(window).scrollTop();
		var div_top = jQuery('#vg-header-toolbar-placeholder').offset().top;
		if (window_top > div_top) {
			jQuery('#vg-header-toolbar').css('top', '');
			jQuery('#vg-header-toolbar').addClass('sticky');
//			jQuery('#vg-header-toolbar').css('left', jQuery( '#vg-header-toolbar' ).position().left + 'px' );
			jQuery('#wpadminbar').hide();
			jQuery('#vg-header-toolbar-placeholder').height(jQuery('#vg-header-toolbar').outerHeight());

		} else {
			jQuery('#wpadminbar').show();
//			jQuery('#vg-header-toolbar').css('left', '' );
			jQuery('#vg-header-toolbar').removeClass('sticky');
			jQuery('#vg-header-toolbar-placeholder').height(0);
		}
	}

	if (jQuery('#vg-header-toolbar').length) {
//		jQuery(window).scroll(sticky_relocate);
		jQuery(window).scroll( _throttle(sticky_relocate, 350 ) );
		sticky_relocate();
	}
	
	
	
	// go to the top
	jQuery('#go-top').click(function (e) {
		e.preventDefault();
		var body = jQuery("html, body");
		body.stop().animate({scrollTop: 0}, '300', 'swing', function () {
		});
	});


	// Add #ohsnap element, which contains the user notifications
	jQuery('body').append('<div id="ohsnap" style="z-index: -1"></div>');

	// Init labelauty, which converts checkboxes into switch buttons
	var $wrapper = jQuery('#vgse-wrapper');

	if ($wrapper.length) {
		$wrapper.find(":checkbox").labelauty();
	}

	// Init tooltips
	jQuery('body').find('.tipso').tipso({
		size: 'small',
		tooltipHover: true,
		background: '#444444'

	});

	/* internal variables */
	var
			$container = jQuery("#post-data"),
			$console = jQuery("#responseConsole"),
			$parent = $container.parent(),
			autosaveNotification,
			maxed = false,
			hot;

	// is cells formatting enabled
	if (jQuery('#formato').is(':checked')) {
		format = false;
	} else {
		format = true;
	}

// Initialize select2 on selects
	
setTimeout( function(){
	vgseInitSelect2();	
}, 2000 );

// Handsontable settings
	var handsontableArgs = {
		startRows: vgse_sheet_settings.startRows, //Cantidad de filas
		startCols: vgse_sheet_settings.startCols, //Cantidad de columnas
		rowHeaders: true, //Cabeceras
		stretchH: 'all',
		currentRowClassName: 'currentRow',
		currentColClassName: 'currentCol',
		fillHandle: false, 
		columnSorting: true,
		contextMenu: ['undo', 'redo'],
		autoWrapRow: true,
		autoRowSize: false,
		autoColumnSize: false,
		viewportRowRenderingOffset: 50,
		wordWrap: true,
		colWidths: vgObjectToArray(vgse_sheet_settings.colWidths),
		colHeaders: vgObjectToArray(vgse_sheet_settings.colHeaders),
		columns: columns_format(format),
		minSpareCols: 0,
		minSpareRows: 0,
		width: !jQuery('body').hasClass('wp-admin') ? $container.width() : null,
		height: !jQuery('body').hasClass('wp-admin') ? jQuery(window).height() : null,
		debug: true,
	};

	var customHandsontableArgs = (vgse_sheet_settings.custom_handsontable_args) ? JSON.parse(vgse_sheet_settings.custom_handsontable_args) : {};
	var finalHandsontableArgs = jQuery.extend(handsontableArgs, customHandsontableArgs);

	hot = new Handsontable($container[0], finalHandsontableArgs);

	window.hot = hot;

	/**
	 * Load initial posts
	 */
	$parent.find('button[name=load]').trigger('click');
	$parent.find('button[name=load]').click(function () {
		var nonce = jQuery('.remodal-bg').data('nonce');

		beLoadPosts({
			post_type: $container.data('post-type'),
			nonce: nonce
		});

	}).click(); // execute immediately

	/**
	 * Save changes
	 */
	// Close modal when clicking the cancel button
	jQuery('.bulk-save.remodal').find('.remodal-cancel').click(function (e) {
		var modalInstance = jQuery('[data-remodal-id="bulk-save"]').remodal();
		modalInstance.close();
		jQuery('body').scrollLeft(0)
	});
	/**
	 * Change from "saving" state to "confirm before saving" state after closing the modal
	 */
	jQuery('.bulk-save.remodal .bulk-saving-screen').find('.remodal-cancel').click(function (e) {
		jQuery('body').scrollLeft(0)
		
		var $button = jQuery(this);
		var $modal = $button.parents('.remodal');

		$modal.find('.be-saving-warning').show();
		$modal.find('.bulk-saving-screen').hide();
		$modal.find('#be-nanobar-container').empty();
		$button.addClass('hidden');
		$modal.find('.response').empty();
	});
	/**
	 * Change from "confirm before saving" state to "saving" on save modal
	 */
	jQuery('.bulk-save.remodal').find('.remodal-confirm').click(function (e) {
		var $button = jQuery(this);
		var $modal = $button.parents('.remodal');

		$modal.find('.be-saving-warning').show();
		$modal.find('.bulk-saving-screen').hide();
		$modal.find('#be-nanobar-container').empty();
		$modal.find('.response').empty();
	});
	/**
	 * Save changes - Start saving
	 */
	jQuery('body').find('.be-start-saving').click(function (e) {
		e.preventDefault();

		// Hide warning and start saving screen

		var $warning = jQuery(this).parents('.be-saving-warning');
		var $progress = $warning.next();

		$progress.find('.be-loading-anim').show();
		$warning.fadeOut();
		$progress.fadeIn();

		console.log($warning);
		console.log($progress);


		var nonce = jQuery('.remodal-bg').data('nonce');


		// Init progress bar
		var options = {
			classname: 'be-progress-bar',
			id: 'be-progress-bar',
			target: document.getElementById('be-nanobar-container')
		};

		var nanobar = new Nanobar(options);

		// Get posts that need saving
		var fullData = hot.getSourceData();

		fullData = beGetModifiedItems(fullData, window.beOriginalData);

		console.log(fullData);
		console.log(!fullData);

		// No posts to save found
		if (!fullData.length) {

			jQuery($progress).find('.response').append('<p>' + vgse_sheet_settings.texts.no_changes_to_save + '</p>');
			loading_ajax({estado: false});

			$progress.find('.remodal-cancel').removeClass('hidden');
			$progress.find('.be-loading-anim').hide();

			setFormSubmitting();
			return true;
		}

		// Start saving posts, start ajax loop
		beAjaxLoop({
			totalCalls: Math.ceil(fullData.length / parseInt(vgse_sheet_settings.save_posts_per_page)),
			url: ajaxurl,
			dataType: 'json',
			method: 'POST',
			data: {
				'data': [],
				'post_type': $container.data('post-type'),
				'action': 'bep_save_data',
				'nonce': nonce,
				'filters': (jQuery('body').data('be-filters')) ? jQuery('body').data('be-filters') : ''
			},
			prepareData: function (data, settings) {
				var dataParts = fullData.chunk(parseInt(vgse_sheet_settings.save_posts_per_page));

				data.data = dataParts[ settings.current - 1 ];

				return data;
			},
			onSuccess: function (res, settings) {


				// if the response is empty or has any other format,
				// we create our custom false response
				if (res.success !== true && !res.data) {
					res = {
						data: {
							message: vgse_sheet_settings.texts.http_error_try_now
						},
						success: false
					};
				}

				// If error
				if (!res.success) {

					// show error message
					jQuery($progress).find('.response').append('<p>' + res.data.message + '</p>');

					// Ask the user if he wants to retry the same post
					var goNext = confirm(res.data.message);

					// stop saving if the user chose to not try again
					if (!goNext) {
						jQuery($progress).find('.response').append(vgse_sheet_settings.texts.saving_stop_error);
						jQuery('.bulk-saving-screen .response').scrollTop(jQuery('.bulk-saving-screen .response')[0].scrollHeight);
						return false;
					}
					// reset pointer to try the same batch again
					settings.current = 0;
					jQuery('.bulk-saving-screen .response').scrollTop(jQuery('.bulk-saving-screen .response')[0].scrollHeight);
					return true;
				}

				nanobar.go(settings.current / settings.totalCalls * 100);


				// Display message saying the number of posts saved so far
				var updated = (parseInt(vgse_sheet_settings.save_posts_per_page) * settings.current > fullData.length) ? fullData.length : parseInt(vgse_sheet_settings.save_posts_per_page) * settings.current;
				var text = vgse_sheet_settings.texts.paged_batch_saved.replace('{updated}', updated);
				var text = text.replace('{total}', fullData.length);
				jQuery($progress).find('.response').append('<p>' + text + '</p>');

				// is complete, show notification to user, hide loading screen, and display "close" button
				if (settings.current === settings.totalCalls) {
					jQuery($progress).find('.response').append('<p>' + vgse_sheet_settings.texts.everything_saved + '</p>');

					loading_ajax({estado: false});


					notification({mensaje: vgse_sheet_settings.texts.everything_saved});

					$progress.find('.remodal-cancel').removeClass('hidden');
					$progress.find('.be-loading-anim').hide();

					setFormSubmitting();

					// Reset original data cache, so the modified cells that we save are not considered modified anymore.
					window.beOriginalData = jQuery.extend(true, {}, hot.getSourceData() );
				} else {
 
				}

				// Move scroll to the button to show always the last message in the saving status section
				setTimeout(function () {
					jQuery('.bulk-saving-screen .response').scrollTop(jQuery('.bulk-saving-screen .response')[0].scrollHeight);
				}, 600);

				return true;
			}});
	});

	/**
	 * Save image cells, single image
	 */
	if (typeof wp !== 'undefined' && wp.media) {
		jQuery('body').delegate('.set_custom_images:not(.multiple)', 'click', function (e) {
			e.preventDefault();
			loading_ajax({estado: true});
			var button = jQuery(this);
			var $cell = button.parent('td');
			var cellCoords = hot.getCoords($cell[0]);
			console.log(hot.getDataAtCell(cellCoords.row, cellCoords.col));
			var scrollLeft = jQuery('body').scrollLeft();
			var id = button.data('id');
			var key = button.data('key');
			var type = button.data('type');
			var gallery = [];
			
			var scrollTop = jQuery(document).scrollTop();
			var currentInfiniteScrollStatus = jQuery('#infinito').prop('checked');
				jQuery('#infinito').prop('checked', false);
			
			media_uploader = wp.media({
				frame: "post",
				state: "insert",
				multiple: false
			});
			
// Allow to save images by URL
media_uploader.state('embed').on( 'select', function() {
				var state = media_uploader.state(),
					type = state.get('type'),
					embed = state.props.toJSON();

				embed.url = embed.url || '';

				console.log(embed);
				console.log(type);
				console.log(state);
				
				if( type === 'image' && embed.url){					
					// Guardar img					
					beSendImageIdToWP( embed.url, id, key, type, cellCoords );
				}
				
				
				
			} );
			
			media_uploader.on('close', function () {
				jQuery('body').scrollLeft(scrollLeft);
				jQuery(window).scrollTop( scrollTop );
				jQuery('#infinito').prop('checked', currentInfiniteScrollStatus);
			});
			media_uploader.on("insert", function(){
				jQuery('body').scrollLeft(scrollLeft);
				
				var length = media_uploader.state().get("selection").length;
				var images = media_uploader.state().get("selection").models
				
				console.log(images);
				if( ! images.length ){
					return true;
				}
				for (var iii = 0; iii < length; iii++) {
					gallery.push(images[iii].id);
				}
				
				beSendImageIdToWP( gallery, id, key, type, cellCoords );
			});
			media_uploader.open();
			loading_ajax({estado: false});
			return false;
		});
	}

	/**
	 * Save image cells, multiple images
	 */
	if (typeof wp !== 'undefined' && wp.media) {
		jQuery('body').delegate('.set_custom_images.multiple', 'click', function (e) {
			e.preventDefault();

			loading_ajax({estado: true});
			var button = jQuery(this);
			var $cell = button.parent('td');
			var cellCoords = hot.getCoords($cell[0]);
			console.log(hot.getDataAtCell(cellCoords.row, cellCoords.col));
			var scrollLeft = jQuery('body').scrollLeft();
			var id = button.data('id');
			var key = button.data('key');
			var type = button.data('type');
			var gallery = [];
			
			var scrollTop = jQuery(document).scrollTop();
			var currentInfiniteScrollStatus = jQuery('#infinito').prop('checked');
				jQuery('#infinito').prop('checked', false);

			media_uploader = wp.media({
				frame: "post",
				state: "insert",
				multiple: true
			});

// Allow to save images by url
media_uploader.state('embed').on( 'select', function() {
				var state = media_uploader.state(),
					type = state.get('type'),
					embed = state.props.toJSON();

				embed.url = embed.url || '';

				console.log(embed);
				console.log(type);
				console.log(state);
				
				if( type === 'image' && embed.url){					
					// Guardar img					
					beSendImageIdToWP( embed.url, id, key, type, cellCoords );
				}
				
				
				
			} );

			media_uploader.on('close', function () {
				jQuery('body').scrollLeft(scrollLeft);
								jQuery(window).scrollTop( scrollTop );
				jQuery('#infinito').prop('checked', currentInfiniteScrollStatus);
			});
			media_uploader.on("insert", function () {
				jQuery('body').scrollLeft(scrollLeft);

				var length = media_uploader.state().get("selection").length;
				var images = media_uploader.state().get("selection").models
				console.log(images);
				for (var iii = 0; iii < length; iii++) {
					gallery.push(images[iii].id);
				}

				beSendImageIdToWP( gallery, id, key, type, cellCoords );
			});
			media_uploader.open();
			loading_ajax({estado: false});
			return false;
		});
	}

	/**
	 * Preview image on image cells, single image
	 */
	jQuery('body').delegate('.view_custom_images:not(.multiple)', 'click', function () {
		loading_ajax({estado: true});
		var element = jQuery(this);
		post_id = element.data('id');
		var nonce = jQuery('.remodal-bg').data('nonce');
		var key = element.data('key');
		var type = element.data('type');
		var $setButton = element.siblings('.set_custom_images');
		var localValue = $setButton.data('images');
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {id: post_id, action: "get_image_preview", nonce: nonce, key: key, type: type, localValue: localValue },
			dataType: 'json',
			success: function (response) {
				loading_ajax({estado: false});

				if (response.success) {
					jQuery('div[data-remodal-id=image] .modal-content').html(response.data.message);
					jQuery('[data-remodal-id=image]').remodal();
				} else {
					notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
				}
			}
		});
	});

	/**
	 * Preview image on image cells, multiple images
	 */
	jQuery('body').delegate('.view_custom_images.multiple', 'click', function () {
		loading_ajax({estado: true});
		var element = jQuery(this);
		post_id = element.data('id');
		var nonce = jQuery('.remodal-bg').data('nonce');
		var key = element.data('key');
		var type = element.data('type');
		var $setButton = element.siblings('.set_custom_images');
		var localValue = $setButton.data('images');
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {id: post_id, action: "get_gallery_preview", nonce: nonce, key: key, type: type, localValue: localValue},
			dataType: 'json',
			success: function (response) {
				loading_ajax({estado: false});

				if (response.success) {
					jQuery('div[data-remodal-id=image] .modal-content').html(response.data.message);
					jQuery('[data-remodal-id=image]').remodal();
				} else {
					notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
				}
			}
		});
	});

	/**
	 * Move to next post on tinymce cells modal
	 */
	jQuery('button.siguiente').click(function () {
		var element = jQuery(this);
		var pos = element.data('pos');
		var $remodalWrapper = element.parents('.remodal-wrapper');
		var key = $remodalWrapper.find('.remodal-confirm.guardar-popup-tinymce').data('key');
		jQuery('.btn-popup-content.button-tinymce-' + key).eq(pos).trigger('click');
	});

	/**
	 * Move to previous post on tinymce cells modal
	 */
	jQuery('button.anterior').click(function () {
		var element = jQuery(this);
		var pos = element.data('pos');
		var $remodalWrapper = element.parents('.remodal-wrapper');
		var key = $remodalWrapper.find('.remodal-confirm.guardar-popup-tinymce').data('key');
		jQuery('.btn-popup-content.button-tinymce-' + key).eq(pos).trigger('click');
	});
	

	/**
	 * Open tinymce cell modal
	 */
	jQuery('body').delegate('.btn-popup-content', 'click', function () {
		loading_ajax({estado: true});
		var element = jQuery(this);
		var post_id = element.data('id');
		var key = element.data('key');
		var type = element.data('type');
		var pos = element.parents('tr').index();
		var length = element.parents('tbody').find('tr').length;
		var nonce = jQuery('.remodal-bg').data('nonce');


		// Display or hide the unnecesary navigation buttons.
		// If first post, hide "previous" button.
		// If last post, hide "next" button
		if (pos === 0) {
			jQuery('button.anterior').hide();
			jQuery('button.anterior').next('.tipso').hide();
		} else {
			jQuery('button.anterior').show();
			jQuery('button.anterior').next('.tipso').show();
		}
		if (pos === (length - 1)) {
			jQuery('button.siguiente').hide();
			jQuery('button.siguiente').next('.tipso').hide();
		} else {
			jQuery('button.siguiente').show();
			jQuery('button.siguiente').next('.tipso').show();
		}

		jQuery('button.anterior').data('pos', pos - 1);
		jQuery('button.siguiente').data('pos', pos + 1);

		/**
		 * Get post title
		 */
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {pid: post_id, action: "get_wp_post_single_data", nonce: nonce, key: 'post_title', type: 'post_data'},
			dataType: 'json',
			success: function (response) {

				if (response.success) {
					jQuery('.modal-tinymce-editor .post-title-modal span').text(response.data.message).show();
				} else {
					jQuery('.modal-tinymce-editor .post-title-modal').hide();
				}
			}
		});

		/**
		 * Get cell content
		 */
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {pid: post_id, action: "get_wp_post_single_data", nonce: nonce, key: key, type: type},
			dataType: 'json',
			success: function (response) {

				if (response.success) {

					// Add content to tinymce editor
					if (jQuery('.wp-editor-area').css('display') !== 'none') {
						jQuery('.wp-editor-area').empty();
						jQuery('.wp-editor-area').val(response.data.message);
					} else {
						if (document.getElementById('editpost_ifr')) {
							var frame = document.getElementById('editpost_ifr').contentWindow.document || document.getElementById('editpost_ifr').contentDocument;
							frame.body.innerHTML = response.data.message;
						}
					}


					window.originalTinyMCEData = beGetTinymceContent();

					jQuery('.remodal2 .remodal-confirm').data('post_id', post_id);
					jQuery('.remodal2 .remodal-confirm').data('key', key);
					jQuery('.remodal2 .remodal-confirm').data('type', type);
					//console.log(jQuery('.remodal2 .remodal-confirm').data('post_id'));

					jQuery('[data-remodal-id="editor"]').remodal().open();
					loading_ajax({estado: false});

				} else {

					notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
				}
			}
		});
	});

	/**
	 * Save changes on tinymce editor
	 */
	jQuery('.guardar-popup-tinymce').click(function (e) {
		loading_ajax({estado: true});
		var element = jQuery('.remodal2 .remodal-confirm');
//		var element = jQuery(this);
		post_id = element.data('post_id');
		key = element.data('key');
		type = element.data('type');

		// Get tinymce editor content
		var content = beGetTinymceContent();
		var nonce = jQuery('.remodal-bg').data('nonce');

		// Save content
		if (!window.originalTinyMCEData || (window.originalTinyMCEData && content !== window.originalTinyMCEData)) {
			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				data: {post_id: post_id, content: content, action: "save_individual_post", nonce: nonce, key: key, type: type},
				dataType: 'json',
				success: function (response) {
					loading_ajax({estado: false});
					if (response.success) {
						notification({mensaje: response.data.message});
					} else {
						notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
					}
				}
			});
		} else {
			console.log('Existing tinymce content is the same, not saved');
			loading_ajax({estado: false});
		}
	});

	/**
	 * Load more posts in the spreadsheet
	 */
	$parent.find('button[name=mas]').click(function () {
		if (jQuery('#formato').is(':checked')) {
			format = true;
		} else {
			format = false;
		}
		var nonce = jQuery('.remodal-bg').data('nonce');

		beLoadPosts({
			post_type: $container.data('post-type'),
			paged: Math.ceil(hot.countRows() / vgse_sheet_settings.posts_per_page) + 1,
			nonce: nonce
		}, function (response) {

			if (response.success) {			
	vgAddRowsToSheet( response.data );
			
				loading_ajax({estado: false});
				notification({mensaje: vgse_sheet_settings.texts.posts_loaded})
				//Para detener el scroll mientras se ejecuta otro y volver a activarlo
				scrroll = true;
			} else {

				loading_ajax({estado: false});
				notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
			}
		});
	});


	/**
	 * Init infinite scroll
	 */
	var contenedor = jQuery('#post-data');
	var cont_offset = contenedor.offset();
	var scrroll = true;
	var countRows = hot.countRows();
	jQuery(window).on('scroll',  _throttle(function () {
		console.log('scrolled2');
		if (jQuery('#infinito').is(':checked') && countRows < vgse_sheet_settings.total_posts) {
			if ((jQuery(window).scrollTop() + jQuery(window).height() == jQuery(document).height()) && scrroll === true && scrollDown()) {
				jQuery('button[name="mas"]').trigger('click');
				scrroll = false;
			}
		}
	}, 500));

	/**
	 * Change cell formatting setting
	 * @param boolean active
	 * @returns boolean
	 */
	function columns_format(active) {		
		if (active === true) {
			var defaultColumns = vgse_sheet_settings.columnsFormat
		} else {
			var defaultColumns = vgse_sheet_settings.columnsUnformat
		}

		return defaultColumns;

	}

	/**
	 * Update cells formatting = change to plain text and viceversa
	 */
	jQuery('#formato').click(function () {
		if (jQuery(this).is(':checked')) {
			format = false;
		} else {
			format = true;
		}
		//console.log(format);

		var defaultColumns = columns_format(format);

		if (typeof vgseColumnsVisibilityUpdateHOT === 'function') {
			vgseColumnsVisibilityUpdateHOT(defaultColumns, vgObjectToArray(vgse_sheet_settings.colHeaders), vgObjectToArray(vgse_sheet_settings.colWidth), 'softUpdate');
			
		} else {
			hot.updateSettings({
				columns: columns_format(format)
			});
		}	
	});

	/**
	 * Update posts count on spreadsheet
	 */
	setInterval(function () {
		var total = hot.countRows();
		jQuery('input[name="visibles"]').val(total);
	}, 1000);

	/**
	 * Add new rows to spreadsheet
	 */
	jQuery("#addrow").click(function () {
		var nonce = jQuery('.remodal-bg').data('nonce');
		var post_type = jQuery('#post_type_new_row').val();
		var rows = (jQuery(this).next('.number_rows').length && jQuery(this).next('.number_rows').val()) ? parseInt(jQuery(this).next('.number_rows').val()) : 1;
		loading_ajax({estado: true});

		// Create posts as drafts
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {action: "insert_individual_post", nonce: nonce, post_type: post_type, rows: rows},
			dataType: 'json',
			success: function (res) {

console.log(res);
				if (res.success) {
					// Add rows to spreadsheet							
	vgAddRowsToSheet( res.data.message, 'prepend' );
	
							loading_ajax({estado: false});
							notification({mensaje: vgse_sheet_settings.texts.new_rows_added});
							
							// Scroll up to the new rows
							jQuery(window).scrollTop(jQuery('.be-spreadsheet-wrapper').offset().top - jQuery('#vg-header-toolbar').height() - 20);
				} else {
					loading_ajax({estado: false});
					notification({mensaje: res.data.message, tipo: 'error', tiempo: 60000});
				}
			}
		});
	});

	jQuery('#addrow2').click(function () {
		jQuery('#addrow').trigger('click');
	});

});


/**
 * Verify we´re scrolling vertically, not horizontally
 */
var lastScrollTop = 0;
function scrollDown() {
	var st = jQuery(window).scrollTop();
	if (st > lastScrollTop) {
		down = true;
	} else {
		down = false;
	}
	lastScrollTop = st;
	return down;
}


/**
 * Display warning before closing the page to ask the user to save changes
 */
var formSubmitting = false;
var setFormSubmitting = function () {
	formSubmitting = true;
};

jQuery(window).on("beforeunload", function () {
	if (formSubmitting || ! jQuery( '.be-spreadsheet-wrapper' ).length ) {
		return undefined;
	}
	return vgse_sheet_settings.texts.save_changes_on_leave;
});


jQuery(document).ready(function () {
	var $quickSetupContent = jQuery('.quick-setup-page-content');

	if (!$quickSetupContent.length) {
		return true;
	}

	$quickSetupContent.find('.save-all-trigger').click(function (e) {
		e.preventDefault();
		var $allTrigger = jQuery(this);
		loading_ajax({estado: true});

				// disable quick setup screen
				var $firstForm = $quickSetupContent.find('.save-trigger').first().parents('form');
				jQuery.post( $firstForm.attr('action'), {
					action: 'vg_disable_quick_setup',
					nonce: $firstForm.find('[name="nonce"]').val(),
				}, function( response ){
					$allTrigger.data('saved', 'yes');
				});
				
		$quickSetupContent.find('.save-trigger').each(function () {
			jQuery(this).trigger('click');
		});

		var savedCount = 0;
		var savedNeeded = $quickSetupContent.find('.save-trigger,.save-all-trigger').length;

		var intervalId = setInterval(function () {
			var $saved = $quickSetupContent.find('.save-trigger,.save-all-trigger').filter(function () {
				return jQuery(this).data("saved") === 'yes';
			});
			// finished saving all forms.
			if ($saved.length === savedNeeded) {
				clearInterval( intervalId );
				window.location.reload();
			}
		}, 800);
	});

	$quickSetupContent.find('.save-trigger').click(function (e) {
		e.preventDefault();
		var $button = jQuery(this);

		var $form = $button.parents('form');

		jQuery.post($form.attr('action'), $form.serializeArray(), function (response) {
			$button.data('saved', 'yes');
		});

	});
});


jQuery(document).ready(function () {

	// Submit formulas modal form 
	jQuery('body').on('click', '.form-submit-outside', function (e) {
		e.preventDefault();

		jQuery(this).parents('.remodal').find('form').submit();
	});


	

	// Disable infinite scroll when opening modals
	jQuery(document).on('opened', '.remodal', function () {
		console.log('Modal is opened');
		// Save the existing scroll position, and disable infinite scroll to
		// avoid loosing the scroll position and loading more posts while it´s opened.
		var scrollTop = jQuery(document).scrollTop();
		var currentInfiniteScrollStatus = jQuery('#infinito').prop('checked');
		jQuery('#infinito').prop('checked', false);
		jQuery('body').data('temp-status', currentInfiniteScrollStatus).data('temp-scrolltop', scrollTop);


		var scrollLeft = jQuery('body').scrollLeft();
		jQuery('body').data('temp-scrollleft', scrollLeft);
	});
	jQuery(document).on('closed', '.remodal', function () {
		console.log('Modal is closed');
		var scrollTop = jQuery('body').data('temp-scrolltop');
		var scrollLeft = jQuery('body').data('temp-scrollleft');
		var scrollInfinito = jQuery('body').data('temp-status');

		if (scrollTop) {
			jQuery(window).scrollTop(scrollTop);
		}
		if (scrollLeft) {
			jQuery('body').scrollLeft(scrollLeft);
		}
		if (scrollInfinito) {
			jQuery('#infinito').prop('checked', scrollInfinito);
		}	
	});
});


// handsontable cells
jQuery(document).ready(function () {

	// Open modal
	jQuery('body').on('click', '.button-handsontable', function (e) {
		e.preventDefault();
		var $button = jQuery(this);
		var buttonData = $button.data();

		var currentRowData = {
			'button': $button,
			'modalSettings': buttonData.modalSettings,
			'existing': buttonData.existing,
		};

		window.vgseWCAttsCurrent = currentRowData;
		var modalInstance = jQuery('.modal-handsontable').remodal().open();
	});

	// Save changes
	jQuery('body').on('click', '.modal-handsontable .save-changes-handsontable', function (e) {
		var $button = jQuery(this);
		var nonce = jQuery('.remodal-bg').data('nonce');
		var data = window.vgseWCAttsCurrent;

		loading_ajax({ estado: true });
		var attrData = hotAttr.getSourceData();

		// cache product data
		if (!data.modalSettings.modal_get_action) {
			data.button.data('existing', attrData);
		}	

		jQuery.post(ajaxurl, {
			action: data.modalSettings.modal_save_action,
			nonce: nonce,
			postId: data.modalSettings.post_id,
			postType: data.modalSettings.post_type,
			data: attrData
		}, function (response) {
			console.log(response);
			loading_ajax({ estado: false });
			jQuery('.modal-handsontable').remodal().close();
		});
	});

// Load modal and spreadsheet
	jQuery(document).on('opened', '.modal-handsontable', function () {
		console.log('Modal is opened');
		var data = window.vgseWCAttsCurrent;

		loading_ajax({ estado: true });

		if (!data) {
			return true;
		}
		var $modal = jQuery('.modal-handsontable');

		// Display post title in modal
		if (!$modal.find('.modal-post-title').length) {
			$modal.find('.modal-general-title').after('<h2 class="modal-post-title"></h2>');
		}
		$modal.find('.modal-post-title').html(data.modalSettings.post_title);
		if (data.modalSettings.modal_title) {
			$modal.find('.modal-general-title').html(data.modalSettings.modal_title);
		}
		if (data.modalSettings.modal_description) {
			$modal.find('.modal-description').html(data.modalSettings.modal_description);
		}

		// Get data for the spreadsheet if necessary
		if (data.modalSettings.modal_get_action) {
			var nonce = jQuery('.remodal-bg').data('nonce');
			jQuery.get(ajaxurl, {
				action: data.modalSettings.modal_get_action,
				nonce: nonce,
				postId: data.modalSettings.post_id
			}).done(function (response) {
				initHandsontable(response.data, data.modalSettings);
			});
		} else {
			var objectData = data.existing;
			initHandsontable(objectData, data.modalSettings);
		}

	});

	// Initialize spreadsheet
	function initHandsontable(data, modalSettings ) {

		if (!data) {
			data = [];
		}

		var columnWidths = modalSettings.handsontable_column_widths[modalSettings.post_type];
		var columnHeaders = modalSettings.handsontable_column_names[modalSettings.post_type];
		var columns = modalSettings.handsontable_columns[modalSettings.post_type];

		var container3 = document.getElementById('handsontable-in-modal');

		if (window.hotAttr) {
			window.hotAttr.destroy();
		}

		var responseData;		
		if (data.custom_handsontable_args) {
			responseData = data.data;
		} else {
			responseData = data;
		}


		var cellHandsontableArgs = {
			data: responseData,
			minSpareRows: 1,
			wordWrap: true,
			colWidths: columnWidths,
			allowInsertRow: true,
			columnSorting: true,
			colHeaders: columnHeaders,
			columns: columns
		};

		var finalCellHandsontableArgs = jQuery.extend(cellHandsontableArgs, data.custom_handsontable_args);
		window.hotAttr = new Handsontable(container3, finalCellHandsontableArgs);
		loading_ajax({ estado: false });
	}
});