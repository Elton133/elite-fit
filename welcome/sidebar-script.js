document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector(".sidebar");
  const container = document.querySelector(".container");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const mobileToggle = document.getElementById("mobileToggle");

  // Create overlay element
  const overlay = document.createElement("div");
  overlay.className = "sidebar-overlay";
  document.body.appendChild(overlay);

  // Function to check if we're on mobile
  function isMobile() {
    return window.innerWidth <= 768;
  }

  // Toggle sidebar function
  function toggleSidebar() {
    if (isMobile()) {
      sidebar.classList.toggle("mobile-open");

      // Toggle overlay
      if (sidebar.classList.contains("mobile-open")) {
        overlay.classList.add("active");
      } else {
        overlay.classList.remove("active");
      }
    } else {
      sidebar.classList.toggle("collapsed");
      container.classList.toggle("expanded");
    }
  }

  // Close sidebar function for mobile
  function closeSidebarMobile() {
    if (isMobile() && sidebar.classList.contains("mobile-open")) {
      sidebar.classList.remove("mobile-open");
      overlay.classList.remove("active");
    }
  }

  // Event listeners
  sidebarToggle.addEventListener("click", toggleSidebar);

  if (mobileToggle) {
    mobileToggle.addEventListener("click", toggleSidebar);
  }

  // Add event listener to overlay to close sidebar
  overlay.addEventListener("click", closeSidebarMobile);

  // Handle window resize
  window.addEventListener("resize", () => {
    if (!isMobile()) {
      sidebar.classList.remove("mobile-open");
      overlay.classList.remove("active");
    } else {
      sidebar.classList.remove("collapsed");
      container.classList.remove("expanded");
    }
  });
});
