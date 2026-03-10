Version 1.2

Fully featured image gallery with thumbnails, drag/swipe scrolling, infinite looping, arrows, and a modal lightbox 

• A main image display

• A thumbnail strip (left side or horizontal on mobile)

• Click + hover thumbnail switching

• Drag-to-scroll thumbnails (mouse + touch)

• Optional infinite thumbnail looping

• Navigation arrows (if needed)

• A modal/lightbox when clicking the main image

• Swipe support inside the modal (mobile)

• Prevents background scrolling while modal is open


********************************************************
INSTALL
********************************************************

Upload the files directly to your Zen Cart root folder (where index.php is located).

No additional setup required — the script will automatically detect thumbnails and the main image on your product pages.

Once uploaded, the gallery is ready to use — click, swipe, and drag through your product images immediately.

********************************************************
Control Infinite Scrolling / Looping
path:  \includes\templates\responsive_classic\jscript\jscript_image_modals.js
********************************************************

const MAX_THUMBS_LOOP = null;

Determines when the thumbnail list loops back to the beginning.

null → Unlimited looping (current infinite scroll).

Number (e.g., 5) → Looping only happens if the number of thumbnails exceeds this value.

Setting a high number effectively disables looping.

********************************************************

Important Note:
Arrows and infinite scrolling will only appear or function if the thumbnails don’t all fit within the visible container. If all images are visible at once, scrolling and arrows won’t be necessary. will only where when there is not enough space to fit all the images
