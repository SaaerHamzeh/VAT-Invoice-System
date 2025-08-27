var showFormBtns = document.querySelectorAll(".showFormBtn");
var forms = document.querySelectorAll(".popupForm");
var overlay = document.getElementById("overlay");
var closeBtns = document.querySelectorAll(".closeBtn");


showFormBtns.forEach(function(btn) {
    btn.addEventListener("click", function() {
        var formId = this.getAttribute("data-form");
        forms.forEach(function(form) {
            form.style.display = "none"; 
        });
        document.getElementById(formId).style.display = "flex";
        overlay.style.display = "flex"; 
    });
});


closeBtns.forEach(function(btn) {
    btn.addEventListener("click", function() {
        this.parentElement.style.display = "none";
        overlay.style.display = "none";
    });
});


overlay.addEventListener("click", function() {
    forms.forEach(function(form) {
        form.style.display = "none"; 
    });
    overlay.style.display = "none"; 
});
