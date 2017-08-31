var PopupManager = {
    counter: 0,
    prefix: 'popupModalDiv',
    is_public: false,
    width: 925,
    height: 500,
    
    
    create: function(page, field_name, field_id, popup_value, width, height, title) {
                
        PopupManager.counter ++;
        var popup_name = PopupManager.prefix + PopupManager.counter;
        
        var div = '<div id="' + popup_name + '" style="background: #f5f5f5;"></div>';
        $(window.top.document.body).append(div);
        
        if (!title) {
            var html = '<span id="loadingMessage" style="position: absolute;" class="loading_spinner">Loading ...</span>';
            window.top.$('#' + popup_name).html(html);
        }
        
        var pv = (popup_value) ? popup_value : 1;
        
        
        if (!PopupManager.is_public) {
            page += '&field_name=' + field_name + '&field_id=' + field_id + '&popup=' + pv;
            
        } else {
            PopupManager.is_public = false;
        }
        
        var iframe = $('<iframe class="popup" name="' + popup_name + '" />').attr('src', page);
        
        if (width) {
            //iframe.css('width', width - 45);
        }
        
        if (height) {
            //iframe.css('height', height - 55);
        }
        
        var options = {
            modal: true,
            height: (height) ? height : PopupManager.height,
            width: (width) ? width : PopupManager.width,
            open: PopupManager.addShadow,
            close: PopupManager.close
        };
        
        if (title) {
            options.title = title;
        }
        
        window.top.$('#' + popup_name).dialog(options);
        window.top.$('#' + popup_name).append(iframe);
        window.top.$('#' + popup_name).css('padding', 0);
    },
    
    
    close: function() {
        var current_popup = PopupManager.getCurrentPopup();
        current_popup.empty();
        /*current_popup.dialog('close');
        current_popup.dialog('destroy');*/
        current_popup.remove();
        
        PopupManager.counter --;
    },
    
    
    closeAll: function() {
        $('div[id^="' + PopupManager.prefix + '"]').each(function() {
            $(this).empty();
            $(this).remove();
        });
        
        PopupManager.counter = 0;
    },
    
    
    checkForEscapeKey: function(e) {
        if (e.keyCode === $.ui.keyCode.ESCAPE) {
            var current_popup = PopupManager.getCurrentPopup();
            current_popup.dialog('close');
        }
        e.stopPropagation();
    },
    
    
    getCurrentPopup: function() {
        var popup_name = PopupManager.prefix + PopupManager.counter;
        return $('#' + popup_name);
    },
    
    
    getCurrentPopupFrame: function() {
        var popup = PopupManager.getCurrentPopup();
        return popup.find('iframe').contents();
    },
    
    
    getParentWindow: function() {
        if (PopupManager.counter > 1) {
            var parent_index = PopupManager.counter - 1;
            return window.top.frames[PopupManager.prefix + parent_index];
            
        } else {
            return window.top;
        }
    },
    
    
    setTitle: function(title) {
        var current_popup = PopupManager.getCurrentPopup();
        current_popup.dialog('option', 'title', title);
    },
    
    
    addShadow: function() {
        $('.ui-dialog').css('box-shadow', '#555 2px 2px 2px');
    }
}