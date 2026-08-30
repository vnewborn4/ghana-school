const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('.site-nav');
if (toggle && nav) {
  toggle.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!open));
    nav.classList.toggle('open', !open);
  });
}

document.querySelectorAll('[data-amount]').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelectorAll('[data-amount]').forEach((item) => item.classList.remove('selected'));
    button.classList.add('selected');
    const amount = document.querySelector('#amount');
    if (amount) amount.value = button.dataset.amount;
  });
});

document.querySelectorAll('input[name="frequency"]').forEach((radio) => {
  radio.addEventListener('change', () => {
    document.querySelectorAll('[data-period]').forEach((label) => {
      label.textContent = radio.value === 'monthly' ? '/month' : 'one time';
    });
  });
});
