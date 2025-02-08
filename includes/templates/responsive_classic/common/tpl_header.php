<?php
/**
 * Common Template - tpl_header.php
 *
 * this file can be copied to /templates/your_template_dir/pagename
 * example: to override the privacy page
 * make a directory /templates/my_template/privacy
 * copy /templates/templates_defaults/common/tpl_footer.php to /templates/my_template/privacy/tpl_header.php
 * to override the global settings and turn off the footer un-comment the following line:
 *
 * $flag_disable_header = true;
 *
 * @copyright Copyright 2003-2024 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Nick Fenwick 2023 Jul 04 Modified in v2.0.0-alpha1 $
 */
?>

<?php
  // Display all header alerts via messageStack:
  if ($messageStack->size('header') > 0) {
    echo $messageStack->output('header');
  }
  if (!empty($_GET['error_message'])) {
    echo zen_output_string_protected(urldecode($_GET['error_message']));
  }
  if (!empty($_GET['info_message'])) {
   echo zen_output_string_protected($_GET['info_message']);
}
// check whether to only display errors/alerts, or to also display the rest of the header
if (isset($flag_disable_header) && $flag_disable_header === true) {
  // do early-return from this template since $flag_disable_header is true
  return;
}
?>
 
<div id="headerWrapper">

<!-- sidebar navigation -->
<div class="nav_spacer"> 
<!-- Hamburger Icon Button -->
  
<div class="row">
  <div class="col">
    <!-- Change <a> to <button> to prevent any unwanted page navigation -->
    <button aria-label="Categories" type="button" class="btn btn-outline-primary">
      <span id="menu-icon" class="fa fa-bars baralignment"></span>
    </button>
  </div>
</div>
<div class="sidebar is-hidden">
  <h1 class="sidebar_title">Categories</h1>
  
  
      <?php
      // load the UL-generator class and produce the menu list dynamically from there
      require_once (DIR_WS_CLASSES . 'categories_ul_generator.php');
      $zen_CategoriesUL = new zen_categories_ul_generator();
      $menulist = $zen_CategoriesUL->buildTree(false, 1);
  
      // Get the entire menu (with subcategories)
      $menulist = $zen_CategoriesUL->buildTree(true, 1);

     echo $menulist;
    ?> 
    
