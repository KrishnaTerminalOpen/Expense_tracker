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

    // Ensure Bootstrap Icons is loaded
    if (!document.querySelector('link[href*="bootstrap-icons"]')) {
        const iconLink = document.createElement("link");
        iconLink.rel = "stylesheet";
        iconLink.href = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css";
        document.head.appendChild(iconLink);
    }

    const currentFile = window.location.pathname.split("/").pop();

    navContainer.innerHTML = `
        <nav class="navbar navbar-expand-lg navbar-dark navbar-spendwise shadow-sm">
            <div class="container-fluid px-xl-4 px-3">
                <a class="navbar-brand" href="${pagePrefix}dashboard.html">
                    <span class="brand-icon"><i class="bi bi-gem"></i></span>
                    <span class="brand-text">SpendWise</span>
                </a>
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#spendwiseNavbar" aria-controls="spendwiseNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="spendwiseNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'dashboard.html' ? 'active' : ''}" href="${pagePrefix}dashboard.html">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'categories.html' ? 'active' : ''}" href="${pagePrefix}categories.html">
                                <i class="bi bi-tags"></i> Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'expenses.html' ? 'active' : ''}" href="${pagePrefix}expenses.html">
                                <i class="bi bi-receipt"></i> Expenses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'history.html' ? 'active' : ''}" href="${pagePrefix}history.html">
                                <i class="bi bi-clock-history"></i> History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'budgets.html' ? 'active' : ''}" href="${pagePrefix}budgets.html">
                                <i class="bi bi-bullseye"></i> Budgets
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'analytics.html' ? 'active' : ''}" href="${pagePrefix}analytics.html">
                                <i class="bi bi-graph-up-arrow"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ${currentFile === 'profile.html' ? 'active' : ''}" href="${pagePrefix}profile.html">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                        <!-- Notification Bell with Pulsing Unread Badge -->
                        <a href="${pagePrefix}notifications.html" class="nav-bell-link position-relative text-white text-decoration-none p-2 d-inline-flex align-items-center justify-content-center" title="Notifications">
                            <i class="bi bi-bell-fill fs-5"></i>
                            <span id="navUnreadBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none shadow-sm" style="font-size: 0.65rem;">
                                0
                            </span>
                        </a>
                        <div class="d-flex align-items-center gap-2 px-3 py-1 rounded-pill" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12);">
                            <i class="bi bi-person-circle text-white-50"></i>
                            <span class="text-white-50 small">Hi,</span>
                            <span class="text-white small fw-bold">${escapeHtml(userName)}</span>
                        </div>
                        <button id="logoutBtn" class="btn btn-outline-light btn-sm px-3 rounded-pill d-flex align-items-center gap-1" style="background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 255, 255, 0.2);">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </button>
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
