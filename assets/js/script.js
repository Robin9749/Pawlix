/* ===========================
   MOBILE MENU
=========================== */

const menuToggle = document.getElementById("menuToggle");
const nav = document.querySelector(".nav");

menuToggle.addEventListener("click", () => {
    nav.classList.toggle("show");
});



/* ===========================
   SEARCH DOGS
=========================== */

const search = document.getElementById("dogSearch");
const cards = document.querySelectorAll(".dog-card");
const noResults = document.getElementById("noResults");

search.addEventListener("keyup", function () {

    const value = this.value.toLowerCase();

    let visible = 0;

    cards.forEach(card => {

        const name = card.dataset.name.toLowerCase();

        const breed = card.querySelector(".breed").textContent.toLowerCase();

        if (name.includes(value) || breed.includes(value)) {

            card.style.display = "block";

            visible++;

        } else {

            card.style.display = "none";

        }

    });

    noResults.style.display = visible ? "none" : "block";

});





/* ===========================
   VIEW ALL BUTTON
=========================== */

document.getElementById("viewAllBtn").addEventListener("click", () => {

    search.value = "";

    cards.forEach(card => {

        card.style.display = "block";

    });

    noResults.style.display = "none";

    // Normal scroll
    window.scrollTo(0, document.querySelector(".explore").offsetTop);

});

/* ===========================
   NEWSLETTER
=========================== */

const newsletterBtn = document.getElementById("newsletterBtn");

newsletterBtn.addEventListener("click", () => {

    const email = document.getElementById("newsletterInput").value.trim();

    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email === "") {

        alert("Please enter your email.");

        return;

    }

    if (!pattern.test(email)) {

        alert("Please enter a valid email.");

        return;

    }

    alert("Thank you for subscribing!");

    document.getElementById("newsletterInput").value = "";

});

/* ===========================
   ACTIVE NAVIGATION
=========================== */

const links = document.querySelectorAll(".nav a");

links.forEach(link => {

    link.addEventListener("click", () => {

        links.forEach(l => l.classList.remove("active"));

        link.classList.add("active");

    });

});

/* ===========================
   SCROLL ANIMATION
=========================== */

const observer = new IntersectionObserver((entries) => {

    entries.forEach(entry => {

        if (entry.isIntersecting) {

            entry.target.style.opacity = "1";

            entry.target.style.transform = "translateY(0)";

        }

    });

}, {
    threshold: .2
});

document.querySelectorAll(".step-card,.dog-card").forEach(item => {

    item.style.opacity = "0";

    item.style.transform = "translateY(50px)";

    item.style.transition = ".6s";

    observer.observe(item);

});



/* ===========================
   NORMAL ANCHOR LINKS
=========================== */

// Smooth scrolling removed.
// Navigation links now use the browser's default scrolling.