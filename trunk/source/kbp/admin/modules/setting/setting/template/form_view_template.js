<script src="../client/jscript/jquery/jquery_select.js" type="text/javascript"></script>
<script type="text/javascript">
	
	var myOptions = {{myOptionsJson}};
			
	$(document).ready(function() {
		var format_value = document.getElementById('view_format').value;
		populateSelect(format_value);
        
        toggleSubscriptionTimePicker($('#subscribe_news_interval').val(), 'news');
        toggleSubscriptionTimePicker($('#subscribe_entry_interval').val(), 'entry');
	});	
	
	function populateSelect(view_key) {
        
        var options_range = ['template', 'menu_type'];
        for (var i in options_range) {
            var vars = myOptions[options_range[i]][view_key];
            $('#view_' + options_range[i]).empty();
            
            if (vars.length == 0) {
                $('#view_' + options_range[i]).attr('disabled', true);
            } else {
                $('#view_' + options_range[i]).attr('disabled', false);
                
                for (var j = 0; j < vars.length; j ++) {
                    $('#view_' + options_range[i]).addOption(vars[j].val, vars[j].text, vars[j].s);
                }
            }
        }
        
        if (view_key == 'fixed') {
            $('#view_header').prop('checked', true).prop('disabled', true);
            
        } else {
            $('#view_header').prop('disabled', false);
        }
	}
    
    function toggleSubscriptionTimePicker(value, type) {
        if (value == 'daily') {
            $('#template_subscribe_' + type + '_time').show();
            $('#template_subscribe_' + type + '_time').removeClass('auto_hidden');
            
        } else {
            $('#template_subscribe_' + type + '_time').hide();
            $('#template_subscribe_' + type + '_time').addClass('auto_hidden');
        }
    }
	
</script>
{field}