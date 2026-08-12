document.addEventListener('DOMContentLoaded', function() {
    // 1. Mobile Navigation Toggle
    const menuToggle = document.getElementById('menuToggle');
    const nav = document.querySelector('.nav');

    if (menuToggle && nav) {
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('show');
        });
    }

    // 2. Interactive Image Gallery (Thumbnails & Side Arrow Switcher)
    const mainDogImg = document.getElementById('mainDogImage');
    const thumbItems = document.querySelectorAll('.thumb-item');
    const prevBtn = document.getElementById('prevImgBtn');
    const nextBtn = document.getElementById('nextImgBtn');

    if (mainDogImg && thumbItems.length > 0) {
        let currentIndex = 0;

        // Extract image sources reliably from data-img or inner img src
        function getSrc(thumb) {
            const dataImg = thumb.getAttribute('data-img');
            if (dataImg) return dataImg;

            const imgTag = thumb.querySelector('img');
            if (imgTag) {
                return imgTag.getAttribute('src') || imgTag.src;
            }
            return '';
        }

        const imagesList = Array.from(thumbItems).map(getSrc);

        function updateGallery(index) {
            if (!imagesList.length || !mainDogImg) return;

            // Seamless infinite loop around thumbnails
            if (index < 0) {
                currentIndex = imagesList.length - 1;
            } else if (index >= imagesList.length) {
                currentIndex = 0;
            } else {
                currentIndex = index;
            }

            const newSrc = imagesList[currentIndex];
            if (newSrc) {
                mainDogImg.src = newSrc;
            }

            // Update active orange border highlight on thumbnail
            thumbItems.forEach((item, i) => {
                if (i === currentIndex) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }

        // Click Event Listeners for Thumbnails
        thumbItems.forEach((thumb, index) => {
            thumb.addEventListener('click', function() {
                updateGallery(index);
            });
        });

        // Click Event Listener for Left Arrow (<)
        if (prevBtn) {
            prevBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                updateGallery(currentIndex - 1);
            });
        }

        // Click Event Listener for Right Arrow (>)
        if (nextBtn) {
            nextBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                updateGallery(currentIndex + 1);
            });
        }
    }
});