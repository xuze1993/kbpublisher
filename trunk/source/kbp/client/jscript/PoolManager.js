var PoolManager = {
    
    visible_display: 'inline',
    
    add: function(id) {
        var pool = getCookie('kb_pool_');
        pool = (pool) ? $.parseJSON(pool) : [];
        
        if (pool.indexOf(id) == -1) {
            pool.push(id);
        }
        
        createCookie('kb_pool_', JSON.stringify(pool), 7);
        
        $('#pool_block').show();
        // $('#pool_num').html(pool.length);
		$('#pool_num').attr('data-badge', pool.length)
        $('#pool_block').fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(300);
        //$('#pool_block').fadeOut(100).toggle( "highlight" );
        
        $('#pool_add_link').hide();
        $('#pool_delete_link').css('display', PoolManager.visible_display);
    },
    
    
    remove: function(id) {
        var pool = getCookie('kb_pool_');
        pool = (pool) ? $.parseJSON(pool) : [];
        
        var index = pool.indexOf(id);
        delete pool[index];
        pool.splice(index, 1);
        
        // $('#pool_num').html(pool.length);
		$('#pool_num').attr('data-badge', pool.length)
        
        if (pool.length == 0) {
            deleteCookie('kb_pool_', '/');
            $('#pool_block').fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100).fadeOut(1000);
            
        } else {
            createCookie('kb_pool_', JSON.stringify(pool), 7);
            $('#pool_block').fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(300);
        }
        
        $('#pool_delete_link').hide();
        $('#pool_add_link').css('display', PoolManager.visible_display);
    },
    
    
    replace: function(pool) {
        createCookie('kb_pool_', JSON.stringify(pool), 7);
    },
        
    
    empty: function() {
        deleteCookie('kb_pool_', '/');
    }
    
}