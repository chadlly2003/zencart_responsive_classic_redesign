<div class="centerColumn" id="featuredCategoryDefault">
<h1 id="featuredCateoryDefaultHeading"><?php echo HEADING_TITLE; ?></h1>

<?php if (count($listing) === 0) { ?>
    <div class="noFeaturedCategories">
        <?php echo defined('TEXT_NO_FEATURED_CATEGORIES') 
            ? TEXT_NO_FEATURED_CATEGORIES 
            : 'No products to show.'; ?>
    </div>
<?php } ?>

<?php
$list_box_contents = [];
$row = 0;
$col = 0;

if (count($listing) !== 0) {

    $col_width = floor(100 / SHOW_PRODUCT_INFO_COLUMNS_FEATURED_PRODUCTS);

    foreach ($listing as $record) {
        $lc_text = '<a href="' . zen_href_link(FILENAME_DEFAULT, 'cPath=' . zen_get_generated_category_path_rev($record['categories_id'])) . '">'
                 . zen_image(DIR_WS_IMAGES . $record['categories_image'], $record['categories_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT);
        $lc_text .= '<div class="categoryName">' . $record['categories_name'] . '</div>';
        $lc_text .= '</a>';

        $list_box_contents[$row][$col] = [
            'params' => 'class="centerBoxContentsFeatured centeredContent back"',
            'text' => $lc_text,
        ];

        $col++;

        if ($col >= SHOW_PRODUCT_INFO_COLUMNS_FEATURED_PRODUCTS) {
            $col = 0;
            $row++;
        }
    }
}

$title = '';
require $template->get_template_dir(
    'tpl_columnar_display.php',
    DIR_WS_TEMPLATE,
    $current_page_base,
    'common'
) . '/tpl_columnar_display.php';
?>
</div>
