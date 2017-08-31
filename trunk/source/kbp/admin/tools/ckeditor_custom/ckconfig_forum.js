CKEDITOR.editorConfig = function( config )
{
	
	// common 
    config.entities = false;
    config.entities_latin = false;
    config.entities_greek = false;
    config.disableNativeSpellChecker = false;
    
    config.browserContextMenuOnCtrl = true;
    config.fillEmptyBlocks = false;
    config.height = '150px';

	var AssetsPath = CKEDITOR.basePath.substr(0, CKEDITOR.basePath.length - 9);
    // config.templates_files = [ AssetsPath + 'ckeditor_custom/cktemplates.php' ];
    // config.stylesSet = 'kbpstyles:' + AssetsPath + 'ckeditor_custom/ckstyles.js';	
	config.contentsCss = [ CKEDITOR.basePath + '/contents.css', AssetsPath + '/ckeditor_custom/contents.css' ];	
    
    // custom plugins
    var customPluginsPath = AssetsPath + 'ckeditor_custom/plugins/';
    CKEDITOR.plugins.addExternal('widget', customPluginsPath + 'widget/', 'plugin.js');
    CKEDITOR.plugins.addExternal('lineutils', customPluginsPath + 'lineutils/', 'plugin.js');
    
    CKEDITOR.plugins.addExternal('codesnippet', customPluginsPath + 'codesnippet/', 'plugin.js');
    config.codeSnippet_theme = 'default';
    
    CKEDITOR.plugins.addExternal('kbp_remote_image', customPluginsPath + 'kbp_remote_image/', 'plugin.js');
    
    CKEDITOR.plugins.addExternal('notification', customPluginsPath + 'notification/', 'plugin.js');
    CKEDITOR.plugins.addExternal('notificationaggregator', customPluginsPath + 'notificationaggregator/', 'plugin.js');
    CKEDITOR.plugins.addExternal('embedbase', customPluginsPath + 'embedbase/', 'plugin.js');
    CKEDITOR.plugins.addExternal('embed', customPluginsPath + 'embed/', 'plugin.js');
    CKEDITOR.plugins.addExternal('autolink', customPluginsPath + 'autolink/', 'plugin.js');
    CKEDITOR.plugins.addExternal('autoembed', customPluginsPath + 'autoembed/', 'plugin.js');
    //config.embed_provider = '//5.101.104.84:8061/oembed?url={url}&callback={callback}'; // iframely
    config.embed_provider = 'oembed_proxy.php?url={url}&callback={callback}';
    
    config.extraPlugins = 'codesnippet,kbp_remote_image,notification,notificationaggregator,embedbase,embed,autolink,autoembed,widget';
    config.removePlugins = 'image';

	// custom 	
    config.enterMode = CKEDITOR.ENTER_BR;
    config.shiftEnterMode = CKEDITOR.ENTER_BR;
    
    config.linkShowAdvancedTab = false;
    config.linkShowTargetTab = false;
	
	config.toolbar = 'Forum';
    config.allowedContent = true;
    //config.toolbarCanCollapse = true;
    //config.toolbarStartupExpanded = false;
	
	config.toolbar_Forum =
	[
		{ name: 'basicstyles', items : [ 'Save','-','Bold','Italic','Underline','Strike' ] },
		{ name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
		{ name: 'links', items : [ 'Link','Unlink','-','Blockquote','CodeSnippet','Source','Embed','-','Maximize' ] },
        { name: 'kbp', items : [ 'KBPRemoteImage' ] }
	];
	
};