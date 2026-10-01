    </main>
  <?php if ($user): ?></div><?php endif; ?>
  <footer class="footer"><span>© <?= date('Y') ?> <?= e(setting('school_name', APP_NAME)) ?></span><?php if ($user): ?><span><?= e(ucfirst($user['role'])) ?> workspace</span><?php endif; ?></footer>
</div>
</body>
</html>