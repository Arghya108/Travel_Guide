</main>

<footer class="footer">
    &copy; <?= date('Y') ?> Travel Guide System
</footer>

<script>
document.querySelectorAll("form[novalidate]").forEach(form => {
    form.addEventListener("submit", function (e) {
        let hasError = false;

        form.querySelectorAll(".auto-inline-error").forEach(item => item.remove());
        form.querySelectorAll(".input-error").forEach(item => item.classList.remove("input-error"));

        form.querySelectorAll("[required]").forEach(field => {
            const value = field.value.trim();

            if (value === "") {
                hasError = true;
                field.classList.add("input-error");

                const span = document.createElement("span");
                span.className = "error auto-inline-error";
                span.textContent = "This field is required.";

                field.insertAdjacentElement("afterend", span);
            }
        });

        form.querySelectorAll("input[type='email']").forEach(field => {
            const value = field.value.trim();

            if (value !== "" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                hasError = true;
                field.classList.add("input-error");

                const span = document.createElement("span");
                span.className = "error auto-inline-error";
                span.textContent = "Please enter a valid email address.";

                field.insertAdjacentElement("afterend", span);
            }
        });

        if (hasError) {
            e.preventDefault();
        }
    });
});
</script>

</body>
</html>