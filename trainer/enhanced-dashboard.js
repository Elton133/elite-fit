// Enhanced Dashboard JavaScript

document.addEventListener("DOMContentLoaded", () => {
  initializeSearch();
  initializeFilters();
  initializeModals();
  initializeQuickActions();
  updateRealTimeData();
});

// Search Functionality
function initializeSearch() {
  const globalSearch = document.getElementById("globalSearch");
  const clientSearch = document.getElementById("clientSearch");

  if (globalSearch) {
    globalSearch.addEventListener("input", (e) => {
      const searchTerm = e.target.value.toLowerCase();
      performGlobalSearch(searchTerm);
    });
  }

  if (clientSearch) {
    clientSearch.addEventListener("input", (e) => {
      const searchTerm = e.target.value.toLowerCase();
      filterClients(searchTerm);
    });
  }
}

function performGlobalSearch(searchTerm) {
  // Search across all dashboard elements
  const searchableElements = document.querySelectorAll("[data-searchable]");
  searchableElements.forEach((element) => {
    const text = element.textContent.toLowerCase();
    const parent = element.closest(
      ".client-item, .request-item, .session-item"
    );
    if (parent) {
      parent.style.display = text.includes(searchTerm) ? "flex" : "none";
    }
  });
}

function filterClients(searchTerm) {
  const clientItems = document.querySelectorAll(".client-item");
  clientItems.forEach((item) => {
    const clientName = item
      .querySelector(".client-name")
      .textContent.toLowerCase();
    const planName = item.querySelector(".plan-name").textContent.toLowerCase();
    const isVisible =
      clientName.includes(searchTerm) || planName.includes(searchTerm);
    item.style.display = isVisible ? "flex" : "none";
  });
}

// Filter Functionality
function initializeFilters() {
  const statsTimeFilter = document.getElementById("statsTimeFilter");
  const clientFilter = document.getElementById("clientFilter");

  if (statsTimeFilter) {
    statsTimeFilter.addEventListener("change", (e) => {
      updateStatsDisplay(e.target.value);
    });
  }

  if (clientFilter) {
    clientFilter.addEventListener("change", (e) => {
      filterClientsByCategory(e.target.value);
    });
  }
}

function updateStatsDisplay(timeframe) {
  // Update stats based on timeframe
  showToast(`Stats updated for ${timeframe}`, "info");

  // Simulate API call to update stats
  setTimeout(() => {
    const statValues = document.querySelectorAll(".stat-value");
    statValues.forEach((stat) => {
      const currentValue = Number.parseInt(stat.textContent);
      const newValue = Math.floor(currentValue * (0.8 + Math.random() * 0.4));
      animateNumber(stat, currentValue, newValue);
    });
  }, 500);
}

function filterClientsByCategory(category) {
  const clientItems = document.querySelectorAll(".client-item");

  clientItems.forEach((item) => {
    let shouldShow = true;

    switch (category) {
      case "recent":
        // Show clients with recent activity
        const lastSession = item.querySelector(".last-session");
        const lastSessionText = lastSession ? lastSession.textContent : "";
        shouldShow =
          lastSessionText.includes("Today") ||
          lastSessionText.includes("Yesterday");
        break;
      case "needs_attention":
        // Show clients with low adherence
        const adherenceBar = item.querySelector(".metric-fill");
        const adherence = adherenceBar
          ? Number.parseInt(adherenceBar.style.width)
          : 100;
        shouldShow = adherence < 70;
        break;
      case "high_progress":
        // Show clients with high progress
        const progressText = item.querySelector(".progress-text");
        const progress = progressText
          ? Number.parseInt(progressText.textContent)
          : 0;
        shouldShow = progress > 80;
        break;
      default:
        shouldShow = true;
    }

    item.style.display = shouldShow ? "flex" : "none";
  });
}

// Modal Management
function initializeModals() {
  const modals = document.querySelectorAll(".modal");
  const closeButtons = document.querySelectorAll(".close, .btn-cancel");

  closeButtons.forEach((button) => {
    button.addEventListener("click", closeModal);
  });

  window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal")) {
      closeModal();
    }
  });

  // Initialize message form
  const messageForm = document.getElementById("messageForm");
  if (messageForm) {
    messageForm.addEventListener("submit", handleMessageSubmit);
  }
}

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "block";
    document.body.style.overflow = "hidden";
  }
}

function closeModal() {
  const modals = document.querySelectorAll(".modal");
  modals.forEach((modal) => {
    modal.style.display = "none";
  });
  document.body.style.overflow = "auto";
}

function handleMessageSubmit(e) {
  e.preventDefault();

  const clientId = document.getElementById("clientSelect").value;
  const messageText = document.getElementById("messageText").value;

  if (!clientId || !messageText.trim()) {
    showToast("Please select a client and enter a message", "error");
    return;
  }

  // Simulate sending message
  showToast("Message sent successfully!", "success");
  closeModal();

  // Reset form
  document.getElementById("messageForm").reset();
}

