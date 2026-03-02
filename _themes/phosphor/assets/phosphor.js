(function () {
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.querySelector('.nav-links');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function () {
        var isOpen = nav.classList.toggle('open');
        toggle.textContent = isOpen ? '[x]' : '[=]';
        toggle.setAttribute('aria-expanded', isOpen);
    });
})();
