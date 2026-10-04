// Tisk: tlačítko s atributem data-print jen spustí dialog tisku prohlížeče,
document.addEventListener('click', (event) => {
  if (event.target.closest('[data-print]')) {
    window.print();
  }
});

// Potvrzovací dialog před smazáním knihy.
document.addEventListener('submit', (event) => {
  const message = event.target.dataset.confirm;
  if (message && !window.confirm(message)) {
    event.preventDefault();
  }
});

// Kliknutí kamkoliv v rámci .select-wrap (ne jen přesně na nativní <select>,
// Např. na odsazení kolem něj nebo na šipku ▾) otevře nabídku přes showPicker().
document.addEventListener('click', (event) => {
  if (event.target.tagName === 'SELECT') {
    return;
  }

  const select = event.target.closest('.select-wrap')?.querySelector('select');
  if (select?.showPicker) {
    select.showPicker();
  }
});

// Select má pro každý sloupec dvě <option> (vzestupně/sestupně); 
// Vybraný směr se zkopíruje z jejího data-dir do skrytého pole #sort-dir-input pro oddělení směru a hodnoty.
// Odešle formulář.
document.addEventListener('change', (event) => {
  const form = event.target.closest('.sort-form');
  if (!form) {
    return;
  }

  if (event.target.id === 'sort-field') {
    form.querySelector('#sort-dir-input').value = event.target.selectedOptions[0].dataset.dir;
  }

  form.submit();
});

// Změna počtu zobrazených záznamů (#per-page-field) se odešle hned po změně,
document.addEventListener('change', (event) => {
  if (event.target.closest('.per-page-form')) {
    event.target.form.submit();
  }
});

// Křížek pro vyhledávacího pole hledání (data-clear-search).
document.addEventListener('click', (event) => {
  const clear = event.target.closest('[data-clear-search]');
  if (!clear) {
    return;
  }

  const field = clear.closest('.search-field-wrap').querySelector('.search-field');
  field.value = '';
  clear.closest('form').submit();
});

// Při každém psaní do textarea, zavolá aktualizaci počítadla.
document.addEventListener('input', (event) => {
  if (event.target.matches('textarea[data-char-counter]')) {
    updateCharCounter(event.target);
  }
});

// Přepíše text přidruženého elementu (podle data-char-counter) na "aktuální délka / maxlength".
function updateCharCounter(textarea) {
  const counter = document.getElementById(textarea.dataset.charCounter);
  if (counter) {
    counter.textContent = `${textarea.value.length} / ${textarea.maxLength}`;
  }
}

// Při načtení stránky nastaví počáteční stav počítadla pro každé textarea, které ho má.
document.querySelectorAll('textarea[data-char-counter]').forEach(updateCharCounter);
