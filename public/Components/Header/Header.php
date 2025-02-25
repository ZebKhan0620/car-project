<?php

namespace Components\Header;

require_once __DIR__ . '/LanguageSwitcher.php';

use Classes\Auth\Session;
use Classes\Language\TranslationManager;
use Components\Header\LanguageSwitcher;

class Header {
    private $session;
    private $translationManager;
    private $languageSwitcher;

    public function __construct() {
        $this->session = Session::getInstance();
        $this->translationManager = TranslationManager::getInstance();
        $this->languageSwitcher = new LanguageSwitcher();
    }

    public function render() {
        $isLoggedIn = $this->session->get('user_id');
        $currentLocale = $this->translationManager->getLocale();

        return '
        <header class="bg-base-100 shadow-xl sticky top-0 z-50">
            <!-- Top Bar -->
            <div class="hidden lg:block bg-primary/5 border-b border-base-200">
                <div class="container mx-auto">
                    <div class="flex justify-between items-center py-1 px-4">
                        <!-- Contact Info -->
                        <div class="flex items-center divide-x divide-base-300">
                            <a href="tel:+1-555-123-4567" class="hover:text-primary transition-colors px-4 first:pl-0 flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>+1 (555) 123-4567</span>
                            </a>
                            <a href="mailto:contact@carverse.com" class="hover:text-primary transition-colors px-4 flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>contact@carverse.com</span>
                            </a>
                            <div class="px-4 flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Mon-Fri: 9:00-17:00</span>
                            </div>
                        </div>
                        <!-- Quick Actions -->
                        <div class="flex items-center gap-6">
                            ' . $this->languageSwitcher->render() . '
                            <a href="/car-project/public/Components/Contact/index.php" 
                               class="btn btn-primary btn-sm normal-case">
                                ' . __('common.contact') . '
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Navigation -->
            <div class="bg-base-100">
                <div class="container mx-auto">
                    <div class="navbar px-4">
                        <!-- Logo & Mobile Menu -->
                        <div class="navbar-start gap-2">
                            <div class="dropdown lg:hidden">
                                <label tabindex="0" class="btn btn-circle btn-ghost btn-sm">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/>
                                    </svg>
                                </label>
                                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow-lg bg-base-100 rounded-box w-52 gap-1">
                                    ' . $this->renderMenuItems() . '
                                </ul>
                            </div>
                            <!-- Logo -->
                            <a href="/car-project/public/index.php" class="flex items-center gap-2 hover:opacity-90 transition-all duration-200">
                                <span class="text-2xl font-black bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent">
                                    CarVerse
                                </span>
                                <div class="w-px h-6 bg-base-300/50"></div>
                                <span class="text-2xl font-black bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent">DriveLink</span>
                            </a>
                        </div>

                        <!-- Desktop Menu -->
                        <div class="navbar-center hidden lg:flex">
                            <ul class="menu menu-horizontal gap-1">
                                ' . $this->renderMenuItems() . '
                            </ul>
                        </div>

                        <!-- User Actions -->
                        <div class="navbar-end gap-2">
                            ' . $this->renderUserActions($isLoggedIn) . '
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Navigation -->
            ' . $this->renderSecondaryNav() . '
        </header>';
    }

    private function renderMenuItems() {
        return '
            <li>
                <a href="/car-project/public/index.php" 
                   class="' . $this->isCurrentPage('index.php') . ' rounded-lg hover:bg-primary/10">
                    ' . __('common.home') . '
                </a>
            </li>
            <li>
                <a href="/car-project/public/Components/Cars/index.php" 
                   class="' . $this->isCurrentPage('Cars/index.php') . ' rounded-lg hover:bg-primary/10">
                    ' . __('cars.browse') . '
                </a>
            </li>
            <li>
                <a href="/car-project/public/Components/Cars/search.php" 
                   class="' . $this->isCurrentPage('Cars/search.php') . ' rounded-lg hover:bg-primary/10">
                    ' . __('cars.search') . '
                </a>
            </li>
            ' . ($this->session->get('user_id') ? '
            <li>
                <a href="/car-project/public/Components/Cars/add-listing.php" 
                   class="' . $this->isCurrentPage('Cars/add-listing.php') . ' rounded-lg hover:bg-primary/10">
                    ' . __('cars.add_listing') . '
                </a>
            </li>
            ' : '');
    }

