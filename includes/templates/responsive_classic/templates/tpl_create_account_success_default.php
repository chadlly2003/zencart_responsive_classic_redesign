<?php
/**
 * Page Template
 *
 * Loaded automatically by index.php?main_page=create-account_success.
 * Displays confirmation that a new account has been created.
 *
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2020 Dec 25 Modified in v1.5.8-alpha $
 */
?>
<div class="centerColumn" id="createAcctSuccess">
<h1 id="createAcctSuccessHeading"><?php echo HEADING_TITLE; ?></h1>

<div id="createAcctSuccessMainContent" class="content"><?php echo TEXT_ACCOUNT_CREATED; ?></div>

<fieldset>
<legend><?php echo PRIMARY_ADDRESS_TITLE; ?></legend>
<?php
/**
 * Used to loop thru and display address book entries
 */
  foreach ($addressArray as $addresses) {
?>
<h2 class="addressBookDefaultName"><?php echo zen_output_string_protected($addresses['firstname'] . ' ' . $addresses['lastname']); ?></h2>

<address><?php echo zen_address_format($addresses['format_id'], $addresses['address'], true, ' ', '<br>'); ?></address>
  
<div class="buttonRow button_accounts "><?php echo '<a href="' . zen_href_link(FILENAME_ADDRESS_BOOK_PROCESS, 'edit=' . $addresses['address_book_id'], 'SSL') . '">' . zen_image_button(BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT) . '</a> <a href="' . zen_href_link(FILENAME_ADDRESS_BOOK_PROCESS, 'delete=' . $addresses['address_book_id'], 'SSL') . '">' . zen_image_button(BUTTON_IMAGE_DELETE, BUTTON_DELETE_ALT) . '</a>'; ?></div>

<?php
  }
?>
</fieldset>
 

<div class="buttonRow fowardcontinue"><?php echo '<a href="' . $origin_href . '">' . zen_image_button(BUTTON_IMAGE_CONTINUE, BUTTON_CONTINUE_ALT) . '</a>'; ?></div>
</div>
