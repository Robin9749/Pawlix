document.addEventListener('DOMContentLoaded', function() {
    // 1. Mobile Navigation Toggle
    const menuToggle = document.getElementById('menuToggle');
    const nav = document.querySelector('.nav');

    if (menuToggle && nav) {
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('show');
        });
    }

    // 2. Wishlist Heart Toggle (❤️ <-> 🤍)
    const wishButtons = document.querySelectorAll('.wish-btn');
    wishButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.toggle('active');
            
            const icon = this.querySelector('span') || this;
            if (this.classList.contains('active')) {
                icon.textContent = '❤️';
            } else {
                icon.textContent = '🤍';
            }
        });
    });

    // 3. Filter Logic for Dog Cards
    const btnApply = document.getElementById('btnApplyFilters');
    const btnClear = document.getElementById('btnClearFilters');
    const resultCards = document.querySelectorAll('.result-card');
    const noResultsMsg = document.getElementById('noResultsMsg');

    if (btnApply && resultCards.length > 0) {
        btnApply.addEventListener('click', filterDogs);
    }

    if (btnClear && resultCards.length > 0) {
        btnClear.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.filters input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = false);

            resultCards.forEach(card => {
                card.style.display = 'block';
            });

            if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        });
    }

    function filterDogs() {
        const selectedBreeds = Array.from(document.querySelectorAll('.filter-group:nth-of-type(1) input[type="checkbox"]:checked')).map(cb => cb.value.toLowerCase());
        const selectedAges = Array.from(document.querySelectorAll('.filter-group:nth-of-type(2) input[type="checkbox"]:checked')).map(cb => cb.value.toLowerCase());
        const selectedSizes = Array.from(document.querySelectorAll('.filter-group:nth-of-type(3) input[type="checkbox"]:checked')).map(cb => cb.value.toLowerCase());

        let visibleCount = 0;

        resultCards.forEach(card => {
            const breed = (card.dataset.breed || '').toLowerCase();
            const age = (card.dataset.ageCategory || '').toLowerCase();
            const size = (card.dataset.size || '').toLowerCase();

            const matchBreed = selectedBreeds.length === 0 || selectedBreeds.some(b => breed.includes(b) || b.includes(breed));
            const matchAge = selectedAges.length === 0 || selectedAges.some(a => age.includes(a));
            const matchSize = selectedSizes.length === 0 || selectedSizes.some(s => size.includes(s));

            if (matchBreed && matchAge && matchSize) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noResultsMsg) {
            noResultsMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    }

    // 4. Pagination Buttons Interactivity
    const pageButtons = document.querySelectorAll('.page-btn');
    pageButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.classList.contains('page-nav')) return;
            pageButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
});