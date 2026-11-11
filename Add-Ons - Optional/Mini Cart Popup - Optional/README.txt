# Mini Cart – AJAX Add to Cart for Zen Cart

A responsive AJAX-powered Mini Cart for Zen Cart that allows customers to add products, update quantities, and remove items without refreshing the page.

Designed to improve the shopping experience on desktop and mobile while maintaining compatibility with Zen Cart's existing shopping cart system.

## Features

### AJAX Add to Cart
- Add products without refreshing the page.
- Supports homepage, category listings, and product information pages.
- Supports Featured Products, New Products, Specials, and product recommendations.
- Preserves the customer's current scroll position.
- Prevents homepage products from reordering after adding an item.
- Automatically displays the Mini Cart after a successful addition.

### Mini Cart Popup
- Displays product images and names.
- Clickable product images and names link to the product information page.
- Displays selected product attributes.
- Displays product quantities and prices.
- Shows the current cart subtotal.
- Includes Continue Shopping and Checkout buttons.
- Automatically closes after 15 seconds of inactivity.
- Pauses the inactivity timer while the customer interacts with the Mini Cart.
- Supports manual closing, Escape key, and mobile swipe-to-dismiss.

### AJAX Quantity Controls
- Increase product quantity using the + button.
- Decrease product quantity using the − button.
- Remove products using the trash-can button.
- Updates product totals and cart subtotal without refreshing.
- Supports Zen Cart minimum, maximum, and quantity increment settings.
- Retrieves updated cart information from the server.
- Keeps selected product attributes associated with their cart items.

### Product Attributes
- Supports radio-button and dropdown attributes.
- Validates required selections before adding products.
- Displays a message when an attribute has not been selected.
- Automatically scrolls to the missing attribute.
- Displays selected attributes in an HTML unordered list.
- Supports attribute information stored in the Zen Cart shopping cart session.

### Additional Features
- Product image fallback when an image is missing.
- Hides duplicate Add to Cart success messages.
- Preserves other Zen Cart warnings and error messages.
- Responsive layout for desktop, tablet, and mobile.
- Works with regular product, document product, and music product pages.

## Installation

**Important:** Back up your existing files before installation.

 
Installation Instructions

Upload the add-on files to the appropriate folders within your active Zen Cart template directory.

Upload the three rcrmc_ PHP files (rcrmc_cart_add.php, rcrmc_cart_quantity.php, and rcrmc_cart_state.php) directly to the root directory of your Zen Cart installation, alongside your main index.php file.

Important: These three PHP files must be placed in the root directory, not inside the includes/ folder or your template directory.

Steps after Installation
**In the Admin go to Configuration/My Store
Scroll down to Display Cart After Adding Product (must be set to false)
