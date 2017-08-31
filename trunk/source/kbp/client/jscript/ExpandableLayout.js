function setHeight() {
    var areas_height = $('#header_div').outerHeight() +
        $('#footer').outerHeight() +
        $('#custom_header').outerHeight() +
        $('#custom_footer').outerHeight() + 1;
        
    var height = $(window).height() - areas_height;
    $('#container, #sidebar, #divider, #content').height(height);
    
    var left_block_width = $('#sidebar').width();
    var width = $(window).width() - (left_block_width + 10);
    $('#content').css('width', width);
    
    var left_block_width = $('#sidebar').width();
    $('#content').css('left', left_block_width);
    $('#content').show();
}


function resizeRightBlock(event, ui) {
    if (event) {
        event.stopPropagation();
    }
    
    var left_block_width = $('#sidebar').width();
    var right_block_width = $(window).width() - left_block_width;
    
    $('#content').css('left', left_block_width);
    $('#content').css('width', right_block_width);
    
    var left = ($('#sidebar').width() > 9) ? $('#sidebar').width() - 9 : $('#sidebar').width();
    $('#divider').css('left', left);
}


function toggleSidebar() {
    var action = $('#sidebar_toggle').attr('title');
    var next_action = $('#sidebar_toggle').attr('data-title');
    
    $('#sidebar_toggle').attr('title', next_action);
    $('#sidebar_toggle').attr('data-title', action);
    
    var sidebar_width = $('#sidebar').width(); 
    if (sidebar_width > 1) {
        expanded_sidebar_width = $('#sidebar').width();
        
        $('#sidebar').css('overflow', 'hidden'); // ie fix
        $('#sidebar').width(1);
        
        $('#divider div').removeClass('sidebar_shown').addClass('sidebar_hidden');
        
    } else {
         // ie fixes
        $('#sidebar').css('overflow', 'auto');
        $('#sidebar').css('overflow-x', 'hidden');
        
        $('#sidebar').width(expanded_sidebar_width);
        
        $('#divider div').removeClass('sidebar_hidden').addClass('sidebar_shown');
    }
    
    createCookie('kb_sidebar_width_', $('#sidebar').width(), 0);
    
    resizeRightBlock();
}


function sidebarResized() {
    expanded_sidebar_width = $('#sidebar').width();
    createCookie('kb_sidebar_width_', $('#sidebar').width(), 0);    
    resizeRightBlock();
}