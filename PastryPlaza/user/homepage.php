<?php 
session_start();
include './header.php'; ?>
<section id="hero">
    <div class="background-slides">
        <div class="background-slide"></div>
        <div class="background-slide"></div>
        <div class="background-slide"></div>
    </div>
    <div class="hero-content">
        <h2>FRESH & DELICIOUS BAKED GOODS JUST FOR YOU</h2>
        <p>Order your favorite pastries, cakes, and pies online!</p>
        <a href="./menu.php" class="btn">SHOP NOW</a>
    </div>
</section>
<?php include '../footer.php'; ?>
<script>
let slideIndex = 0;
const slides = document.querySelector('.background-slides');
const totalSlides = document.querySelectorAll('.background-slide').length;
function showSlides() {
    slideIndex++;
    if (slideIndex >= totalSlides) {
        slideIndex = 0;
    }
    slides.style.transform = 'translateX(' + (-slideIndex * 100) + '%)';
    setTimeout(showSlides, 3000);
}
showSlides();
</script>
</body>
</html>
