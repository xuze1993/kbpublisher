function showAction(action, actions) {
	HideDiv('bulk_update');
	setSortOrder(action);
	ShowExtraDiv(action);

	for(var i=0; i<actions.length; i++) {
		var d = 'bulk_'+actions[i];
		
		if(action == actions[i]) {
			ShowDiv(d);
			ShowDiv('bulk_update');
		} else {
			HideDiv(d);
		}
	}	
}


function showActionCustom(field_id) {

	$('.bulk_custom_field').hide();
	$('.bulk_custom_append').hide();
	$('.bulk_custom_append_ch').attr('checked', false);
	// 	$('input[name=foo]').attr('checked', true);
	
	if (field_id == 'set') {
		$('.bulk_custom_field').show();

	} else {
		$('#bulk_custom_field_'+field_id).show();
		$('#bulk_custom_append_'+field_id).show();
	}
}


function setSortOrder(action) {
	if(action == 'sort_order' || action == 'step_sort_order') {
		sortOrderToInput();
	} else {
		sortOrderToText();
	}	
}

function ShowExtraDiv(action) {

}

function BulkOnSubmit() {
	
}

function bulkValidate(action) {
    return true;
}

function checkAll(action, form, name){
	//var ch = fr.getElementsByTagName('checkbox');
	var f = document.getElementById(form);
	for (i=0; i<f.length; i++) {
		if (f[i].name.indexOf(name) == 0) {
			if(f[i].disabled == true) {
				continue;
			}
			
			if(action) {
				f[i].checked = true;
			} else {
				f[i].checked = false;
			}
		}
	}	
}


function isAnyChecked(form, name) {
	var f = document.getElementById(form);
	for (i=0; i<f.length; i++) {
		if (f[i].name.indexOf(name) == 0) {
			if(f[i].checked == true) {
				return true;
			}
		}
	}
	
	return false;
}

function getCheckedValues(form, name) {
    var values = [];
    
    var selector = '#' + form + ' input[name^=' + name + ']:checked';
    $(selector).each(function() {
        values.push($(this).val());
    });
    
    return values;
}

function bulkSubmit(sure_msg, empty_msg, name) {
    if (!name) {
        name = 'id';
    }
    
	if(isAnyChecked('bulk_form', name)) {
	    
	    var action = document.getElementById('bulk_action').value;
        if(!bulkValidate(action)) {
            return false;
        }
        
        confirmForm(sure_msg, 'bulk_submit');
        
	} else {
		alert(empty_msg);
	}

	return false;
}

function sortOrderToInput() {
	f = document.getElementById('bulk_form');
	f = $(".sortOrderText", f);
	f.attr({ disabled: false, tabindex: "1" } );
	f.removeClass().addClass("sortOrderInput");
}

function sortOrderToText() {
	f = document.getElementById('bulk_form');
	f = $(".sortOrderInput", f);
	f.attr({ disabled: true } );	
	f.removeClass().addClass("sortOrderText");
}

function changeTagAction(value) {
    if (value != 'remove') {
        $('#tag_assigned_block').show();
        $('#add_tag_link').show();
    } else {
        $('#add_tag_link').hide();
        $('#tag_assigned_block').hide();
    }
    $('#tag_form').hide();
}

function loadRoles(empty_msg) {
    var values = getCheckedValues('bulk_form', 'id');
    if (values.length == 0) {
        alert(empty_msg);
    } else {
        xajax_loadRoles(values);
    }
}