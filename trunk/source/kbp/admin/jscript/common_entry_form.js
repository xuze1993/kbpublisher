function subForm() {
    LeaveScreenMsg.skipCheck();	
    selHandler.selectAll();
    selRoleHandler.selectAll();
    selRoleWriteHandler.selectAll();
}


function categoryAjaxHandler() {
	
	$('#category_private_div').html('<span id="writeroot_private"></span>');
	
    $('#category_private_content, #category_private_content2').hide();
	
	var writeroot = document.getElementById('writeroot_sort');
	if(writeroot) {
		$('#writeroot_sort').html('<span id="writeroot_sort"></span>');
	}
    
    $('#category').children('option').each(function() {
		xajax_getCategoryPrivateInfo(this.value, this.text);
		
		// no need to sort in add local files form and file rules 
		// and SortShowMore required ???
		if(SortShowMore && writeroot) {
			xajax_populateSortSelect(this.value, this.text);
		}
    });
    
    if (window.button_text_enabled) {
        updateButtonText();
    }
}


// custom fields, with categories
function customFieldCategoryHandler(msg) {
    
    selHandler.addOnPopupCloseFunction('getCustomByCategory');
    
    var onclick = $('#delete_category_button').attr('onclick');
    
    $('#delete_category_button').attr('onclick', '');
    $('#category').attr('ondblclick', '');
    
    $('#delete_category_button').click(onclick, _customFieldCategoryHandler);        
    $('#category').dblclick(onclick, _customFieldCategoryHandler);
}

function _customFieldCategoryHandler(e) {
    eval(e.data);
}

function getCustomByCategory(data) {
    var ids = [];
    var categories = data[0];
    for (var i in categories) {
        ids.push(categories[i]['value']);
    }
    
    xajax_getCustomByCategory(ids);
}

function deleteCustom(ids) {
    for (var id in ids) {
        $('#tr_custom_' + ids[id]).remove();
    }
}

function deleteCustomAll() {
    $('.custom_category').remove();
}

function insertCustom(html) {
    var ids = [];
    $(html).find('tr').each(function() {
        if (this.id) {
            if (!$('#' + this.id).length) {
                $(this).insertAfter('#custom_field_bottom_border');
            }
            ids.push(this.id);
        }
    });
    
    deleteCustomByCategory(ids);
}

function deleteCustomByCategory(ids) {
    $('.custom_category').each(function() {
        if (jQuery.inArray(this.id, ids) == -1) {
            $('#' + this.id).remove();
        }
    });
}