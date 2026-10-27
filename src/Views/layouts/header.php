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

    <!-- Tailwind CSS CDN untuk standalone & XAMPP native -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
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
        /* Antislop Focus & Utility styles */
        *:focus-visible {
            outline: 2px solid #4f46e5;
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
    </style>
</head>
<body class="h-full text-slate-800 antialiased flex flex-col font-sans">
