        </div>
    </main>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-inner">
                <p class="footer-quote"><?= t('footer_quote') ?></p>
                <p class="footer-copy">&copy; <?= date('Y') ?> <?= e($blogTitle ?? "Miha's Blog of Philosophy") ?>. <?= t('footer_copy') ?></p>
            </div>
        </div>
    </footer>
    <script src="/js/app.js"></script>
</body>
</html>
