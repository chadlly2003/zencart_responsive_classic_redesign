jQuery(function ($) {

  /* =====================
     CONFIG
  ===================== */
  const SHOW_THUMB_ARROWS = true;
  const SWIPE_SPEED = 1;

  // SETTING: limit infinite scroll based on number of thumbnails
  // Set a number to enforce looping only if thumbs.length > MAX_THUMBS_LOOP
  // Set to null for unlimited looping (current infinite scroll)
  const MAX_THUMBS_LOOP = null; 

  /* =====================
     ELEMENTS
  ===================== */
  const thumbsContainer = document.querySelector('.left-thumbs');
  const leftArrow = document.querySelector('.thumb-arrow-left');
  const rightArrow = document.querySelector('.thumb-arrow-right');
  const mainImage = document.getElementById('mainImage');

  if (!thumbsContainer || !mainImage) return;

  /* =====================
     STATE
  ===================== */
  const thumbs = Array.from(thumbsContainer.querySelectorAll('img'));
  let activeIndex = 0;
  let isDragging = false;
  let startX = 0, startY = 0;
  let startLeft = 0, startTop = 0;

  /* =====================
     HELPERS
  ===================== */
  function isHorizontal() {
    return window.matchMedia('(max-width: 768px)').matches;
  }

  function preloadImage(src) {
    if (!src) return;
    const img = new Image();
    img.src = src;
  }

  function scrollToThumb(thumb) {
    if (!thumb) return;

    const container = thumbsContainer;
    const containerRect = container.getBoundingClientRect();
    const thumbRect = thumb.getBoundingClientRect();

    if (isHorizontal()) {
      if (thumbRect.left < containerRect.left) {
        container.scrollLeft -= (containerRect.left - thumbRect.left);
      } else if (thumbRect.right > containerRect.right) {
        container.scrollLeft += (thumbRect.right - containerRect.right);
      }
    } else {
      if (thumbRect.top < containerRect.top) {
        container.scrollTop -= (containerRect.top - thumbRect.top);
      } else if (thumbRect.bottom > containerRect.bottom) {
        container.scrollTop += (thumbRect.bottom - containerRect.bottom);
      }
    }
  }

  /* =====================
     ACTIVE THUMB
  ===================== */
  function setActiveThumb(index, doScroll = true) {
    if (index < 0) index = thumbs.length - 1;
    if (index >= thumbs.length) index = 0;
    activeIndex = index;

    const thumb = thumbs[activeIndex];

    thumbs.forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');

    if (mainImage.src !== thumb.dataset.large) {
      mainImage.src = thumb.dataset.large;
      preloadImage(thumb.dataset.large);
    }

    if (doScroll) scrollToThumb(thumb);
  }

  function showNextThumb() {
    setActiveThumb(activeIndex + 1);
  }

  function showPrevThumb() {
    setActiveThumb(activeIndex - 1);
  }

  /* =====================
     THUMB EVENTS
  ===================== */
  thumbs.forEach((thumb, i) => {
    thumb.dataset.index = i;
    thumb.setAttribute('draggable', 'false');

    thumb.addEventListener('click', () => setActiveThumb(i, true));
    thumb.addEventListener('mouseenter', () => setActiveThumb(i, false));
  });

  /* =====================
   DRAG SCROLL (LOOPING)
  ===================== */
  let activePointerId = null;

  thumbsContainer.addEventListener('pointerdown', e => {
    if (e.button !== 0) return;

    isDragging = true;
    activePointerId = e.pointerId;

    startX = e.clientX;
    startY = e.clientY;
    startLeft = thumbsContainer.scrollLeft;
    startTop = thumbsContainer.scrollTop;

    thumbsContainer.setPointerCapture(activePointerId);
  });

  thumbsContainer.addEventListener('pointermove', e => {
    if (!isDragging || e.pointerId !== activePointerId) return;

    const dx = e.clientX - startX;
    const dy = e.clientY - startY;

    if (isHorizontal()) {
      thumbsContainer.scrollLeft = startLeft - dx * SWIPE_SPEED;

      const maxScroll = thumbsContainer.scrollWidth - thumbsContainer.clientWidth;
      const current = thumbsContainer.scrollLeft;
      const TOLERANCE = 1;

      if (!MAX_THUMBS_LOOP || thumbs.length > MAX_THUMBS_LOOP) {
        if (current <= TOLERANCE) {
          thumbsContainer.scrollLeft = maxScroll - TOLERANCE;
          startLeft = thumbsContainer.scrollLeft;
          startX = e.clientX;
        }
        if (current >= maxScroll - TOLERANCE) {
          thumbsContainer.scrollLeft = TOLERANCE;
          startLeft = thumbsContainer.scrollLeft;
          startX = e.clientX;
        }
      }
    } else {
      thumbsContainer.scrollTop = startTop - dy * SWIPE_SPEED;

      const maxScroll = thumbsContainer.scrollHeight - thumbsContainer.clientHeight;

      if (!MAX_THUMBS_LOOP || thumbs.length > MAX_THUMBS_LOOP) {
        if (thumbsContainer.scrollTop <= 0) {
          thumbsContainer.scrollTop = maxScroll - 2;
          startTop = thumbsContainer.scrollTop;
          startY = e.clientY;
        }
        if (thumbsContainer.scrollTop >= maxScroll) {
          thumbsContainer.scrollTop = 2;
          startTop = thumbsContainer.scrollTop;
          startY = e.clientY;
        }
      }
    }

    e.preventDefault();
  }, { passive: false });

  function stopDragging(e) {
    if (activePointerId !== null && thumbsContainer.hasPointerCapture(activePointerId)) {
      thumbsContainer.releasePointerCapture(activePointerId);
    }

    isDragging = false;
    activePointerId = null;
  }

  thumbsContainer.addEventListener('pointerup', stopDragging);
  thumbsContainer.addEventListener('pointercancel', stopDragging);
  thumbsContainer.addEventListener('lostpointercapture', stopDragging);

  thumbsContainer.addEventListener('dragstart', e => {
    e.preventDefault();
  });

  /* =====================
   ARROWS (ONLY SHOW IF NEEDED & FINITE SCROLL)
  ===================== */
  function updateArrows() {
    if (!SHOW_THUMB_ARROWS) {
      if (leftArrow) leftArrow.style.display = 'none';
      if (rightArrow) rightArrow.style.display = 'none';
      return;
    }

    if (!leftArrow || !rightArrow) return;

    const horizontal = isHorizontal();
    const container = thumbsContainer;

    // Determine if overflow exists (can scroll)
    const canScroll = horizontal
      ? container.scrollWidth > container.clientWidth + 2
      : container.scrollHeight > container.clientHeight + 2;

    if (!canScroll) {
      leftArrow.style.display = 'none';
      rightArrow.style.display = 'none';
      return;
    }

    // Always show arrows for infinite looping
    leftArrow.style.display = 'flex';
    rightArrow.style.display = 'flex';

    if (horizontal) {
      leftArrow.innerHTML = '❮';
      rightArrow.innerHTML = '❯';
    } else {
      leftArrow.innerHTML = '⌃';
      rightArrow.innerHTML = '⌄';
    }

    leftArrow.onclick = showPrevThumb;
    rightArrow.onclick = showNextThumb;
  }

  /* =====================
     WAIT FOR ALL THUMBS TO LOAD BEFORE UPDATING ARROWS
  ===================== */
  function onThumbsLoaded(callback) {
    let loadedCount = 0;
    thumbs.forEach(img => {
      if (img.complete) {
        loadedCount++;
      } else {
        img.addEventListener('load', () => {
          loadedCount++;
          if (loadedCount === thumbs.length) callback();
        });
      }
    });
    if (loadedCount === thumbs.length) callback();
  }

  onThumbsLoaded(() => {
    requestAnimationFrame(() => {
      updateArrows();
    });
  });

  /* =====================
     OBSERVERS / RESIZE
  ===================== */
  if ('ResizeObserver' in window) {
    const resizeObserver = new ResizeObserver(() => {
      requestAnimationFrame(updateArrows);
    });
    resizeObserver.observe(thumbsContainer);
  }

  window.addEventListener('resize', () => {
    const currentIndex = activeIndex;
    requestAnimationFrame(() => {
      setActiveThumb(currentIndex, false);
      scrollToThumb(thumbs[currentIndex]);
      updateArrows();
    });
  });

  /* =====================
     MODAL & SCROLL CONTROL
  ===================== */
  function disableScroll() {
    const scrollY = window.scrollY || document.documentElement.scrollTop;
    document.body.style.position = 'fixed';
    document.body.style.top = `-${scrollY}px`;
    document.body.style.left = '0';
    document.body.style.right = '0';
    document.body.style.overflow = 'hidden';
    document.body.style.width = '100%';
    document.documentElement.style.overflow = 'hidden';
    document.body.dataset.scrollY = scrollY;
  }

  function enableScroll() {
    const scrollY = document.body.dataset.scrollY;
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.left = '';
    document.body.style.right = '';
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
    window.scrollTo(0, parseInt(scrollY || 0));
  }

  const modal = document.getElementById('modal');
  const modalImg = modal?.querySelector('#modalImg');
  const modalTitle = modal?.querySelector('#modalTitle');
  const closeBtn = modal?.querySelector('.close');
  const prevArrowModal = modal?.querySelector('.arrow-left');
  const nextArrowModal = modal?.querySelector('.arrow-right');

  if (modal && modalImg && closeBtn) {
    function openModal() {
      modal.classList.add('show');
      modalImg.src = thumbs[activeIndex].dataset.large;
      if (modalTitle) modalTitle.textContent = thumbs[activeIndex].alt || '';
      closeBtn.focus();
      disableScroll();
    }
    function closeModal() {
      modal.classList.remove('show');
      enableScroll();
    }

    mainImage.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);

    if (prevArrowModal) {
      prevArrowModal.addEventListener('click', () => {
        showPrevThumb();
        modalImg.src = thumbs[activeIndex].dataset.large;
      });
    }
    if (nextArrowModal) {
      nextArrowModal.addEventListener('click', () => {
        showNextThumb();
        modalImg.src = thumbs[activeIndex].dataset.large;
      });
    }

    modal.addEventListener('click', e => {
      if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', e => {
      if (!modal.classList.contains('show')) return;
      if (e.key === 'Escape') closeModal();
      if (e.key === 'ArrowRight') showNextThumb();
      if (e.key === 'ArrowLeft') showPrevThumb();
      modalImg.src = thumbs[activeIndex].dataset.large;
    });
  }

  /* =====================
     MODAL SWIPE (MOBILE)
  ===================== */
  if (modal && modalImg && closeBtn) {
    let touchStartX = 0, touchStartY = 0, isSwiping = false;
    const SWIPE_THRESHOLD = 40;

    modal.addEventListener('touchstart', e => {
      const touch = e.touches[0];
      touchStartX = touch.clientX;
      touchStartY = touch.clientY;
      isSwiping = true;
    }, { passive: true });

    modal.addEventListener('touchmove', e => {
      if (!isSwiping) return;
      const touch = e.touches[0];
      const dx = touch.clientX - touchStartX;
      const dy = touch.clientY - touchStartY;

      if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > SWIPE_THRESHOLD) {
        if (dx < 0) showNextThumb(); else showPrevThumb();
        modalImg.src = thumbs[activeIndex].dataset.large;
        if (modalTitle) modalTitle.textContent = thumbs[activeIndex].alt || '';
        isSwiping = false;
      }
    }, { passive: true });

    modal.addEventListener('touchend', () => { isSwiping = false; });
  }

  /* =====================
     INIT
  ===================== */
  setActiveThumb(activeIndex);
  // Arrows will now initialize correctly once thumbs load
});
