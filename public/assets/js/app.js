document.addEventListener('click', (event) => {
  if (event.target.closest('[data-print]')) {
    window.print();
  }
});

document.addEventListener('submit', (event) => {
  const message = event.target.dataset.confirm;
  if (message && !window.confirm(message)) {
    event.preventDefault();
  }
});

document.addEventListener('click', (event) => {
  if (event.target.tagName === 'SELECT') {
    return;
  }

  const select = event.target.closest('.select-wrap')?.querySelector('select');
  if (select?.showPicker) {
    select.showPicker();
  }
});

document.addEventListener('change', (event) => {
  if (event.target.closest('.sort-form')) {
    event.target.form.submit();
  }
});
