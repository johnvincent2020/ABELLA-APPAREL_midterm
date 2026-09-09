function showForm(formId) {
    document.querySelectorAll('.form-box').forEach(form => form.classList.remove("active"));
    document.getElementById(formId).classList.add("active");
}

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".error-message.show").forEach(function (msg) {
        setTimeout(function () {
            msg.classList.remove("show");
        }, 3000);
    });
});