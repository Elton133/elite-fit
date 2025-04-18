document.querySelector(".dropdown-menu").addEventListener("click", function () {
  this.querySelector(".dropdown-content").classList.toggle("show");
});

// Close dropdown when clicking outside
window.addEventListener("click", function (event) {
  if (
    !event.target.matches(".dropdown-menu") &&
    !event.target.matches(".fa-chevron-down")
  ) {
    const dropdowns = document.querySelectorAll(".dropdown-content");
    dropdowns.forEach((dropdown) => {
      if (dropdown.classList.contains("show")) {
        dropdown.classList.remove("show");
      }
    });
  }
});
