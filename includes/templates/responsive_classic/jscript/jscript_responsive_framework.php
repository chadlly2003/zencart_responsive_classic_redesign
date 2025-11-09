<?php
/**
 * @copyright Copyright 2003-2024 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Jeff Rutt 2024 Sep 17 Modified in v2.1.0-beta1 $
 */
?>

<script title="responsive_framework">

(function($) {
$(document).ready(function() {

$('#contentMainWrapper').addClass('onerow-fluid');
 $('#mainWrapper').css({
     'max-width': '100%',
     'margin': 'auto'
 });
 $('#headerWrapper').css({
     'max-width': '100%',
     'margin': 'auto'
 });
 $('#navSuppWrapper').css({
     'max-width': '100%',
     'margin': 'auto'
 });

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
