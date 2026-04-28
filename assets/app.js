// PropTXChange — App JS

// Mobile nav toggle
const toggle   = document.getElementById('navToggle');
const mobileNav = document.getElementById('navMobile');
if (toggle && mobileNav) {
    toggle.addEventListener('click', () => {
        const open = mobileNav.classList.toggle('open');
        const spans = toggle.querySelectorAll('span');
        if (open) {
            spans[0].style.transform = 'rotate(45deg) translate(5px,5px)';
            spans[1].style.opacity   = '0';
            spans[2].style.transform = 'rotate(-45deg) translate(5px,-5px)';
        } else {
            spans.forEach(s => { s.style.transform = ''; s.style.opacity = ''; });
        }
    });
    document.addEventListener('click', e => {
        if (!toggle.contains(e.target) && !mobileNav.contains(e.target)) {
            mobileNav.classList.remove('open');
            toggle.querySelectorAll('span').forEach(s => { s.style.transform=''; s.style.opacity=''; });
        }
    });
}

// Tenant repair form toggle
function toggleRepairForm() {
    const f = document.getElementById('repairForm');
    if (f) f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
