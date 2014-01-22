jQuery(document).ready(function(){
  jQuery('.expired_button').on('click', function(event){
    event.preventDefault();
    jQuery.post(wpeb_script.ajaxurl, {action: 'handle_button_click', pid: jQuery(this).data('pid')}, function(data) {
      alert('Thank You!');
    });
  });
});