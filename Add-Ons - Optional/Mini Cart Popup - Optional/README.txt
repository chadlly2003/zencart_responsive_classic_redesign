# Zen Cart – Mini Cart Popup (Optional Add-On)

## Overview

The Mini Cart Popup is a lightweight shopping cart notification designed for Zen Cart's responsive template.

When a customer adds a product to their shopping cart, a popup appears showing the items in their cart, providing a quick confirmation without requiring them to visit the shopping cart page.

The add-on uses Zen Cart's existing shopping cart system and does not require AJAX.

## Features

- Automatically displays after a product is added to the cart.
- Shows product images, names, quantities, and prices.
- Product images and names link to their product information pages.
- Provides a link to view the shopping cart.
- Allows customers to continue shopping.
- Uses the existing Zen Cart shopping cart session.
- Supports products with attributes.
- Works with desktop, tablet, and mobile layouts.
- Uses a normal Zen Cart page reload — no AJAX required.
- Does not modify Zen Cart's core shopping cart functionality.
- Designed as an optional enhancement.

## How It Works

1. A customer selects a product and clicks **Add to Cart**.
2. Zen Cart processes the product using its normal shopping cart functionality.
3. The page reloads.
4. The Mini Cart checks whether the number of items in the cart has increased.
5. If an item was added, the Mini Cart Popup appears.
6. The customer can view the cart or continue shopping.

 
To Install
The add-on should be placed inside your active template directory.

For example, if you use the responsive_classic template:
includes/templates/responsive_classic/

**In the Admin go to Configuration/My Store
Scroll down to Display Cart After Adding Product (must be set to false)