<hr class="spacernav" />

    <ul class="myaccounts">
      <li><?php echo '<a href="' . HTTP_SERVER . DIR_WS_CATALOG . '">'; ?><i class="fa fa-xl fa-fw fa-home" aria-hidden="true">&nbsp</i> <?php echo HEADER_TITLE_CATALOG; ?></a></li>
      
      <?php
          if (zen_is_logged_in() && !zen_in_guest_checkout()) {
      ?>
      <li><a href="<?php echo zen_href_link(FILENAME_LOGOFF, '', 'SSL'); ?>"><i class="fa fa-xl fa-fw fa-sign-out" aria-hidden="true"></i> <?php echo HEADER_TITLE_LOGOFF; ?></a></li><?php if ($_SESSION['cart']->count_contents() != 0) { ?>
      
      <li><a href="<?php echo zen_href_link(FILENAME_ACCOUNT, '', 'SSL'); ?>"><i class="fa-solid fa-xl fa-fw fa-user"></i> <?php echo HEADER_TITLE_MY_ACCOUNT; ?></a></li>
        <?php } else { ?>
        <li class=""><a href="<?php echo zen_href_link(FILENAME_ACCOUNT, '', 'SSL'); ?>"><i class="fa-solid fa-xl fa-fw fa-user"></i> <?php echo HEADER_TITLE_MY_ACCOUNT; ?></a></li>
        <?php } ?>
      <?php
        } else {
          if (STORE_STATUS == '0') {
      ?>

      <?php if ($_SESSION['cart']->count_contents() != 0) { ?>
        <li><a href="<?php echo zen_href_link(FILENAME_LOGIN, '', 'SSL'); ?>"> 
          <i class="fa fa-xl fa-fw fa-sign-out" aria-hidden="true"></i> 
          <?php echo HEADER_TITLE_LOGIN; ?></a></li>
        <?php } else { ?>
          <li class=""><a href="<?php echo zen_href_link(FILENAME_LOGIN, '', 'SSL'); ?>">
          <i class="fa fa-xl fa-fw fa-sign-in" aria-hidden="true">&nbsp</i> <?php echo HEADER_TITLE_LOGIN; ?></a></li>
        <?php } ?>
      <?php } } ?>

      <?php if ($_SESSION['cart']->count_contents() != 0) { ?>
        <li><a class="" href="<?php echo zen_href_link(FILENAME_SHOPPING_CART, '', 'NONSSL'); ?>">   
        <i class="fa-solid fa-xl fa-fw fa-cart-shopping" title="Shopping Cart"></i>
      <?php
          echo HEADER_TITLE_CART_CONTENTS;
          // Alternatively, if you want to display cart quantity and value, use the following line instead of the one above. Adapt for multiple languages if relevant.
          // echo $_SESSION['cart']->count_contents().' item(s) '. $currencies->format($_SESSION['cart']->show_total());
      ?>
      </a></li>

      <li><a href="<?php echo zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'); ?>">
        <i class="fa-solid fa-xl fa-fw  fa-truck-fast"></i>
        <?php echo HEADER_TITLE_CHECKOUT; ?></a></li>
      <?php }?>


      <li><?php echo '<a href="' . zen_href_link(FILENAME_SITE_MAP) . '">'; ?> <i class="fa fa-xl fa-fw fa-sitemap fa-flip-horizontal" aria-hidden="true">&nbsp</i> Sitemap</a></li>

      <li><?php echo ' <a href="' . zen_href_link(FILENAME_CONTACT_US, '', 'SSL') . '">'; ?><i class="fa fa-xl fa-fw fa-phone fa-flip-horizontal" aria-hidden="true">&nbsp</i> Contact</a></li>
  </ul>
</div>
<!-- eof sidebar navigation -->


<!--bof branding display-->
<div id="logoWrapper" class="group onerow-fluid">
    <div id="logo">
      <?php echo '<a href="' . HTTP_SERVER . DIR_WS_CATALOG . '">' . zen_image($template->get_template_dir(HEADER_LOGO_IMAGE, DIR_WS_TEMPLATE, $current_page_base,'images'). '/' . HEADER_LOGO_IMAGE, HEADER_ALT_TEXT) . '</a>'; ?>
      <?php if (HEADER_SALES_TEXT != '' || (SHOW_BANNERS_GROUP_SET2 != '' && $banner = zen_banner_exists('dynamic', SHOW_BANNERS_GROUP_SET2))) { ?>
    <div id="taglineWrapper">
<?php
  if (HEADER_SALES_TEXT != '') {
?>
      <div id="tagline"><?php echo HEADER_SALES_TEXT;?></div>
<?php
  }
?>
<?php
  if (SHOW_BANNERS_GROUP_SET2 != '' && $banner = zen_banner_exists('dynamic', SHOW_BANNERS_GROUP_SET2)) {
    if ($banner->RecordCount() > 0) {
?>
  <div id="bannerTwo" class="banners"><?php echo zen_display_banner('static', $banner);?></div>
<?php
    }
  }
?>
   </div>
<?php } // no HEADER_SALES_TEXT or SHOW_BANNERS_GROUP_SET2 ?>
 </div> 
</div>


<!-- tablet desktop search and icons -->
<div class="search_box"> 
   <?php require(DIR_WS_MODULES . zen_get_module_sidebox_directory('search_header.php')); ?>
</div>
 
  <div class="shoppingcart">
  <a href="<?php echo zen_href_link(FILENAME_LOGIN, '', 'SSL'); ?>" aria-label="<?php echo TABLE_HEADING_LOGIN_DETAILS; ?>">
    <i class="fa fa-user spacer2 navitem1t" aria-hidden="true" class="contactusicon;" role="img" aria-label="Login icon" >&nbsp</i>
