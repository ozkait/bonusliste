<header class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar">
            <div class="container-xl">
                <ul class="navbar-nav">
                    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''); ?>">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>index.php">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/></svg>
                            </span>
                            <span class="nav-link-title">
                                Dashboard
                            </span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_bonuses.php' ? 'active' : ''); ?>">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>manage_bonuses.php">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8h18a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-18a3 3 0 0 1 -3 -3v-2a3 3 0 0 1 3 -3"/><path d="M12 8v13m-2 -8h4m-7 0v-5a3 3 0 0 1 3 -3h10a3 3 0 0 1 3 3v5"/></svg>
                            </span>
                            <span class="nav-link-title">
                                Bonusları Yönet
                            </span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo ($current_page == 'manage_carousel.php' ? 'active' : ''); ?>">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>manage_carousel.php">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 12l-5 5l-5 -5l5 -5z"/><path d="M17 12l-5 5l-5 -5l5 -5z" transform="rotate(90 12 12)"/></svg>
                            </span>
                            <span class="nav-link-title">
                                Carousel Yönet
                            </span>
                        </a>
                    </li>
<li class="nav-item <?php echo ($current_page == 'manage_popups.php' ? 'active' : ''); ?>">
    <a class="nav-link" href="<?php echo ADMIN_URL; ?>manage_popups.php">
        <span class="nav-link-icon d-md-none d-lg-inline-block"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 20l-3 -3h-5a1 1 0 0 1 -1 -1v-12a1 1 0 0 1 1 -1h14a1 1 0 0 1 1 1v9a1 1 0 0 1 -1 1h-7l-3 3" /><path d="M19 15h2m-1 -1v2m-1 -5a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg>
        </span>
        <span class="nav-link-title">
            Popup Yönetimi
        </span>
    </a>
</li>
                    <li class="nav-item <?php echo ($current_page == 'manage_settings.php' ? 'active' : ''); ?>">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>manage_settings.php">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37a1.724 1.724 0 0 0 2.572 -1.065z"/><path d="M9 12a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/></svg>
                            </span>
                            <span class="nav-link-title">
                                Ayarları Yönet
                            </span>
                        </a>
                    </li>
                     <li class="nav-item <?php echo ($current_page == 'users.php' ? 'active' : ''); ?>">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>users.php">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 1 0 1 8 0a4 4 1 0 1 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.167a.784 .784 0 0 0 1.07 -.139l.75 -1.094a.5 .5 0 0 1 .74 -.093l.974 .975a.5 .5 0 0 1 .093 .74l-1.094 .75a.784 .784 0 0 0 -.139 1.07l.385 .925a.5 .5 0 0 1 -.238 .622l-1.094 .557a.784 .784 0 0 0 -.972 .594l-.385 1.026a.5 .5 0 0 1 -.622 .238l-.557 -1.094a.784 .784 0 0 0 -1.07 -.139l-.925 .385a.5 .5 0 0 1 -.622 -.238l-.557 -1.094a.784 .784 0 0 0 -.139 -1.07l1.094 -.75a.784 .784 0 0 0 .139 -1.07l-.385 -.925a.5 .5 0 0 1 .238 -.622l1.094 -.557a.784 .784 0 0 0 .972 -.594l.385 -1.026a.5 .5 0 0 1 .622 -.238l.557 1.094z"/><path d="M19 12a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/></svg>
                            </span>
                            <span class="nav-link-title">
                                Üyeler
                            </span>
                        </a>
                    </li>
                     <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_cloaker.php' ? 'active' : ''); ?>">
                        <a class="nav-link" href="<?php echo ADMIN_URL; ?>manage_cloaker.php">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                               <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-mask"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14.535 9.465l-6.07 -6.07a8 8 0 0 0 -5.657 13.562l6.07 6.07a8 8 0 0 0 13.562 -5.657z" /><path d="M9 10a1 1 0 1 0 0 -2a1 1 0 0 0 0 2" /><path d="M15 10a1 1 0 1 0 0 -2a1 1 0 0 0 0 2" /><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg>
                            </span>
                            <span class="nav-link-title">
                                Cloaker Ayarları
                            </span>
                        </a>
                    </li>
                    </ul>
            </div>
        </div>
    </div>
</header>