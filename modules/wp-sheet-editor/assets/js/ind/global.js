
/* Ajax calls loop 
 * Execute ajax calls one after another
 * */
function beAjaxLoop(args) {

	//setup an array of AJAX options, each object is an index that will specify information for a single AJAX request

	var defaults = {
		totalCalls: null,
		current: 1,
		url: '',
		method: 'GET',
		dataType: 'json',
		data: {},
		prepareData: function (data, settings) {
			return data;
		},
		onSuccess: function (response, settings) {

		},
		onError: function (jqXHR, textStatus, settings) {

		},
		status: 'running',
	};

	var settings = jQuery.extend(defaults, args);


	//declare your function to run AJAX requests
	function do_ajax() {

		//check to make sure there are more requests to make
		if (settings.current < settings.totalCalls + 1) {

			if (settings.status !== 'running') {
//				console.log('not running');
				return true;
			}

			settings.data.page = settings.current;

//			console.log(settings);

			var data = {
				url: settings.url,
				dataType: settings.dataType,
				data: settings.prepareData(settings.data, settings),
				method: settings.method,
			};
//			console.log(data);
			jQuery.ajax(data).done(function (serverResponse) {

//				console.log(serverResponse);
				var goNext = settings.onSuccess(serverResponse, settings);

				//increment the `settings.current` counter and recursively call this function again
				if (goNext) {
					settings.current++;

					setTimeout(function () {
						do_ajax();
					}, parseInt(vgse_sheet_settings.wait_between_batches) * 1000);
				}
			}).fail(function (jqXHR, textStatus) {

//				console.log(jqXHR);
//				console.log(textStatus);
				var goNext = settings.onError(jqXHR, textStatus, settings);
				//increment the `settings.current` counter and recursively call this function again
				if (goNext) {
					settings.current++;
					setTimeout(function () {
						do_ajax();
					}, parseInt(vgse_sheet_settings.wait_between_batches) * 1000);
				}
			});
		}
	}

	//run the AJAX function for the first time once `document.ready` fires
	do_ajax();

	return {
		pause: function () {
			settings.status = 'paused';
		},
		resume: function () {
			settings.status = 'running';
//			console.log('resuming');
			do_ajax();
		}
	};
}


//  show or hide loading screen
function loading_ajax(options) {
	var defaults = {
		'estado': true
	}
	jQuery.extend(defaults, options);

	if (defaults.estado == true) {
		if (!jQuery('body').find('.sombra_popup').length) {
			jQuery('body').append('<div class="sombra_popup be-ajax"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>');
		}
		jQuery('.sombra_popup').fadeIn(1000);
	} else {
		jQuery('.sombra_popup').fadeOut(800, function () {
//			jQuery('.sombra_popup').remove();
		});
	}
}


// Show notification to user
function notification(options) {
	var defaults = {
		'tipo': 'success',
		'mensaje': '',
		'time': 8600
	}
	jQuery.extend(defaults, options);

	setTimeout(function () {
		if (defaults.tipo == 'success') {
			var color = 'green';
		} else if (defaults.tipo == 'error') {
			var color = 'red';
		} else if (defaults.tipo == 'warning') {
			var color = 'orange';
		} else {
			var color = 'blue';
		}

		jQuery('#ohsnap').css('z-index', '1100000');
		setTimeout(function () {
			jQuery('#ohsnap').css('z-index', '-1');
		}, defaults.time);
		ohSnap(defaults.mensaje, {time: defaults.time, color: color});

	}, 500);
}


// Define chunk method to split arrays in groups
Object.defineProperty(Array.prototype, 'chunk', {
	value: function (chunkSize) {
		var array = this;
		return [].concat.apply([],
				array.map(function (elem, i) {
					return i % chunkSize ? [] : [array.slice(i, i + chunkSize)];
				})
				);
	}
});

/**
 * Show notification to user after a failed ajax request.
 * Ex. the server is not available
 */