</a>
    
    <a href="<?php echo zen_href_link('contact_us', '', 'SSL'); ?>"  aria-label="<?php echo BOX_INFORMATION_CONTACT; ?>">
    <i class="fa fa-envelope spacer1 navitem2t" aria-hidden="true" class="loginiconuser" role="img" aria-label="Contact icon">&nbsp</i></a>  
    
    <a href="<?php echo zen_href_link(FILENAME_SHOPPING_CART, '', 'NONSSL'); ?>" aria-label="<?php echo BOX_HEADING_SHOPPING_CART; ?>">
    <i class="fa fa-fw badge fa-lg navitem3t" aria-hidden="true" role="img" aria-label="Shopping cart icon">
        <span class="cart-count"><?php echo $_SESSION['cart']->count_contents(); ?></span>
        &#xf07a;  <!-- Font Awesome shopping cart icon --></i></a>
  </div> 
</div>

<!-- mobile search -->
<div class="search_box_mobile" id="test"> 
   <?php require(DIR_WS_MODULES . zen_get_module_sidebox_directory('search_header.php')); ?>
</div>

<!--eof branding display-->
<!--eof header logo and navigation display-->

 

<!--bof optional categories tabs navigation display-->
<?php require($template->get_template_dir('tpl_modules_categories_tabs.php',DIR_WS_TEMPLATE, $current_page_base,'templates'). '/tpl_modules_categories_tabs.php'); ?>
<!--eof optional categories tabs navigation display-->

<!--bof header ezpage links-->
<?php if (EZPAGES_STATUS_HEADER == '1' or (EZPAGES_STATUS_HEADER == '2' && zen_is_whitelisted_admin_ip())) { ?>
<?php   require($template->get_template_dir('tpl_ezpages_bar_header.php',DIR_WS_TEMPLATE, $current_page_base,'templates'). '/tpl_ezpages_bar_header.php'); ?>
<?php } ?>
<!--eof header ezpage links-->

<script>
$(document).ready(function() {
  // Toggle sidebar on button click
  $(".btn").click(function(e) {
    e.stopPropagation(); // Prevent click event from propagating to the document
    var $this = $(this);
    var $sidebar = $(".sidebar");
    var $icon = $("#menu-icon"); // Reference to the icon inside the button
    var $overlay = $("#overlay"); // Reference to the overlay

    // Toggle the sidebar visibility
    $sidebar.toggleClass('is-hidden');
    $overlay.toggle(); // Toggle the overlay visibility

    // Change icon based on sidebar state
    if ($sidebar.hasClass("is-hidden")) {
      $icon.removeClass('fa-times').addClass('fa-bars');
    } else {
      $icon.removeClass('fa-bars').addClass('fa-times');
    }
  });

  // Close sidebar and overlay when clicking outside of it
  $(document).click(function(e) {
    var $sidebar = $(".sidebar");
    var $btn = $(".btn");
    var $overlay = $("#overlay");

    // Check if the click was outside the sidebar and button
    if (!$(e.target).closest($sidebar).length && !$(e.target).closest($btn).length) {
      // If outside, close the sidebar and hide the overlay
      if (!$sidebar.hasClass('is-hidden')) {
        $sidebar.addClass('is-hidden');
        $overlay.hide(); // Hide the overlay
        $("body").removeClass('no-scroll'); // Enable scrolling
        $("#menu-icon").removeClass('fa-times').addClass('fa-bars');
      }
    }
  });

  // Prevent sidebar from closing if the sidebar itself is clicked
  $(".sidebar").click(function(e) {
    e.stopPropagation();
  });

  // Hide overlay when it's clicked
  $("#overlay").click(function() {
    $(".sidebar").addClass('is-hidden');
    $(this).hide(); // Hide the overlay
    $("body").removeClass('no-scroll'); // Enable scrolling
    $("#menu-icon").removeClass('fa-times').addClass('fa-bars');
  });
});
</script>

<!-- eof sidebar nav script -->

</div>
