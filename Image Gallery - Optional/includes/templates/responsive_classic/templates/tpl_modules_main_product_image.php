 <?php
/**
 * Custom Product Image Gallery
 * Zen Cart Compatible
 */

require DIR_WS_MODULES . zen_get_module_directory('additional_images.php');

$gallery_images = [];

// Helper to make paths correct
function clean_image_path($path) {
    if (empty($path)) return '';

    // Remove leading "images/" if present
    if (strpos($path, 'images/') === 0) {
        $path = substr($path, strlen('images/'));
    }

    return $path;
}

function get_image_path($img) {
    if (!empty($img['base_image'])) return clean_image_path($img['base_image']);
    if (!empty($img['image'])) return clean_image_path($img['image']);
    return '';
}

// Main product image
if (!empty($products_image)) {
    $gallery_images[] = [
        'image' => get_image_path(['base_image' => $products_image]),
        'alt'   => $products_name
    ];
}

// Additional product images
if (!empty($modal_images)) {
    foreach ($modal_images as $img) {
        $gallery_images[] = [
            'image' => get_image_path($img),
            'alt'   => $img['products_name']
        ];
    }
}

// Nothing to show
if (empty($gallery_images)) {
    return;
}
?>

<div class="gallery-wrap">

  <!-- THUMBS -->
  <div class="thumbs-container <?php echo (count($gallery_images) === 1) ? 'hide-thumbs' : ''; ?>">
    <div class="thumb-arrow thumb-arrow-left">&#10094;</div>

    <div class="left-thumbs">
      <?php foreach ($gallery_images as $img) { ?>
        <img
          class="thumb"
          src="images/<?php echo $img['image']; ?>"
          data-large="images/<?php echo $img['image']; ?>"
          alt="<?php echo htmlspecialchars($img['alt']); ?>">
      <?php } ?>
    </div>

    <div class="thumb-arrow thumb-arrow-right">&#10095;</div>
  </div>

  <!-- MAIN IMAGE -->
  <div class="main-image">
    <img
      id="mainImage"
      src="images/<?php echo $gallery_images[0]['image']; ?>"
      data-large="images/<?php echo $gallery_images[0]['image']; ?>"
      alt="<?php echo htmlspecialchars($gallery_images[0]['alt']); ?>">
  </div>

</div>

<!-- MODAL -->
<div class="modal <?php echo (count($gallery_images) === 1) ? 'single-image' : ''; ?>" id="modal">
  <span class="close">&times;</span>
  <span class="arrow arrow-left">&#10094;</span>
  <img id="modalImg" alt="">
  <span class="arrow arrow-right">&#10095;</span>
  <div id="modalTitle" class="modal_product_name">
    <?php echo htmlspecialchars($products_name); ?>
  </div>
</div>