jQuery(document).ajaxError(function (event, xhr, ajaxOptions, thrownError) {
//	console.log(event);
//	console.log(xhr);
//	console.log(ajaxOptions);
//	console.log(thrownError);

	loading_ajax({estado: false});
	if (xhr.statusText !== 'abort') {
		if (xhr.status == 400) {
			notification({mensaje: vgse_sheet_settings.texts.http_error_400, tipo: 'error', tiempo: 60000});
		} else if (xhr.status == 403) {
			notification({mensaje: vgse_sheet_settings.texts.http_error_403, tipo: 'error', tiempo: 60000});
		} else if (xhr.status == 500 || xhr.status == 502 || xhr.status == 505) {
			notification({mensaje: vgse_sheet_settings.texts.http_error_500_502_505, tipo: 'error', tiempo: 60000});
		} else if (xhr.status == 503) {
			notification({mensaje: vgse_sheet_settings.texts.http_error_503, tipo: 'error', tiempo: 60000});
		} else if (xhr.status == 509) {
			notification({mensaje: vgse_sheet_settings.texts.http_error_509, tipo: 'error', tiempo: 60000});
		} else if (xhr.status == 504) {
			notification({mensaje: vgse_sheet_settings.texts.http_error_504, tipo: 'error', tiempo: 60000});
		} else {
			notification({mensaje: vgse_sheet_settings.texts.http_error_default, tipo: 'error', tiempo: 60000});
		}
	}
});

/**
 * Show notification to user after a successful ajax request.
 */
jQuery(document).ajaxComplete(function (event, xhr, ajaxOptions, thrownError) {
//	console.log(event);
//	console.log(xhr);
//	console.log(ajaxOptions);
//	console.log(thrownError);

	if (xhr.statusText !== 'abort') {
		if (xhr.responseText === '0' || xhr.responseText === 0 || thrownError) {
//		console.log('empty response');
			loading_ajax({estado: false});
			notification({mensaje: vgse_sheet_settings.texts.http_error_500_502_505, tipo: 'error', tiempo: 60000});
		}
	}
});

/**
 * Load posts into the spreadsheet
 * @param obj data ajax request data parameters
 * @param fun callback
 * @param bool customInsert If we want to load rows but use custom success controller.
 */
function beLoadPosts(data, callback, customInsert, removeExisting) {
	loading_ajax({estado: true});

	if (!customInsert) {
		customInsert = true;
	}
	if (!removeExisting) {
		removeExisting = false;
	}
	data.action = 'bep_load_data';

	// Apply filters to request
	if (jQuery('body').data('be-filters')) {
		data.filters = jQuery('body').data('be-filters');
	}
	jQuery.ajax({
		url: ajaxurl,
		dataType: 'json',
		type: 'POST',
		data: data,
		dataType: 'json',
	}).success(function (response) {

		if (typeof callback === 'function') {
			callback(response);

			if (customInsert) {
				return true;
			}
		}
		if (response.success) {

			// Add rows to spreadsheet
			/*var data = [], row;
			 for (var i = 0, ilen = Object.keys(response.data).length; i < ilen; i++) {
			 row = response.data[i]
			 data[i] = row;
			 }*/

			vgAddRowsToSheet(response.data, null, removeExisting);

			notification({mensaje: vgse_sheet_settings.texts.posts_loaded});
			loading_ajax({estado: false});

			jQuery('.ht_clone_top.handsontable').remove();

		} else {
			// Disable loading screen and notify of error
			loading_ajax({estado: false});

			notification({mensaje: response.data.message, tipo: 'error', tiempo: 60000});
		}
	});
}

/**
 * Remove duplicated items from array
 * @param array data
 * @returns array
 */
function beDeduplicateItems(data) {
	var out = [];
	var type = (data[0] instanceof Array) ? 'array' : 'object';
	jQuery.each(data, function (key, item) {
		var id = (type === 'array') ? item[0] : item.ID;

		if (typeof id === 'string') {
			id = id.replace(/[^0-9]/gi, '');
		}
		if (!out[ id ]) {
			out[ id ] = item;
		}
	});
	return out;
}

/**
 * Get modified object properties
 * @param obj orig
 * @param obj update
 * @returns obj
 */
function beGetModifiedObjectProperties(orig, update) {
	var diff = {};

	Object.keys(update).forEach(function (key) {
		if (typeof orig[key] === 'undefined' || update[key] != orig[key]) {
			diff[key] = update[key];
		}
	})

	console.log(diff);
	return diff;
}

/**
 * Check if arrays are identical recursively
 * @param array arr1
 * @param array arr2
 * @returns Boolean
 */
function beArraysIdenticalCheck(arr1, arr2) {
	console.log(arr1);
	console.log(arr2);
	if (arr1.length !== arr2.length) {
		return false;
	}
	for (var i = arr1.length; i--; ) {
		if (arr1[i] !== arr2[i]) {
			return false;
		}
	}

	return true;
}
/**
 * Compare arrays and return modified items only.
 * 
 * @param array newData
 * @param array originalData
 * @returns array
 */
