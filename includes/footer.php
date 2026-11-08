</main>

<footer class="text-center text-muted py-3 mt-4 border-top">
  <small>&copy; <?= date('Y') ?> TaskApp | ICT600 Assignment 1</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
setTimeout(function () {
  document.querySelectorAll('.alert-success, .alert-info').forEach(function (a) {
    a.style.transition = 'opacity 0.5s';
    a.style.opacity = '0';
    setTimeout(function () { a.remove(); }, 500);
  });
}, 4000);
</script>

</body>
</html>