    private function isCurrentPage($path) {
        return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';
    }

    private function renderUserActions($isLoggedIn) {
        if ($isLoggedIn) {
            // Get user profile image
            $userId = $this->session->get('user_id');
            $userModel = new \Models\User();
            $userData = $userModel->findById($userId);
            $profileImage = $userData['profile_image'] ?? 'default-avatar.png';
            
            return '
                <div class="flex items-center gap-3">
                    <!-- Notifications -->
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-ghost btn-circle btn-sm">
                            <div class="indicator">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <span class="badge badge-sm badge-primary badge-outline indicator-item">3</span>
                            </div>
                        </label>
                        <div tabindex="0" class="mt-3 z-[1] card card-compact dropdown-content w-80 bg-base-100 shadow-xl">
                            <div class="card-body">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="font-bold text-lg">Notifications</h3>
                                    <span class="text-sm text-base-content/70">3 unread</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex gap-3 items-start p-2 hover:bg-base-200 rounded-lg cursor-pointer">
                                        <div class="avatar placeholder">
                                            <div class="bg-primary text-primary-content rounded-full w-8">
                                                <span class="text-xs">N</span>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium">New message received</p>
                                            <p class="text-xs text-base-content/70">2 minutes ago</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-actions mt-3">
                                    <button class="btn btn-primary btn-sm btn-block">View all notifications</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="dropdown dropdown-end">
                        <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                            <div class="w-10 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                                <img src="/car-project/public/uploads/profiles/' . htmlspecialchars($profileImage) . '" 
                                     alt="Profile"
                                     onerror="this.src=\'/car-project/public/assets/images/default-avatar.png\'" />
                            </div>
                        </label>
                        <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow-xl bg-base-100 rounded-box w-52 gap-1">
                            <li>
                                <a href="/car-project/public/Components/Profile/index.php" class="hover:bg-primary/10">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    ' . __('common.profile') . '
                                </a>
                            </li>
                            <li>
                                <a href="/car-project/public/Components/Cars/favorites.php" class="hover:bg-primary/10">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    ' . __('cars.favorites') . '
                                </a>
                            </li>
                            <li>
                                <a href="/car-project/public/Components/Cars/my-listings.php" class="hover:bg-primary/10">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    ' . __('cars.my_listings') . '
                                </a>
                            </li>
                            <div class="divider my-1"></div>
                            <li>
                                <a href="/car-project/public/pages/login/logout.php" class="hover:bg-error/10 text-error">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    ' . __('common.logout') . '
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>';
        } else {
            return '
                <div class="flex items-center gap-2">
                    <a href="/car-project/public/pages/login/login.php" 
                       class="btn btn-ghost btn-sm normal-case hover:bg-primary/10">
                        ' . __('common.login') . '
                    </a>
                    <a href="/car-project/public/pages/register/register.php" 
                       class="btn btn-primary btn-sm normal-case">
                        ' . __('common.register') . '
                    </a>
                </div>';
        }
    }

    private function renderSecondaryNav() {
        if (strpos($_SERVER['REQUEST_URI'], '/Cars/') !== false) {
            return '
            <div class="bg-base-200 border-b">
                <div class="container mx-auto px-4">
                    <div class="flex items-center justify-between">
                        <!-- Car Navigation -->
                        <div class="flex items-center gap-6 overflow-x-auto py-4">
                           
                        </div>

                        <!-- Contact Info -->
                        <div class="hidden lg:flex items-center gap-6">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <a href="tel:+1-555-123-4567" class="text-sm hover:text-primary">+1 (555) 123-4567</a>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <a href="mailto:contact@carverse.com" class="text-sm hover:text-primary">contact@carverse.com</a>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm">Mon-Fri: 9:00-17:00</span>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>';
        }
        return '';
    }

    private function handleLanguageSwitch() {
        if (isset($_GET['lang'])) {
            $newLang = $_GET['lang'];
            if (in_array($newLang, ['en', 'ja'])) {
                $_SESSION['locale'] = $newLang;
                
                // Get current URL without the lang parameter
                $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $params = $_GET;
                unset($params['lang']); // Remove lang from parameters
                
                // Rebuild URL with remaining parameters
                if (!empty($params)) {
                    $currentUrl .= '?' . http_build_query($params);
                }
                
                header('Location: ' . $currentUrl);
                exit;
            }
        }
    }
}