function beGetModifiedItems(newData, originalData) {
	var newData = beDeduplicateItems(newData);
	var originalData = beDeduplicateItems(originalData);
	var out = [];

	console.log(newData);
	console.log(originalData);

	var type = (newData[0] instanceof Array) ? 'array' : 'object';

	console.log(type);
	newData.forEach(function (item, id) {
		console.log(id);
		console.log(item);
		console.log(newData[ id ]);
		console.log(originalData[ id ]);

		if (type === 'array' && (typeof originalData[ id ] === 'undefined' || !beArraysIdenticalCheck(newData[ id ], originalData[ id ]))) {
			out.push(item);
		} else if (type === 'object') {

			var modifiedProperties = beGetModifiedObjectProperties(originalData[id], newData[id]);
			console.log(modifiedProperties);

			var saveData;
			if (typeof originalData[id] === 'undefined' || !jQuery.isEmptyObject(modifiedProperties)) {
				if (originalData[id].post_type && vgse_sheet_settings.saveFullRowPostTypes && vgse_sheet_settings.saveFullRowPostTypes.indexOf(originalData[id].post_type) > -1) {
					saveData = newData[id];
				} else {
					modifiedProperties.ID = id;
					saveData = modifiedProperties;
				}
				// Replace file columns html with the file value
				jQuery.each(saveData, function (key, value) {
					if (typeof value === 'string' && value.indexOf('set_custom_images') > -1) {
						var $cellData = jQuery('<div/>').html(value);
						var gallery = $cellData.find('.set_custom_images').attr('data-images');

						saveData[key] = (gallery) ? gallery : '';
					}
				});



				out.push(saveData);
			}


		}
	});

	console.log(out);
	return out;
}

/**
 * Get tinymce editor content
 * @returns string
 */
function beGetTinymceContent() {
	if (jQuery('.wp-editor-area').css('display') !== 'none') {
		var content = jQuery('.wp-editor-area').val() || '';
	} else {
		if (document.getElementById('editpost_ifr')) {
			var frame = document.getElementById('editpost_ifr').contentWindow.document || document.getElementById('editpost_ifr').contentDocument;
			var content = frame.body.innerHTML;
		} else {
			var content = '';
		}
	}

	return content;
}

/**
 * Execute function by string name
 */
function vgseExecuteFunctionByName(functionName, context /*, args */) {
	var args = [].slice.call(arguments).splice(2);
	var namespaces = functionName.split(".");
	var func = namespaces.pop();
	for (var i = 0; i < namespaces.length; i++) {
		context = context[namespaces[i]];
	}
	return context[func].apply(context, args);
}

/**
 * Convert an object to array of values
 * @param obj object
 * @returns Array
 */
function vgObjectToArray(object) {
	var values = [];
	for (var property in object) {
		values.push(object[property]);
	}
	return values;
}


/**
 * Returns a function, that, as long as it continues to be invoked, will not be triggered. The function will be called after it stops being called for N milliseconds. If immediate is passed, trigger the function on the leading edge, instead of the trailing.
 * @param func func
 * @param int wait
 * @param bool immediate
 * @returns func
 */
function _debounce(func, wait, immediate) {
	var timeout, args, context, timestamp, result;

	var later = function () {
		var last = _now() - timestamp;

		if (last < wait && last >= 0) {
			timeout = setTimeout(later, wait - last);
		} else {
			timeout = null;
			if (!immediate) {
				result = func.apply(context, args);
				if (!timeout)
					context = args = null;
			}
		}
	};

	return function () {
		context = this;
		args = arguments;
		timestamp = _now();
		var callNow = immediate && !timeout;
		if (!timeout)
			timeout = setTimeout(later, wait);
		if (callNow) {
			result = func.apply(context, args);
			context = args = null;
		}

		return result;
	};
}
;

/**
 * A (possibly faster) way to get the current timestamp as an integer.
 * @returns int
 */
function _now() {
	var out = Date.now() || new Date().getTime();
	return out;
}

/**
 * Returns a function, that, when invoked, will only be triggered at most once during a given window of time. Normally, the throttled function will run as much as it can, without ever going more than once per wait duration; but if you’d like to disable the execution on the leading edge, pass {leading: false}. To disable execution on the trailing edge, ditto.
 * @param func
 * @param int wait
 * @param obj options
 * @returns func
 */
function _throttle(func, wait, options) {

	if (!wait) {
		wait = 300;
	}
	var context, args, result;
	var timeout = null;
	var previous = 0;
	if (!options)
		options = {};
	var later = function () {
		previous = options.leading === false ? 0 : _now();
		timeout = null;
		result = func.apply(context, args);
		if (!timeout)
			context = args = null;
	};
	return function () {
		var now = _now();
		if (!previous && options.leading === false)
			previous = now;
		var remaining = wait - (now - previous);
		context = this;
		args = arguments;
		if (remaining <= 0 || remaining > wait) {
			if (timeout) {
				clearTimeout(timeout);
				timeout = null;
			}
			previous = now;
			result = func.apply(context, args);
			if (!timeout)
				context = args = null;
		} else if (!timeout && options.trailing !== false) {
			timeout = setTimeout(later, remaining);
		}
		return result;
	};
}
;

