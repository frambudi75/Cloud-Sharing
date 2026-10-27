<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4f46e5">
    <title><?= htmlspecialchars($pageTitle ?? 'Cloud Storage & File Sharing') ?></title>

    <!-- Browser Taskbar & Tab Favicon (SVG & High-Res Data URI for Instant Zero-Latency Render) -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%234338ca'/%3E%3Cstop offset='50%25' stop-color='%234f46e5'/%3E%3Cstop offset='100%25' stop-color='%236366f1'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='64' height='64' rx='16' fill='url(%23g)'/%3E%3Cpath d='M22 45h20a9 9 0 0 0 1.8-17.8 13 13 0 0 0-25.1-1.2 8.5 8.5 0 0 0 3.3 17z' fill='%23ffffff'/%3E%3Ccircle cx='28' cy='34' r='3' fill='%234f46e5'/%3E%3Ccircle cx='37' cy='29' r='2.5' fill='%234f46e5'/%3E%3Ccircle cx='37' cy='39' r='2.5' fill='%234f46e5'/%3E%3Cpath d='M28 34l9-5M28 34l9 5' stroke='%234f46e5' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E">
    <link rel="alternate icon" href="favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="assets/img/favicon.svg">

    <!-- Google Fonts: Plus Jakarta Sans for crisp modern typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Dark Mode Immediate Pre-render Script -->
    <script>
        try {
            if (localStorage.getItem('drive_theme') === 'dark' || (!('drive_theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        } catch (e) {}
    </script>

    <!-- Tailwind CSS CDN untuk standalone & XAMPP native -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Modern Slate Dark Mode Styles */
        html.dark, html.dark body {
            background-color: #0b0f19 !important;
            color: #f1f5f9 !important;
        }
        html.dark .bg-white {
            background-color: #111827 !important;
            color: #f1f5f9 !important;
        }
        html.dark .bg-slate-50, html.dark .bg-slate-50\/70 {
            background-color: #0b0f19 !important;
        }
        html.dark .bg-slate-100 {
            background-color: #1f2937 !important;
            color: #e2e8f0 !important;
        }
        html.dark .border-slate-200, html.dark .border-slate-200\/80, html.dark .border-slate-100 {
            border-color: #1f2937 !important;
        }
        html.dark .text-slate-900, html.dark .text-slate-800, html.dark .text-slate-700 {
            color: #f3f4f6 !important;
        }
        html.dark .text-slate-600, html.dark .text-slate-500, html.dark .text-slate-400 {
            color: #94a3b8 !important;
        }
        html.dark header {
            background-color: rgba(17, 24, 39, 0.95) !important;
            border-color: #1f2937 !important;
        }
        html.dark input, html.dark select, html.dark textarea {
            background-color: #111827 !important;
            color: #f3f4f6 !important;
            border-color: #374151 !important;
        }
        html.dark .file-row:hover {
            background-color: #1e293b !important;
        }
        html.dark .file-card {
            background-color: #111827 !important;
            border-color: #1f2937 !important;
        }
        html.dark .file-card:hover {
            border-color: #6366f1 !important;
        }
        html.dark .sidebar-nav-active {
            background-color: #1e1b4b !important;
            color: #818cf8 !important;
        }
        /* Modern Typography & Focus */
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        *:focus-visible {
            outline: 2px solid #6366f1;
            outline-offset: 2px;
        }
        /* Custom scrollbar for clean aesthetic */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        /* Card & Dropzone Animations */
        @keyframes subtlePulse {
            0%, 100% { border-color: #a5b4fc; box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.15); }
            50% { border-color: #6366f1; box-shadow: 0 0 0 6px rgba(99, 102, 241, 0.1); }
        }
        .dragover-active {
            border-color: #4f46e5 !important;
            background-color: #eef2ff !important;
            animation: subtlePulse 1.5s ease-in-out infinite;
        }
    </style>
</head>
<body class="h-full text-slate-800 antialiased flex flex-col font-sans selection:bg-brand-500 selection:text-white">