// Quick Actions
function initializeQuickActions() {
  // Add click handlers for action buttons
  const actionButtons = document.querySelectorAll(".action-btn-small");
  actionButtons.forEach((button) => {
    if (button.classList.contains("message")) {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        const clientItem = this.closest(".client-item");
        const clientId = clientItem.dataset.clientId;
        openMessageModalForClient(clientId);
      });
    }

    if (button.classList.contains("progress")) {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        const clientItem = this.closest(".client-item");
        const clientId = clientItem.dataset.clientId;
        viewClientProgress(clientId);
      });
    }
  });
}

function openCreatePlanModal() {
  showToast("Opening create plan modal...", "info");
  // Redirect to create plan page or open modal
  window.location.href = "create-plan.php";
}

function openScheduleModal() {
  showToast("Opening schedule modal...", "info");
  // Open scheduling modal
}

function openMessageModal() {
  openModal("messageModal");
}

function openProgressModal() {
  showToast("Opening progress modal...", "info");
  // Open progress tracking modal
}

function openNutritionModal() {
  showToast("Opening nutrition modal...", "info");
  // Open nutrition planning modal
}

function generateReport() {
  showToast("Generating report...", "info");
  // Generate and download report
  setTimeout(() => {
    showToast("Report generated successfully!", "success");
  }, 2000);
}

function openMessageModalForClient(clientId) {
  openModal("messageModal");
  const clientSelect = document.getElementById("clientSelect");
  if (clientSelect) {
    clientSelect.value = clientId;
  }
}

function viewClientProgress(clientId) {
  showToast(`Viewing progress for client ${clientId}`, "info");
  // Redirect to client progress page
  window.location.href = `client-progress.php?client_id=${clientId}`;
}

function viewRequestDetails(requestId) {
  showToast(`Viewing request details for ${requestId}`, "info");
  // Open request details modal or redirect
}

// Real-time Updates
function updateRealTimeData() {
  // Update session countdown
  updateSessionCountdown();

  // Update activity feed
  setInterval(updateActivityFeed, 30000); // Every 30 seconds

  // Update notification count
  setInterval(updateNotificationCount, 60000); // Every minute
}

function updateSessionCountdown() {
  const sessionTimes = document.querySelectorAll(".session-time .time");
  sessionTimes.forEach((timeElement) => {
    const sessionTime = timeElement.textContent;
    // Calculate time until session and update display
  });
}

function updateActivityFeed() {
  // Simulate new activity
  const activities = [
    "New client message received",
    "Workout plan completed",
    "Progress photo uploaded",
    "Session reminder sent",
  ];

  // Randomly add new activity (simulation)
  if (Math.random() > 0.7) {
    const randomActivity =
      activities[Math.floor(Math.random() * activities.length)];
    addActivityItem(randomActivity);
  }
}

function addActivityItem(activity) {
  const activityFeed = document.querySelector(".activity-feed");
  if (activityFeed) {
    const newItem = document.createElement("div");
    newItem.className = "activity-item";
    newItem.innerHTML = `
            <div class="activity-icon new">
                <i class="fas fa-bell"></i>
            </div>
            <div class="activity-content">
                <p><strong>System:</strong> ${activity}</p>
                <span class="activity-time">Just now</span>
            </div>
        `;
    activityFeed.insertBefore(newItem, activityFeed.firstChild);

    // Remove old items if too many
    const items = activityFeed.querySelectorAll(".activity-item");
    if (items.length > 10) {
      items[items.length - 1].remove();
    }
  }
}

function updateNotificationCount() {
  // Simulate notification updates
  const notificationBadge = document.querySelector(".notification-badge");
  if (notificationBadge && Math.random() > 0.8) {
    const currentCount = Number.parseInt(notificationBadge.textContent);
    notificationBadge.textContent = currentCount + 1;

    // Add pulse animation
    notificationBadge.style.animation = "pulse 0.5s ease-in-out";
    setTimeout(() => {
      notificationBadge.style.animation = "";
    }, 500);
  }
}

// Utility Functions
function animateNumber(element, start, end) {
  const duration = 1000;
  const startTime = performance.now();

  function update(currentTime) {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);

    const current = Math.floor(start + (end - start) * progress);
    element.textContent = current;

    if (progress < 1) {
      requestAnimationFrame(update);
    }
  }

  requestAnimationFrame(update);
}

function showToast(message, type = "info") {
  const colors = {
    success: "#4CAF50",
    error: "#f44336",
    warning: "#ff9800",
    info: "#2196F3",
  };

  const Toastify = window.Toastify; // Declare Toastify variable
  if (Toastify) {
    Toastify({
      text: message,
      duration: 3000,
      gravity: "top",
      position: "right",
      backgroundColor: colors[type] || colors.info,
      close: true,
    }).showToast();
  }
}

// Export functions for global use
window.openCreatePlanModal = openCreatePlanModal;
window.openScheduleModal = openScheduleModal;
window.openMessageModal = openMessageModal;
window.openProgressModal = openProgressModal;
window.openNutritionModal = openNutritionModal;
window.generateReport = generateReport;
window.viewRequestDetails = viewRequestDetails;
