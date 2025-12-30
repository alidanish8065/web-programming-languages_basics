const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const pageWrapper = document.getElementById('page-content-wrapper');

sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    pageWrapper.classList.toggle('full');
});
