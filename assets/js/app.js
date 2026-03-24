// Yağ Takip Lite — JS

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        }, 4500);
    });
});

function toggleDrawer() {
    var d = document.getElementById('navDrawer');
    var h = document.getElementById('hamburger');
    if (!d) return;
    var open = d.classList.toggle('open');
    if (h) h.classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
}
function closeDrawer() {
    var d = document.getElementById('navDrawer');
    var h = document.getElementById('hamburger');
    if (!d) return;
    d.classList.remove('open');
    if (h) h.classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) { if(e.key==='Escape') closeDrawer(); });
