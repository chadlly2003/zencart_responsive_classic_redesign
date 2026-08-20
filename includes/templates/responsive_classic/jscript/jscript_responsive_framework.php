<script title="responsive_framework">

(function($) {
$(document).ready(function() {

$('#contentMainWrapper').addClass('onerow-fluid');

$('a[href="#top"]').click(function(){
$('html, body').animate({scrollTop:0}, 'slow');
return false;
});

$(".categoryListBoxContents").click(function() {
window.location = $(this).find("a").attr("href");
return false;
});

$('.no-fouc').removeClass('no-fouc');
});

}) (jQuery);

</script>
