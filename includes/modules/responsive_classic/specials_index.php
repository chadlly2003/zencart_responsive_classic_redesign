<?php

/**
 * specials_index module
 *
 * @copyright Copyright 2003-2024 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2024 May 24 Modified in v2.1.0-alpha1 $
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// initialize vars
$categories_products_id_list = [];
$list_of_products = '';
$specials_index_query = '';
$display_limit = '';

if ((($manufacturers_id > 0 && empty($_GET['filter_id'])) || !empty($_GET['music_genre_id']) || !empty($_GET['record_company_id'])) || empty($new_products_category_id)) {
    $specials_index_query = "SELECT p.products_id, p.products_image, pd.products_name, p.master_categories_id
                           FROM (" . TABLE_PRODUCTS . " p
                           LEFT JOIN " . TABLE_SPECIALS . " s ON p.products_id = s.products_id
                           LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id )
                           WHERE p.products_id = s.products_id
                           AND p.products_id = pd.products_id
                           AND p.products_status = 1 AND s.status = 1
                           AND pd.language_id = " . (int)$_SESSION['languages_id'];
} else {
    // get all products and cPaths in this subcat tree
    $productsInCategory = zen_get_categories_products_list((($manufacturers_id > 0 && !empty($_GET['filter_id'])) ? zen_get_generated_category_path_rev($_GET['filter_id']) : $cPath), false, true, 0, $display_limit);

    if (is_array($productsInCategory) && sizeof($productsInCategory) > 0) {
        // build products-list string to insert into SQL query
        foreach ($productsInCategory as $key => $value) {
            $list_of_products .= $key . ', ';
        }
        $list_of_products = substr($list_of_products, 0, -2); // remove trailing comma
        $specials_index_query = "SELECT DISTINCT p.products_id, p.products_image, pd.products_name, p.master_categories_id
                             FROM (" . TABLE_PRODUCTS . " p
                             LEFT JOIN " . TABLE_SPECIALS . " s ON p.products_id = s.products_id
                             LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id )
                             WHERE p.products_id = s.products_id
                             AND p.products_id = pd.products_id
                             AND p.products_status = 1 AND s.status = 1
                             AND pd.language_id = " . (int)$_SESSION['languages_id'] . "
                             AND p.products_id in (" . $list_of_products . ")";
    }
}
if ($specials_index_query !== '') {
    $specials_index = $db->ExecuteRandomMulti($specials_index_query, MAX_DISPLAY_SPECIAL_PRODUCTS_INDEX);
}

$row = 0;
$col = 0;
$list_box_contents = [];
$title = '';

$num_products_count = ($specials_index_query === '') ? 0 : $specials_index->RecordCount();

// show only when 1 or more
if ($num_products_count > 0) {
    if ($num_products_count < SHOW_PRODUCT_INFO_COLUMNS_SPECIALS_PRODUCTS || SHOW_PRODUCT_INFO_COLUMNS_SPECIALS_PRODUCTS == 0) {
        $col_width = floor(100 / $num_products_count);
    } else {
        $col_width = floor(100 / SHOW_PRODUCT_INFO_COLUMNS_SPECIALS_PRODUCTS);
    }

    $list_box_contents = [];
    while (!$specials_index->EOF) {
        $product_info = new Product((int)$specials_index->fields['products_id']);
        $data = array_merge($specials_index->fields, $product_info->getDataForLanguage());

        $products_price = zen_get_products_display_price($data['products_id']);
        if (!isset($productsInCategory[$data['products_id']])) {
            $productsInCategory[$data['products_id']] = zen_get_generated_category_path_rev($data['master_categories_id']);
        }

        $zco_notifier->notify('NOTIFY_MODULES_SPECIALS_INDEX_B4_LIST_BOX', [], $data, $products_price);

        $data['products_name'] = zen_get_products_name($data['products_id']);
        $list_box_contents[$row][$col] = [
            'params' => 'class="centerBoxContentsSpecials centeredContent back"' . ' ' . '',
            'text' => (($data['products_image'] === '' && PRODUCTS_IMAGE_NO_IMAGE_STATUS == 0)
                    ? ''
                    : '<a href="' . zen_href_link(
                        zen_get_info_page($data['products_id']),
                        'cPath=' . $productsInCategory[$data['products_id']] . '&products_id=' . (int)$data['products_id']
                    ) . '">'
                    . zen_image(DIR_WS_IMAGES . $data['products_image'], $data['products_name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT)
                    . '</a><br>')
                . '<a href="' . zen_href_link(zen_get_info_page($data['products_id']), 'cPath=' . $productsInCategory[$data['products_id']] . '&products_id=' . $data['products_id']) . '">' . $data['products_name']
                . '</a><br>' . $products_price,
        ];

        $col++;
        if ($col > (SHOW_PRODUCT_INFO_COLUMNS_SPECIALS_PRODUCTS - 1)) {
            $col = 0;
            $row++;
        }
        $specials_index->MoveNextRandom();
    }

    if ($specials_index->RecordCount() > 0) {
        $title = '<h2 class="centerBoxHeading">' . sprintf(TABLE_HEADING_SPECIALS_INDEX, $zcDate->output('%B')) . '</h2>';
        $zc_show_specials = true;
    }
}
