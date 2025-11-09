<?php
/**
 * @copyright Copyright 2003-2024 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2024 Feb 03 New in v2.0.0-beta1 $
 */

// Ensure variables are defined to prevent warnings
$modal_link_attributes = isset($modal_link_attributes) ? $modal_link_attributes : '';
$modal_link_id = isset($modal_link_id) ? $modal_link_id : 'modal-link-default';
$modal_link_img = isset($modal_link_img) ? $modal_link_img : '';

$modal_id = isset($modal_id) ? $modal_id : 'modal-default';
$modal_content_id = isset($modal_content_id) ? $modal_content_id : 'modal-content-default';

$image = isset($image) ? $image : ['products_image_large' => '', 'products_name' => ''];

?>

<!-- Modal -->
<div id="<?= $modal_id; ?>" class="imgmodal">
    <div id="<?= $modal_content_id; ?>" class="imgmodal-content">
        <div onclick="closeModal('<?= $modal_id; ?>')">
            <?= zen_image($image['products_image_large'], $image['products_name'], '', '', 'class="centered-image"'); ?>
            <div class="imgmodal-close"><i class="fa-solid fa-circle-xmark"></i></div>
            <div class="center"><?= $image['products_name']; ?></div>
        </div>
    </div>
</div>

<!-- Use modal link generated from the previous file -->
<div class="back">
    <?= $modal_link_img; ?>
</div>
