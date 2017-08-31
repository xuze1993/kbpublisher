CKEDITOR.editorConfig = function( config )
{
    
    // common 
    config.entities = false;
    config.entities_latin = false;
    config.entities_greek = false;
    config.disableNativeSpellChecker = false;
    
    config.browserContextMenuOnCtrl = true;
    config.fillEmptyBlocks = false;
    config.height = '250px';
    
	var AssetsPath = CKEDITOR.basePath.substr(0, CKEDITOR.basePath.length - 9);
    config.templates_files = [ AssetsPath + 'ckeditor_custom/cktemplates.php' ];
    config.stylesSet = 'kbpstyles:' + AssetsPath + 'ckeditor_custom/ckstyles.js';	
    config.contentsCss = [ CKEDITOR.basePath + '/contents.css', AssetsPath + '/ckeditor_custom/contents.css' ];	
	

    // ckfinder
    config.filebrowserBrowseUrl = AssetsPath + 'ckfinder/ckfinder.html';
    config.filebrowserImageBrowseUrl = AssetsPath + 'ckfinder/ckfinder.html?type=Images';
    config.filebrowserFlashBrowseUrl = AssetsPath + 'ckfinder/ckfinder.html?type=Flash';
    config.filebrowserUploadUrl = AssetsPath + 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files';
    config.filebrowserImageUploadUrl = AssetsPath + 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images';
    config.filebrowserFlashUploadUrl = AssetsPath + 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash';


    // custom 
    config.templates_replaceContent = false;
    
    var customPluginsPath = AssetsPath + 'ckeditor_custom/plugins/';
    
    CKEDITOR.plugins.addExternal('codesnippet', customPluginsPath + 'codesnippet/', 'plugin.js');
    config.codeSnippet_theme = 'default';
    
    // drag and drop
    CKEDITOR.plugins.addExternal('image2', customPluginsPath + 'image2/', 'plugin.js');
    CKEDITOR.plugins.addExternal('filetools', customPluginsPath + 'filetools/', 'plugin.js');
    CKEDITOR.plugins.addExternal('notification', customPluginsPath + 'notification/', 'plugin.js');
    CKEDITOR.plugins.addExternal('notificationaggregator', customPluginsPath + 'notificationaggregator/', 'plugin.js');
    CKEDITOR.plugins.addExternal('uploadwidget', customPluginsPath + 'uploadwidget/', 'plugin.js');
    CKEDITOR.plugins.addExternal('uploadimage', customPluginsPath + 'uploadimage/', 'plugin.js');
    config.uploadUrl = AssetsPath + 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images&responseType=json';
    
    // widgets
    CKEDITOR.plugins.addExternal('widget', customPluginsPath + 'widget/', 'plugin.js');
    CKEDITOR.plugins.addExternal('lineutils', customPluginsPath + 'lineutils/', 'plugin.js');
    CKEDITOR.plugins.addExternal('simplebox', customPluginsPath + 'simplebox/', 'plugin.js');
    CKEDITOR.plugins.addExternal('kbp_draggable_template', customPluginsPath + 'kbp_draggable_template/', 'plugin.js');
    
    CKEDITOR.plugins.addExternal('kbp_tooltip', customPluginsPath + 'kbp_tooltip/', 'plugin.js');
    CKEDITOR.plugins.addExternal('kbp_entry_link', customPluginsPath + 'kbp_entry_link/', 'plugin.js');
    
    CKEDITOR.plugins.addExternal('video', customPluginsPath + 'video/', 'plugin.js');
    
    //CKEDITOR.plugins.addExternal('sharedspace', customPluginsPath + 'sharedspace/', 'plugin.js');
    
    config.extraPlugins = 'simplebox,codesnippet,filetools,notification,notificationaggregator,uploadwidget,uploadimage,image2,kbp_tooltip,kbp_entry_link,video';
    
    //CKEDITOR.plugins.addExternal('kbp_dropuploader', customPluginsPath + 'kbp_dropuploader/', 'plugin.js');
    //config.extraPlugins = 'kbp_dropuploader';

    config.toolbar = 'Knowledgebase';
    config.allowedContent = true;
    config.toolbar_Knowledgebase =
    [
        { name: 'document', items : [ 'Save','-','Source','-','Templates','-','Preview','-','Maximize'] },
        { name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','CodeSnippet','-','Print' ] },
        { name: 'editing', items : [ 'Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat','Scayt' ] },
        { name: 'tools', items : [ 'ShowBlocks','-','About' ] },
                '/',
        { name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike' ] },
        { name: 'paragraph', items : [ 'NumberedList','BulletedList','-','Outdent','Indent' ] },
        { name: 'justify', items : ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock' ] },
        { name: 'links', items : [ 'Link','Unlink','Anchor' ] },
        { name: 'insert', items : [ 'Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak' ] },
        { name: 'colors', items : [ 'TextColor','BGColor' ] },
                '/',        
        { name: 'styles', items : [ 'Styles','Format','Font','FontSize' ] },
        // { name: 'form', items : [ 'Checkbox', 'Radio', 'Select', 'TextField', 'Textarea' ] },
        { name: 'kbp', items : [ 'KBPTooltip','KBPRemoveTooltip','-','KBPArticleLink','KBPFileLink','Video' ] }
    ];
    
    /*config.sharedSpaces = {
        top: 'ck_toolbar_top',
        bottom: 'ck_toolbar_bottom'
    };*/

};