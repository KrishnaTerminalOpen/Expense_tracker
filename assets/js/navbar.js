// assets/js/navbar.js
// Shared navbar, theme application, and authentication check for all SpendWise pages

// 1. Immediately apply cached theme preference to avoid visual flicker
(function () {
    const cachedTheme = localStorage.getItem("spendwise_theme");
    if (cachedTheme === "dark") {
        document.documentElement.setAttribute("data-bs-theme", "dark");
    } else {
        document.documentElement.setAttribute("data-bs-theme", "light");
    }
})();

document.addEventListener("DOMContentLoaded", function () {
    // Determine relative paths based on current directory
    const isInPagesFolder = window.location.pathname.includes("/pages/");
    const apiPrefix = isInPagesFolder ? "../api/" : "api/";
    const pagePrefix = isInPagesFolder ? "" : "pages/";
    const rootPrefix = isInPagesFolder ? "../" : "";

    // Identify current page name
    const currentPath = window.location.pathname;
    const isAuthPage = currentPath.endsWith("login.html") || currentPath.endsWith("register.html");

    // Check user session
    fetch(apiPrefix + "check_session.php")
        .then(response => response.json())
        .then(res => {
            if (res.status === "success" && res.data.logged_in) {
                // Apply and cache user's saved theme from database
                const serverTheme = res.data.theme || "light";
                localStorage.setItem("spendwise_theme", serverTheme);
                document.documentElement.setAttribute("data-bs-theme", serverTheme);

                // Cache currency symbol
                if (res.data.currency) {
                    localStorage.setItem("spendwise_currency", res.data.currency);
                }

                // If logged in and on login/register page, redirect to dashboard
                if (isAuthPage) {
                    window.location.href = pagePrefix + "dashboard.html";
                    return;
                }

                // Render navbar for authenticated user
                renderNavbar(res.data.user_name || "User", rootPrefix, pagePrefix, apiPrefix);
            } else {
                // If not logged in and on a protected page, redirect to login
                if (!isAuthPage) {
                    window.location.href = rootPrefix + "login.html";
                }
            }
        })
        .catch(err => {
            console.error("Session check error:", err);
            if (!isAuthPage) {
                window.location.href = rootPrefix + "login.html";
            }
        });
});

// Function to render the shared Bootstrap navbar
function renderNavbar(userName, rootPrefix, pagePrefix, apiPrefix) {
    const navContainer = document.getElementById("navbar-container");
    if (!navContainer) return;

    const currentFile = window.location.pathname.split("/").pop();

    navContainer.innerHTML = `
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-bold text-white" href="${pagePrefix}dashboard.html">
                    💰 SpendWise
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#spendwiseNavbar" aria-controls="spendwiseNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-style"><span class="navbar-toggler-icon"></span></span>
                </button>
                <div class="collapse navbar-collapse" id="spendwiseNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'dashboard.html' ? 'active text-white' : ''}" href="${pagePrefix}dashboard.html">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'categories.html' ? 'active text-white' : ''}" href="${pagePrefix}categories.html">Categories &amp; Payment</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'expenses.html' ? 'active text-white' : ''}" href="${pagePrefix}expenses.html">Expenses</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'history.html' ? 'active text-white' : ''}" href="${pagePrefix}history.html">History</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'budgets.html' ? 'active text-white' : ''}" href="${pagePrefix}budgets.html">Budgets</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'analytics.html' ? 'active text-white' : ''}" href="${pagePrefix}analytics.html">Analytics</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'profile.html' ? 'active text-white' : ''}" href="${pagePrefix}profile.html">Profile &amp; Settings</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center gap-3">
                        <!-- Notification Bell with Unread Badge -->
                        <a href="${pagePrefix}notifications.html" class="position-relative text-white text-decoration-none px-2" title="Notifications">
                            <span class="fs-5">🔔</span>
                            <span id="navUnreadBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size: 0.65rem;">
                                0
                            </span>
                        </a>
                        <span class="text-light small">Welcome, <strong>${escapeHtml(userName)}</strong></span>
                        <button id="logoutBtn" class="btn btn-outline-light btn-sm px-3">Logout</button>
                    </div>
                </div>
            </div>
        </nav>
    `;

    // Fetch and display unread notifications count
    fetch(apiPrefix + "get_unread_count.php")
        .then(res => res.json())
        .then(res => {
            if (res.status === "success" && res.data.unread_count > 0) {
                const badge = document.getElementById("navUnreadBadge");
                if (badge) {
                    badge.innerText = res.data.unread_count;
                    badge.classList.remove("d-none");
                }
            }
        })
        .catch(() => {});

    // Attach logout click handler
    document.getElementById("logoutBtn").addEventListener("click", function () {
        fetch(apiPrefix + "logout.php")
            .then(res => res.json())
            .then(data => {
                window.location.href = rootPrefix + "login.html";
            })
            .catch(() => {
                window.location.href = rootPrefix + "login.html";
            });
    });
}

// Utility function to prevent XSS in rendered name
function escapeHtml(text) {
    const div = document.createElement("div");
    div.innerText = text;
    return div.innerHTML;
}
