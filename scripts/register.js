document
  .getElementById("togglePassword")
  .addEventListener("click", function () {
    const passwordInput = document.getElementById("password");
    const type =
      passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);
    this.classList.toggle("fa-eye");
    this.classList.toggle("fa-eye-slash");
  });

// Track current section
let currentSection = 1;

function nextSection() {
  const role = document.getElementById("role").value;

  if (currentSection === 1) {
    // Skip to 2b if trainer, else check for section 2
    if (role === "trainer") {
      currentSection = 2.5; // Use decimal to indicate intermediate step
    } else if (role === "admin" || role === "equipment_manager") {
      currentSection = 3; // Skip both 2 and 2b
    } else {
      currentSection = 2;
    }
  } else if (currentSection === 2) {
    if (role === "trainer") {
      currentSection = 2.5;
    } else {
      currentSection = 3;
    }
  } else if (currentSection === 2.5) {
    currentSection = 3;
  }

  showSection(currentSection);
}

function showSection(sectionNum) {
  // Hide all sections first
  ["section1", "section2", "section2b", "section3"].forEach((id) => {
    document.getElementById(id).classList.add("hidden");
  });

  // Hide all buttons
  document.getElementById("prevBtn").classList.add("hidden");
  document.getElementById("nextBtn").classList.add("hidden");
  document.getElementById("submitBtn").classList.add("hidden");
  document.getElementById("link-to-login").classList.add("hidden");

  const role = document.getElementById("role").value;
  const skipSection2 = role !== "user";

  if (sectionNum === 1) {
    document.getElementById("section1").classList.remove("hidden");
    document.getElementById("nextBtn").classList.remove("hidden");
    document.getElementById("link-to-login").classList.remove("hidden");
  } else if (sectionNum === 2) {
    document.getElementById("section2").classList.remove("hidden");
    document.getElementById("prevBtn").classList.remove("hidden");
    document.getElementById("nextBtn").classList.remove("hidden");
  } else if (sectionNum === 2.5) {
    document.getElementById("section2b").classList.remove("hidden");
    document.getElementById("prevBtn").classList.remove("hidden");
    document.getElementById("nextBtn").classList.remove("hidden");
  } else if (sectionNum === 3) {
    document.getElementById("section3").classList.remove("hidden");
    document.getElementById("prevBtn").classList.remove("hidden");
    document.getElementById("submitBtn").classList.remove("hidden");
  }
}

// File input label update
document
  .getElementById("profile_picture")
  .addEventListener("change", function () {
    const fileName = this.files[0]?.name || "Choose a file";
    document.querySelector(".file-input-label").textContent = fileName;
  });

// Add this to your JavaScript section
document.getElementById("role").addEventListener("change", function () {
  // Reset to section 1 when role changes
  currentSection = 1;
  showSection(1);
});