/**
 * Remove post ID from array of data
 */
function vgseRemovePostFromSheet(postId, data) {

	console.log(data);
	var newData = [];

	postId = parseInt(postId);
	data.forEach(function (item, id) {
		var item2 = jQuery.extend(true, {}, item);
		console.log(item.ID);

		if (typeof item2.ID === 'string') {
			item2.ID = parseInt(item2.ID.replace(/[^0-9]/gi, ''));
		}
		console.log(item2.ID);
		console.log(postId);
		if (postId !== item2.ID) {
			newData.push(item);
		}
	});
	return newData;
}

/**
 * Add rows to spreadsheet
 * @param array data Array of objects
 * @param str method append | prepend
 * @returns null
 */
function vgAddRowsToSheet(data, method, removeExisting) {
	if (!method) {
		method = 'append';
	}
	
	if(!data){
		data = [];
	}
	
	if (method === 'prepend') {
		data = data.reverse();
	}

	var hotData = hot.getSourceData();
	console.log(hotData);


	// Remove existing items from spreadsheet
	if (removeExisting) {
		data.forEach(function (item, id) {
			var item2 = jQuery.extend(true, {}, item);
			if (typeof item2.ID === 'string') {
				item2.ID = parseInt(item2.ID.replace(/[^0-9]/gi, ''));
			}
			console.log(item2.ID);
			hotData = vgseRemovePostFromSheet(item2.ID, hotData);
		});
	}

	for (i = 0; i < data.length; i++) {

		if (method === 'append') {
			hotData.push(jQuery.extend(true, {}, data[i]));
		} else {
			hotData.unshift(jQuery.extend(true, {}, data[i]));
		}

	}
	hot.loadData(hotData);
	console.log(hotData);


	// Save original data, used to compare posts 
	// before saving and save only modified posts.
	if (!window.beOriginalData) {
		window.beOriginalData = [];
	}

	window.beOriginalData = jQuery.merge(window.beOriginalData, data);
}
/**
 * save image in local cache
 */
function beSendImageIdToWP(gallery, id, key, type, cellCoords, callback) {

	setTimeout(function () {

		var cellData = hot.getDataAtCell(cellCoords.row, cellCoords.col);
		var $cellData = jQuery('<div/>').html(cellData);
		$cellData.find('.set_custom_images').text(vgse_sheet_settings.texts.use_other_image);
		$cellData.find('.set_custom_images').attr('data-images', gallery);
		$cellData.find('.hidden').removeClass('hidden');
		console.log(cellData);
		console.log(cellCoords);
		console.log($cellData.html());
		hot.setDataAtCell(cellCoords.row, cellCoords.col, $cellData.html());


		if (typeof callback === 'function') {
			callback(response);
		}
	}, 800);
}

/**
 *Init select2 on <select>s
 */
function vgseInitSelect2() {

	jQuery("select.select2").each(function () {
		var config = {
			placeholder: jQuery(this).data('placeholder'),
			minimumInputLength: jQuery(this).data('min-input-length') || 0,
			//			allowClear: true
		};
		if (jQuery(this).data('remote')) {
			config.ajax = {
				url: ajaxurl,
				delay: 1000,
				data: function (params) {
					var query = {
						search: params.term,
						page: params.page,
						action: jQuery(this).data('action'),
						output_format: jQuery(this).data('output-format'),
						post_type: jQuery(this).data('post-type') || jQuery('#post-data').data('post-type'),
						nonce: jQuery(this).data('nonce'),
					}

					// Query paramters will be ?search=[term]&page=[page]
					return query;
				},
				processResults: function (response) {
					console.log(response);
					if (!response.success) {
						return {
							results: []
						};
					}
					return {
						results: response.data.data
					};
				},
				cache: true
			};
		}
		jQuery(this).select2(config);
	});
}

/**
 * Reload spreadsheet.
 * Removes current rows and loads the rows from the server again.
 */
function vgseReloadSpreadsheet() {
	var nonce = jQuery('.remodal-bg').data('nonce');
	var $container = jQuery("#post-data")

	// Reset internal cache, used to find the modified cells for saving        
	window.beOriginalData = [];
	// Reset spreadsheet
	hot.loadData([]);

	beLoadPosts({
		post_type: $container.data('post-type'),
		nonce: nonce
	});
}