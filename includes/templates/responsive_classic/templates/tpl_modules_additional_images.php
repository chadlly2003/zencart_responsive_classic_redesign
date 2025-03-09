<?php
/**
 * Loaded by product-type template to display additional product images.
 *
 * @copyright Copyright 2003-2024 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2024 Jan 31 New in v2.0.0-beta1 $
 */

require DIR_WS_MODULES . zen_get_module_directory('additional_images.php');

if (empty($flag_show_product_info_additional_images) || empty($modal_images)) {
    return;
}
?>
<div id="productAdditionalImages" class="image-grid">
<?php
    $i = 0; // Counter for modal IDs
    foreach ($modal_images as $image) {
        $i++;

        // Generate modal variables for each image (with unique IDs)
        $modal_id = 'imageModal' . $i;
        $modal_content_id = 'modalContent' . $i; // Ensure unique modal content IDs
        $modal_link_js = 'openModal(\'' . $modal_id . '\')';

        // Generate the image wrapped inside an anchor tag
        $modal_link_img = '<a href="javascript:void(0);" onclick="' . $modal_link_js . '" role="button" aria-label="Open modal for ' . htmlspecialchars($image['products_name']) . '">'
            . zen_image($image['base_image'], $image['products_name'], MEDIUM_IMAGE_WIDTH, MEDIUM_IMAGE_HEIGHT)
            . '</a>';

        // Now we call the modal template for each image
        require $template->get_template_dir('tpl_image_additional.php', DIR_WS_TEMPLATE, $current_page_base, 'modalboxes') . '/tpl_image_additional.php';
    }
?>
</div>
