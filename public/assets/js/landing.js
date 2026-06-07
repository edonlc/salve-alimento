// Efeito de sombra na navbar ao rolar
window.addEventListener('scroll', () => {
  document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
});

// Animação fade-up via IntersectionObserver
const observador = new IntersectionObserver((entradas) => {
  entradas.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12 });
document.querySelectorAll('.fade-up').forEach(el => observador.observe(el));

// Filtros de categoria nas doações
document.querySelectorAll('.filter-chip').forEach(chip => {
  chip.addEventListener('click', () => {
    chip.closest('.filter-bar').querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
  });
});

// Abas de perfil nos modais
function definirAbaAtiva(btn) {
  btn.closest('.role-tabs').querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
}
document.querySelectorAll('.role-tab').forEach(tab => {
  tab.addEventListener('click', () => definirAbaAtiva(tab));
});

// Controle dos modais
function abrirModal(id) {
  const idReal = (id === 'register-receptor') ? 'modal-register' : `modal-${id}`;
  document.getElementById(idReal).classList.add('open');
  document.body.style.overflow = 'hidden';
}
function fecharModal(id) {
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow = '';
}
function trocarModal(de, para) {
  fecharModal(de);
  setTimeout(() => abrirModal(para.replace('modal-', '')), 200);
}
function fecharAoClicarFora(e, id) {
  if (e.target === document.getElementById(id)) fecharModal(id);
}

// Fechar modal com Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => fecharModal(m.id));
  }
});
