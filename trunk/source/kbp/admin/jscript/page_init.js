$(document).ready(function() {
    TableListHandler.init();
    
    LeaveScreenMsg.form_id = 'aContentForm';
    if(document.getElementById(LeaveScreenMsg.form_id)) {
        
        LeaveScreenMsg.setDoCheck(1);
        LeaveScreenMsg.check();
        
        // ie stop leave alert
        if (jQuery.browser.msie) {
            
            $('a[href^="javascript"],a[href^="#"]').bind('click', function(){
                LeaveScreenMsg.skipCheck();
            });
        
            $('a[href^="javascript"],a[href^="#"]').blur(function() {
                LeaveScreenMsg.setDoCheck(1);
            });
        }
    }
    
    $('ul.dropdown-menu').each(function() { // fix to enable a decent tab navigation in dropdowns
        $(this).find('li > a').last().blur(function() {
            $(document).click();
        });
    });
    
    
    $('._tooltip:not([title=""])').tooltipster({
    // $('div:not([title=""])').tooltipster({
        contentAsHTML: true,
        theme: ['tooltipster-kbp'],
        interactive: true,
        maxWidth: 400,
        side: ['top', 'left']
    });
            
    $('._tooltip_click').tooltipster({
        contentAsHTML: true,
        theme: ['tooltipster-kbp'],
        interactive: true,
        maxWidth: 400,
        trigger: 'click',
        side: ['top', 'left']
    });
    
    
    $('body').bind('kbpCategorySelected', {}, function(e, params) {
        $('#' + CategoriesInputHandler.text_input_id).val(params.text);
        $('#' + CategoriesInputHandler.text_input_id).attr('title', params.text);
        $('#' + CategoriesInputHandler.hidden_input_id).val(params.id).trigger('change');
    });


	// to hide on cache loading msg
	window.onpageshow = function(event) {
	    if (event.persisted) {
	        $('#loadingMessagePage').hide();
	    }
	};


		//     $(window).on('beforeunload', function(){
		//         setTimeout(function() {
		//             $('#loadingMessagePage').show();
		//         }, 1000);
		//     });
		//
		//     $(window).on('load', function(){
		// $('#loadingMessagePage').hide();
		//     });

	
	
	
	
});