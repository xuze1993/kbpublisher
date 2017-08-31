$('body').bind('kbpSelectedEntryTransferredToPopup', {}, function(e, params) {
	var parents = window.parent_categories[params.id];
    for (var i in parents) {
        $('#' + handler.idFirst + ' option[value="' + parents[i] + '"]').attr('disabled', true); 
    }
});


$('body').bind('kbpEntrySelected', {}, function(e, params) {
    var parents = window.parent_categories[params.id];
    
    $('#' + params.handler.idSecond).children('option').each(function() {
        if ($.inArray(parseInt(this.value), parents) != -1) {
            params.handler.deleteOption(this.value);
        }
    });
    
    params.handler.setDisabled(parents);        
});


$('body').bind('kbpEntryDeleted', {}, function(e, params) {
    var entry_to_delete_parents = window.parent_categories[params.id];
    var selected_entries_parents = [];
    
    $('#' + params.handler.idSecond).children('option').each(function() {
        var parents = window.parent_categories[this.value];
        selected_entries_parents = selected_entries_parents.concat(parents);
    });
    
    var entries_to_enable = [];
    for (var i in entry_to_delete_parents) {
        if ($.inArray(entry_to_delete_parents[i], selected_entries_parents) == -1) {
            entries_to_enable.push(entry_to_delete_parents[i]);
        }
    }
    
    params.handler.setEnabled(entries_to_enable);
});


$('body').bind('kbpEntriesFiltered', {}, function(e, params) {
    var parents = window.parent_categories[params.id];
                
    for (var i in parents) {
        $('#' + handler.idFirst + ' option[value="' + parents[i] + '"]').attr('disabled', true);
    }
});