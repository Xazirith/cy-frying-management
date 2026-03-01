document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("sentra-auth-modal");
    const openBtns = document.querySelectorAll("[data-auth-open]");
    const closeBtns = document.querySelectorAll("[data-auth-close]");
    const form = document.getElementById("sentra-login-form");

    if (!modal) return;

    // OPEN
    openBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            modal.classList.add("is-open");
        });
    });

    // CLOSE
    closeBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            modal.classList.remove("is-open");
        });
    });

    // Click outside closes
    modal.addEventListener("click", e => {
        if (e.target === modal) {
            modal.classList.remove("is-open");
        }
    });

    // LOGIN SUBMIT
    if (form && typeof SentraAuth !== "undefined") {
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            const email = form.querySelector("[name='email']").value;
            const password = form.querySelector("[name='password']").value;

            const action = SentraAuth.login_action || "sentrasystems_login";
            fetch(SentraAuth.ajax_url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    action,
                    nonce: SentraAuth.nonce,
                    email,
                    password
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data?.message || "Login failed.");
                }
            })
            .catch(() => alert("Connection error."));
        });
    }

});
