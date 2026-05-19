<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UPAS - Pangasinan State University</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    @include('partials.vite-production-assets')
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding-top: 80px; /* Add padding to account for fixed header */
        }
        
        /* Fixed Header Styles */
        .fixed-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            height: 80px;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .logo-image {
            height: 65px;
            width: auto;
            object-fit: contain;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }
        
        .university-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            line-height: 1.2;
            padding-top: 0;
            padding-bottom: 0;
        }
        
        .university-title {
            font-size: 22px;
            font-weight: 700;
            font-style: italic;
            color: #1A1A1A;
            margin: 0 0 4px 0;
            line-height: 1.2;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .university-subtitle {
            font-size: 14px;
            color: #5A5A5A;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 500;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.2;
        }
        
        .header-nav {
            display: flex;
            align-items: center;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
        }
        
        .nav-link {
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s ease;
            padding: 8px 0;
        }
        
        .nav-link:hover {
            color: #0033cc;
        }
        
        .login-button {
            background: #0033cc;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 51, 204, 0.2);
        }
        
        .login-button:hover {
            background: #002b99;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 51, 204, 0.3);
        }
        
        /* Mobile Menu Styles */
        .mobile-menu {
            position: fixed;
            top: 80px;
            right: -100%;
            width: 300px;
            height: calc(100vh - 80px);
            background: white;
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 999;
        }
        
        .mobile-menu.active {
            right: 0;
        }
        
        .mobile-menu-content {
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .mobile-nav-link {
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            font-size: 18px;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            transition: color 0.3s ease;
        }
        
        .mobile-nav-link:hover {
            color: #0033cc;
        }
        
        .mobile-login-button {
            background: #0033cc;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            text-align: center;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 51, 204, 0.2);
        }
        
        .mobile-login-button:hover {
            background: #002b99;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 51, 204, 0.3);
        }
        
        /* Hamburger Menu */
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 4px;
        }
        
        .hamburger span {
            width: 25px;
            height: 3px;
            background: #374151;
            margin: 3px 0;
            transition: 0.3s;
            border-radius: 2px;
        }
        
        .hamburger.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }
        
        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger.active span:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }
        
        /* Header Responsive Styles */
        @media (max-width: 768px) {
            .header-container {
                padding: 0 16px;
            }
            
            .nav-links {
                display: none;
            }
            
            .hamburger {
                display: flex;
            }
            
            .header-left {
                gap: 10px;
            }
            
            .logo-image {
                height: 48px;
                width: auto;
            }
            
            .university-title {
                font-size: 20px;
                font-weight: 700;
                font-style: italic;
                letter-spacing: 0.4px;
                margin: 0 0 1px 0;
            }
            
            .university-subtitle {
                font-size: 12px;
                letter-spacing: 0.3px;
                margin: 0;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding-top: 70px;
            }
            
            .fixed-header {
                height: 70px;
            }
            
            .mobile-menu {
                top: 70px;
                height: calc(100vh - 70px);
            }
            
            .header-left {
                gap: 8px;
            }
            
            .logo-image {
                height: 42px;
                width: auto;
            }
            
            .university-title {
                font-size: 18px;
                font-weight: 700;
                font-style: italic;
                letter-spacing: 0.3px;
                margin: 0 0 1px 0;
            }
            
            .university-subtitle {
                font-size: 11px;
                letter-spacing: 0.2px;
                margin: 0;
            }
        }
        
        /* Main Container */
        main {
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 1.5rem;
        }
        
        .floating-container {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1rem 1.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            max-height: calc(100vh - 80px - 2rem);
            overflow: hidden;
        }
        
        .psu-campus-bg {
            background: url('{{ asset("images/psu_building.jpg") }}') center/cover no-repeat;
            min-height: 100vh;
            position: relative;
        }
        
        .split-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }
        
        .split-container {
            display: flex;
            min-height: 620px;
            max-height: calc(100vh - 80px - 4rem);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .left-section {
            background: linear-gradient(135deg, #005bea, #00c6fb);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding: 110px 40px 44px;
            color: white;
            position: relative;
            flex: 1;
            min-height: 0;
            min-width: 0;
        }
        
        .left-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0, 51, 204, 0.95), rgba(59, 130, 246, 0.85));
            z-index: 1;
        }
        
        .left-content {
            position: relative;
            z-index: 2;
            max-width: 500px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            text-align: center;
        }
        
        .right-section {
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 44px 40px;
            position: relative;
            color: #1f2937;
            flex: 1;
            min-height: 0;
            min-width: 0;
        }
        
        .diagonal-shape {
            position: absolute;
            top: -20%;
            right: -10%;
            width: 60%;
            height: 140%;
            background: linear-gradient(135deg, #0033cc, #3b82f6);
            transform: rotate(15deg);
            opacity: 0.1;
            z-index: 1;
        }
        
        .right-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }
        
        .main-headline {
            font-size: 2.75rem;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 0.875rem;
            color: white;
            letter-spacing: -0.02em;
        }
        
        .headline-emphasis {
            color: #fbbf24;
        }
        
        .subheadline {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.4;
            letter-spacing: 0.01em;
        }
        
        .benefit-list {
            margin-bottom: 0.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .benefit-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            width: 2rem;
        }
        
        .benefit-title {
            font-weight: 700;
            color: #fbbf24;
            margin-right: 0.5rem;
        }
        
        .benefit-description {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .benefit-description-block {
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 0.75rem;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 3rem;
            margin-bottom: 1.5rem;
        }
        
        .btn-primary {
            background: #0033cc;
            color: white;
            padding: 0.5rem 1.25rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0, 51, 204, 0.3);
        }
        
        .btn-primary:hover {
            background: #002b99;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 51, 204, 0.4);
        }
        
        .btn-hero-login {
            padding: 0.65rem 3rem;
            font-size: 1.2rem;
            border-radius: 0.625rem;
            min-width: 200px;
        }
        
        .btn-secondary {
            background: transparent;
            color: white;
            padding: 1rem 2rem;
            border: 2px solid white;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: white;
            color: #0033cc;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.2);
        }
        
        .uaps-logo-section {
            margin-bottom: 0.75rem;
        }
        
        .logo-container {
            background: transparent;
            border-radius: 0;
            padding: 0.5rem;
            box-shadow: none;
            margin-bottom: 0.5rem;
            width: 165px;
            height: 165px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            margin-right: auto;
        }
        
        .logo-container img {
            max-width: 150px;
            max-height: 150px;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
        }
        
        .uaps-title-right {
            font-size: 2.1rem;
            font-weight: 800;
            color: #0033cc;
            margin-bottom: 0.25rem;
            text-shadow: 0 2px 4px rgba(0, 51, 204, 0.1);
        }
        
        .right-section .university-subtitle {
            font-size: 1.35rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
            font-weight: 700;
        }
        
        .acronym-section {
            text-align: left;
            max-width: 480px;
            margin: 0 auto;
            margin-top: 1.5rem;
        }
        
        .acronym-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem 1.1rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 0.85rem;
            border-left: 5px solid #0033cc;
            box-shadow: 0 4px 15px rgba(0, 51, 204, 0.1);
            transition: all 0.3s ease;
        }
        
        .acronym-item:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0, 51, 204, 0.15);
        }
        
        .acronym-letter-new {
            font-size: 1.9rem;
            font-weight: 800;
            color: #0033cc;
            width: 48px;
            text-align: center;
        }
        
        .acronym-dash-new {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0033cc;
            margin: 0 0.6rem;
        }
        
        .acronym-word-new {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0033cc;
        }
        
        .description-section {
            margin-top: 0.5rem;
            text-align: center;
        }
        
        .left-description {
            margin-top: auto;
            padding-top: 1.5rem;
        }
        
        .left-description-text {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.5;
            max-width: 500px;
            margin: 0 auto;
            text-align: center;
        }
        
        .description-text-new {
            font-size: 0.95rem;
            color: #6b7280;
            line-height: 1.5;
            max-width: 400px;
            margin: 0 auto;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            main {
                padding: 1rem;
            }
            
            .floating-container {
                padding: 1.5rem;
            }
            
            .split-layout {
                grid-template-columns: 1fr;
            }
            
            .split-container {
                flex-direction: column;
                min-height: auto;
                max-height: calc(100vh - 80px - 3rem);
            }
            
            .left-section,
            .right-section {
                min-height: 0;
                padding: 20px 16px;
            }
            
            .main-headline {
                font-size: 2.5rem;
            }
            
            .subheadline {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 768px) {
            main {
                padding: 0.5rem;
            }
            
            .floating-container {
                padding: 1rem;
                border-radius: 12px;
            }
            
            .split-container {
                flex-direction: column;
                border-radius: 8px;
                max-height: calc(100vh - 80px - 2rem);
            }
            
            .left-section,
            .right-section {
                padding: 16px 12px;
            }
            
            .main-headline {
                font-size: 2rem;
            }
            
            .uaps-title-right {
                font-size: 2.5rem;
            }
            
            .acronym-letter-new {
                font-size: 2.25rem;
                width: 56px;
            }
            
            .acronym-word-new {
                font-size: 1.45rem;
            }
            
            .cta-buttons {
                flex-direction: column;
            }
            
            .btn-primary,
            .btn-secondary {
                text-align: center;
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            main {
                padding: 0.25rem;
            }
            
            .floating-container {
                padding: 0.75rem;
                border-radius: 8px;
            }
            
            .split-container {
                flex-direction: column;
                border-radius: 6px;
                max-height: calc(100vh - 70px - 1.5rem);
            }
            
            .main-headline {
                font-size: 1.75rem;
            }
            
            .uaps-title-right {
                font-size: 1.5rem;
            }
            
            .logo-container {
                width: 110px;
                height: 110px;
            }
            
            .logo-container img {
                max-width: 100px;
                max-height: 100px;
            }
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .psu-seal {
            background: linear-gradient(135deg, #1e3a8a, #1e40af);
            box-shadow: 
                0 0 0 3px #fbbf24,
                0 10px 25px rgba(30, 64, 175, 0.4),
                inset 0 2px 4px rgba(255, 255, 255, 0.1);
        }
        
        .psu-seal-stars {
            position: absolute;
            color: #fbbf24;
            font-size: 0.5rem;
        }
        
        .psu-seal-stars.star-1 { top: 8px; left: 50%; transform: translateX(-50%); }
        .psu-seal-stars.star-2 { top: 15px; right: 12px; }
        .psu-seal-stars.star-3 { bottom: 15px; right: 12px; }
        .psu-seal-stars.star-4 { bottom: 8px; left: 50%; transform: translateX(-50%); }
        .psu-seal-stars.star-5 { bottom: 15px; left: 12px; }
        .psu-seal-stars.star-6 { top: 15px; left: 12px; }
        
        .uaps-title {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-style: italic;
            color: #000000 !important;
            text-shadow: 
                0 1px 3px rgba(0, 82, 204, 0.4),
                0 2px 6px rgba(0, 0, 0, 0.2);
            letter-spacing: 0.05em;
            line-height: 1.1;
        }
        
        .psu-subtitle {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            color: #000000 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            letter-spacing: 0.08em;
            line-height: 1.2;
        }
        
        .hamburger {
            width: 28px;
            height: 28px;
            position: relative;
            cursor: pointer;
            z-index: 50;
        }
        
        .hamburger span {
            display: block;
            height: 3px;
            width: 100%;
            background: #1e40af;
            margin-bottom: 5px;
            border-radius: 2px;
            transition: all 0.3s ease;
            transform-origin: center;
        }
        
        .hamburger span:last-child {
            margin-bottom: 0;
        }
        
        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }
        
        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(8px, -8px);
        }
        
        .mobile-menu {
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        
        .mobile-menu.active {
            transform: translateX(0);
        }

        .acronym-line {
            transition: all 0.3s ease;
        }
        
        .acronym-line:hover {
            transform: translateX(10px);
            color: #1e40af;
        }
        
        @media (max-width: 768px) {
            .desktop-nav {
                display: none;
            }
            .psu-campus-bg {
                background-attachment: scroll;
            }
            .uaps-title {
                font-size: 1.5rem;
            }
            .psu-subtitle {
                font-size: 0.75rem;
                line-height: 1.2;
            }
        }
        
        @media (max-width: 480px) {
            .uaps-title {
                font-size: 1.25rem;
            }
            .psu-subtitle {
                font-size: 0.7rem;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-menu {
                display: none !important;
            }
            .hamburger {
                display: none;
            }
        }
        
        .header-logo-container {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }
        
        .header-logo-container:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Responsive logo sizing */
        @media (max-width: 640px) {
            .header-logo-container div:first-child div {
                width: 40px !important;
                height: 40px !important;
            }
            .header-logo-container div:first-child div img {
                max-height: 38px !important;
            }
        }
        
        @media (min-width: 641px) and (max-width: 1024px) {
            .header-logo-container div:first-child div {
                width: 45px !important;
                height: 45px !important;
            }
            .header-logo-container div:first-child div img {
                max-height: 43px !important;
            }
        }
        
        @media (min-width: 1025px) {
            .header-logo-container div:first-child div {
                width: 60px !important;
                height: 60px !important;
            }
            .header-logo-container div:first-child div img {
                max-height: 58px !important;
            }
        }
        
        /* UPAS Info Box Styling */
        .uaps-info-box {
            background: rgba(255, 255, 255, 0.6);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            max-width: 600px;
            margin-left: 60px;
            position: relative;
            z-index: 10;
        }
        
        /* Acronym Letter Styling */
        .acronym-letter {
            font-size: 32px;
            font-weight: bold;
            color: #0033cc;
            width: 50px;
            display: inline-block;
        }
        
        /* Acronym Dash Styling */
        .acronym-dash {
            font-size: 32px;
            font-weight: bold;
            color: #0033cc;
            margin: 0 10px;
        }
        
        /* Acronym Word Styling */
        .acronym-word {
            font-size: 32px;
            font-weight: bold;
            color: #0033cc;
        }
        
        /* Description Text Styling */
        .description-text {
            max-width: 500px;
            margin-top: 15px;
        }
        
        .description-text p {
            font-size: 18px;
            color: #0033cc;
            line-height: 1.6;
            margin: 0;
            font-weight: normal;
            text-align: left;
        }
        
        /* CTA Button Styling */
        .cta-button {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .cta-primary {
            background: #0033cc;
            color: white;
        }
        
        .cta-primary:hover {
            background: #0028a3;
            transform: translateY(-1px);
        }
        
        .cta-secondary {
            border: 2px solid #0033cc;
            color: #0033cc;
            background: transparent;
        }
        
        .cta-secondary:hover {
            background: #0033cc;
            color: white;
        }
        
        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            .uaps-info-box {
                margin: 0 auto;
                padding: 25px;
                max-width: 90%;
                margin-left: auto;
                margin-right: auto;
            }
            
            .acronym-letter {
                font-size: 26px;
                width: 40px;
            }
            
            .acronym-dash {
                font-size: 26px;
                margin: 0 8px;
            }
            
            .acronym-word {
                font-size: 26px;
            }
            
            .description-text p {
                font-size: 16px;
            }
        }
        
        @media (max-width: 480px) {
            .uaps-info-box {
                padding: 20px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .acronym-letter {
                font-size: 22px;
                width: 35px;
            }
            
            .acronym-dash {
                font-size: 22px;
                margin: 0 6px;
            }
            
            .acronym-word {
                font-size: 22px;
            }
            
            .description-text p {
                font-size: 14px;
            }
        }
        
        .fade-in {
            animation: fadeIn 1s ease-in-out;
        }
        
        .slide-up {
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* About Us Section Styles */
        .about-section {
            padding: 80px 20px;
            background: url('{{ asset("images/psu_building.jpg") }}') center/cover no-repeat;
            position: relative;
            min-height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .about-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(2px);
        }

        .about-container {
            max-width: 1200px;
            width: 100%;
            position: relative;
            z-index: 2;
        }

        .about-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 60px 50px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
            max-width: 900px;
            margin: 0 auto;
        }

        .about-title {
            color: #0d47a1;
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 40px;
            font-family: 'Inter', sans-serif;
        }

        .about-text {
            color: #333333;
            font-size: 1.1rem;
            line-height: 1.8;
            font-family: 'Inter', sans-serif;
        }

        .about-text p {
            margin-bottom: 25px;
            text-align: justify;
        }

        .about-text p:last-child {
            margin-bottom: 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .about-section {
                padding: 60px 15px;
            }

            .about-card {
                padding: 40px 30px;
                border-radius: 15px;
            }

            .about-title {
                font-size: 2rem;
                margin-bottom: 30px;
            }

            .about-text {
                font-size: 1rem;
                line-height: 1.7;
            }
        }

        @media (max-width: 480px) {
            .about-section {
                padding: 40px 10px;
            }

            .about-card {
                padding: 30px 20px;
            }

            .about-title {
                font-size: 1.75rem;
            }
        }

        /* Focal Persons Section Styles */
        .focal-persons-section {
            padding: 80px 20px;
            background: url('{{ asset("images/psu_building.jpg") }}') center/cover no-repeat;
            position: relative;
            min-height: 700px;
        }

        .focal-persons-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(2px);
        }

        .focal-persons-container {
            max-width: 1400px;
            width: 100%;
            position: relative;
            z-index: 2;
            margin: 0 auto;
        }

        .focal-persons-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .focal-persons-title {
            color: #0d47a1;
            font-size: 2.5rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0d47a1, #1976d2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .focal-persons-content {
            display: grid;
            grid-template-columns: 1fr 1fr 400px;
            gap: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Key Personnel Styles */
        .key-personnel {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .personnel-item {
            padding: 20px;
            background: rgba(13, 71, 161, 0.05);
            border-radius: 12px;
            border-left: 4px solid #0d47a1;
            transition: all 0.3s ease;
        }

        .personnel-item:hover {
            background: rgba(13, 71, 161, 0.1);
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(13, 71, 161, 0.1);
        }

        .personnel-name {
            color: #0d47a1;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            font-family: 'Inter', sans-serif;
        }

        .personnel-position {
            color: #555;
            font-size: 0.95rem;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }

        /* Planning Coordinators Styles */
        .planning-coordinators {
            display: flex;
            flex-direction: column;
        }

        .coordinators-title {
            color: #0d47a1;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #0d47a1;
            font-family: 'Inter', sans-serif;
        }

        .coordinators-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .coordinators-column {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .coordinator-item {
            color: #333;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            border: 1px solid rgba(13, 71, 161, 0.1);
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .coordinator-item:hover {
            background: rgba(13, 71, 161, 0.05);
            border-color: #0d47a1;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(13, 71, 161, 0.1);
        }

        /* Organizational Chart Styles */
        .org-chart {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .org-chart-container {
            width: 100%;
            max-width: 380px;
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .org-chart-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .focal-persons-content {
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }

            .org-chart {
                grid-column: 1 / -1;
                margin-top: 30px;
            }

            .org-chart-container {
                max-width: 500px;
            }
        }

        @media (max-width: 768px) {
            .focal-persons-section {
                padding: 60px 15px;
            }

            .focal-persons-content {
                grid-template-columns: 1fr;
                padding: 30px 25px;
                gap: 30px;
            }

            .focal-persons-title {
                font-size: 2rem;
            }

            .coordinators-grid {
                grid-template-columns: 1fr;
            }

            .personnel-name {
                font-size: 1rem;
            }

            .personnel-position {
                font-size: 0.9rem;
            }

            .coordinator-item {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .focal-persons-section {
                padding: 40px 10px;
            }

            .focal-persons-content {
                padding: 25px 20px;
            }

            .focal-persons-title {
                font-size: 1.75rem;
            }

            .personnel-item {
                padding: 15px;
            }
        }
    </style>
</head>
<body class="psu-campus-bg min-h-screen">
    <!-- Fixed Top Header -->
    <header class="fixed-header">
        <div class="header-container">
            <!-- Left Section: Logo and University Name -->
            <div class="header-left">
                <div class="header-logo">
                    <img src="{{ asset('images/psu_logo.png') }}" 
                         alt="Pangasinan State University Seal" 
                         class="logo-image">
                </div>
                <div class="university-info">
                    <h1 class="university-title">UPAS</h1>
                    <p class="university-subtitle">Pangasinan State University</p>
                </div>
            </div>

            <!-- Right Section: Navigation Links -->
            <nav class="header-nav">
                <div class="nav-links">
                    <a href="#" class="nav-link">Home</a>
                    <a href="#about-us" class="nav-link">About Us</a>
                    <a href="#focal-persons" class="nav-link">Focal Persons</a>
                    
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="login-button">
                                Dashboard
                            </a>
                        @endauth
                    @endif
                </div>

                <!-- Mobile Hamburger -->
                <div class="hamburger md:hidden" onclick="toggleMobileMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </nav>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu">
            <div class="mobile-menu-content">
                <a href="#" class="mobile-nav-link">Home</a>
                <a href="#about-us" class="mobile-nav-link">About Us</a>
                <a href="#focal-persons" class="mobile-nav-link">Focal Persons</a>
                
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="mobile-login-button">
                            Dashboard
                        </a>
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden" onclick="closeMobileMenu()"></div>

    <!-- Main Content -->
    <main>
        <div class="floating-container">
            <div class="split-container">
                <!-- Left Section -->
                <div class="left-section">
                    <div class="left-content">
                        <!-- Main Headline -->
                        <h1 class="main-headline">
                            <span class="headline-emphasis">Plan.</span> Track. <span class="headline-emphasis">Achieve.</span>
                        </h1>
                        <p class="subheadline">University Planning Accomplishment System</p>
                        
                        <!-- Benefits List -->
                        <div class="benefit-list">
                            <div class="benefit-item">
                                <div>
                                    <span class="benefit-title">Real-time Reporting</span>
                                    <span class="benefit-description">– Access performance data instantly.</span>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <div>
                                    <span class="benefit-title">Transparency</span>
                                    <span class="benefit-description">– Clear and traceable accomplishments.</span>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <div>
                                    <span class="benefit-title">Efficiency</span>
                                    <span class="benefit-description">– Streamlined planning across campuses.</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- CTA Button -->
                        <div class="cta-buttons">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="btn-primary btn-hero-login">
                                        Log in
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="btn-primary btn-hero-login">
                                        Log in
                                    </a>
                                @endauth
                            @endif
                        </div>
                        
                        <!-- Description (bottom of blue section) -->
                        <div class="left-description">
                            <p class="left-description-text">
                                A University Planning Accomplishment System can help streamline operations, promote transparency, and support timely data reporting and analysis across different campuses.
                            </p>
                        </div>
                    </div>
            </div>
            
            <!-- Right Section -->
            <div class="right-section">
                <!-- Diagonal Shape -->
                <div class="diagonal-shape"></div>
                
                <div class="right-content">
                    <!-- UPAS Logo -->
                    <div class="uaps-logo-section">
                        <div class="logo-container">
                            <img src="{{ asset('images/psu_logo.png') }}" alt="Pangasinan State University Logo">
                        </div>
                        <p class="university-subtitle">Pangasinan State University</p>
                    </div>
                    
                    <!-- Acronym Breakdown -->
                    <div class="acronym-section">
                        <div class="acronym-item">
                            <span class="acronym-letter-new">U</span>
                            <span class="acronym-dash-new">-</span>
                            <span class="acronym-word-new">University</span>
                        </div>
                        <div class="acronym-item">
                            <span class="acronym-letter-new">P</span>
                            <span class="acronym-dash-new">-</span>
                            <span class="acronym-word-new">Planning</span>
                        </div>
                        <div class="acronym-item">
                            <span class="acronym-letter-new">A</span>
                            <span class="acronym-dash-new">-</span>
                            <span class="acronym-word-new">Accomplishment</span>
                        </div>
                        <div class="acronym-item">
                            <span class="acronym-letter-new">S</span>
                            <span class="acronym-dash-new">-</span>
                            <span class="acronym-word-new">System</span>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </main>

    <!-- About Us Section -->
    <section id="about-us" class="about-section">
        <div class="about-container">
            <div class="about-card">
                <div class="about-content">
                    <h2 class="about-title">About Us</h2>
                    <div class="about-text">
                        <p>
                            In 1979 under Presidential Decree No. 1497, Pangasinan State University (PSU) is a leading academic institution in Region I, uniting multiple campuses across Pangasinan. Committed to integrity, excellence, and service, PSU aspires to become an ASEAN-recognized university of choice, advancing education, research, and community engagement in Northern Luzon.
                        </p>
                        <p>
                            The University Planning Office (UPO) plays a critical role in institutional development, overseeing strategic planning, performance monitoring, and alignment with national priorities like the Philippine Development Plan (PDP). It ensures compliance with regulatory frameworks set by CHED and government agencies, manages the Annual Operational Plan (AOP), coordinates campus data, and facilitates assessments and reports such as the Performance-Based Bonus (PBB) and SUC Levelling. Through collaborative leadership, the UPO drives accountability, evidence-based decision-making, and continuous improvement within PSU.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Focal Person Section -->
    <section id="focal-persons" class="focal-persons-section">
        <div class="focal-persons-container">
            <div class="focal-persons-header">
                <h2 class="focal-persons-title">Focal Persons</h2>
            </div>
            
            <div class="focal-persons-content">
                <!-- Left Column - Key Personnel -->
                <div class="key-personnel">
                    <div class="personnel-item">
                        <h3 class="personnel-name">DR. ELBERT M. GALAS</h3>
                        <p class="personnel-position">University President</p>
                    </div>
                    <div class="personnel-item">
                        <h3 class="personnel-name">DR. ELMER C. VINGUA</h3>
                        <p class="personnel-position">VP for Administration and Finance Management</p>
                    </div>
                    <div class="personnel-item">
                        <h3 class="personnel-name">GREGORIO F. DELOS ANGELES JR.</h3>
                        <p class="personnel-position">Director, University Planning</p>
                    </div>
                    <div class="personnel-item">
                        <h3 class="personnel-name">CARLA JOY O. DELA CRUZ</h3>
                        <p class="personnel-position">Planning Officer I</p>
                    </div>
                    <div class="personnel-item">
                        <h3 class="personnel-name">PERLA R. MALICDEM</h3>
                        <p class="personnel-position">Statistician III</p>
                    </div>
                </div>

                <!-- Middle Column - Planning Coordinators -->
                <div class="planning-coordinators">
                    <h3 class="coordinators-title">Planning Coordinators</h3>
                    <div class="coordinators-grid">
                        <div class="coordinators-column">
                            <div class="coordinator-item">PRINCESS N. GALANTA</div>
                            <div class="coordinator-item">JANNA D. ARCE</div>
                            <div class="coordinator-item">JASON B. SARMIENTO</div>
                            <div class="coordinator-item">RUBENAL SORIANO JR.</div>
                            <div class="coordinator-item">ROFER-JAY FERRER</div>
                            <div class="coordinator-item">REXIAN NOAH S. ZARENA</div>
                        </div>
                        <div class="coordinators-column">
                            <div class="coordinator-item">GEIEN B. TOLENTINO</div>
                            <div class="coordinator-item">MARICHRISNC. SAN-PEDRO</div>
                            <div class="coordinator-item">WILLIAM L. DELE-A CRUZ</div>
                            <div class="coordinator-item">CHRISTIAN THOM F. TABISOLA</div>
                            <div class="coordinator-item">ROSELYN M. VILLARUZ</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Organizational Chart -->
                <div class="org-chart">
                    <div class="org-chart-container">
                        <img src="{{ asset('images/focal_persons.png') }}" 
                             alt="University Planning Office Organizational Chart" 
                             class="org-chart-image">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-center py-8 text-white fade-in">
    </footer>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('mobile-overlay');
            const hamburger = document.querySelector('.hamburger');
            
            menu.classList.toggle('active');
            overlay.classList.toggle('hidden');
            hamburger.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if (menu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        function closeMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('mobile-overlay');
            const hamburger = document.querySelector('.hamburger');
            
            menu.classList.remove('active');
            overlay.classList.add('hidden');
            hamburger.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close mobile menu when clicking outside or pressing escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeMobileMenu();
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                // If href is just '#', scroll to top
                if (!href || href === '#') {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                    return;
                }
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
