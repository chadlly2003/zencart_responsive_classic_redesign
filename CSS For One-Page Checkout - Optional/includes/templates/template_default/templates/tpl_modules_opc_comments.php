 
<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<!--bof comments block -->
  
    <div id="checkoutComments">
      <fieldset class="shipping" id="comments"><legend><?php echo TABLE_HEADING_COMMENTS; ?></legend>
      <!-- bof updated for WCAG -->
      <?php echo str_replace(
    '<textarea',
    '<textarea aria-label="Comments"',
    zen_draw_textarea_field('comments', '45', '3')); ?>
      <!-- eof of updated for WCAG -->
</fieldset>
    </div>
  
<!--eof comments block -->
