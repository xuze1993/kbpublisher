var CookiePoolManager = {
    cookie_name: 'kb_forum_',
    
    add: function(id) {
        var pool = getCookie(CookiePoolManager.cookie_name);
        pool = (pool) ? $.parseJSON(pool) : [];
        
        if (pool.indexOf(id) == -1) {
            pool.push(id);
        }
        
        createCookie(CookiePoolManager.cookie_name, JSON.stringify(pool), 7);
    },
    
    
    remove: function(id) {
        var pool = getCookie(CookiePoolManager.cookie_name);
        pool = (pool) ? $.parseJSON(pool) : [];
        
        var index = pool.indexOf(id);
        delete pool[index];
        pool.splice(index, 1);
        
        if (pool.length == 0) {
            deleteCookie(CookiePoolManager.cookie_name, '/');
            
        } else {
            createCookie(CookiePoolManager.cookie_name, JSON.stringify(pool), 7);
        }
    },
    
    
    replace: function(pool) {
        createCookie(CookiePoolManager.cookie_name, JSON.stringify(pool), 7);
    },
        
    
    empty: function() {
        deleteCookie(CookiePoolManager.cookie_name, '/');
    }
    
}