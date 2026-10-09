<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo get_setting('site_name', 'Newspower'); ?>
    </title>
    <meta name="description"
        content="<?php echo isset($meta_description) ? $meta_description : get_setting('site_description', 'Newspower - Your trusted source for latest news and updates'); ?>">
    <meta name="keywords"
        content="<?php echo get_setting('meta_keywords', 'news, newspower, latest news, breaking news, politics, business, entertainment, sports'); ?>">
<?php
$site_base = rtrim(SITE_URL, '/');
$og_title  = isset($og_title) ? $og_title : (isset($page_title) ? $page_title : get_setting('site_name', 'Newspower'));
$og_desc   = isset($meta_description) ? $meta_description : get_setting('site_description', 'Newspower - Your trusted source for latest news and updates');
$og_type   = isset($og_type) ? $og_type : 'website';
$og_image  = isset($og_image) && $og_image ? $og_image : $site_base . '/og-default.jpg';
if (!preg_match('#^https?://#i', $og_image)) {
    $og_image = $site_base . '/' . ltrim($og_image, '/');
}
$og_url = isset($og_url) ? $og_url : $site_base . $_SERVER['REQUEST_URI'];
?>
<meta property="og:site_name" content="<?php echo htmlspecialchars(get_setting('site_name', 'Newspower')); ?>">
<meta property="og:type" content="<?php echo htmlspecialchars($og_type); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars(strip_tags($og_title)); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars(strip_tags($og_desc)); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">

<meta property="og:url" content="<?php echo htmlspecialchars($og_url); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars(strip_tags($og_title)); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars(strip_tags($og_desc)); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITE_URL; ?>/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo SITE_URL; ?>/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo SITE_URL; ?>/favicon-16x16.png">
<link rel="manifest" href="<?php echo SITE_URL; ?>/site.webmanifest">
<link rel="icon" href="<?php echo SITE_URL; ?>/favicon.ico" type="image/x-icon">


    <style>
        :root {
            --primary-color: rgb(32, 57, 97);
            --secondary-color: rgb(238, 32, 66);
            --background-color: #ffffff;
            --text-color: #000000;
            --light-gray: #f8f9fa;
            --border-color: #dee2e6;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            background-color: var(--background-color);
            line-height: 1.6;
        }

        .navbar-brand {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color) !important;
            text-decoration: none;
        }

        .navbar-brand:hover {
            color: var(--secondary-color) !important;
        }

        .navbar {
            background-color: var(--background-color) !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }

        .navbar-nav .nav-link {
            color: var(--text-color) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: color 0.3s ease;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: var(--secondary-color) !important;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .text-secondary {
            color: var(--secondary-color) !important;
        }

        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .bg-light-custom {
            background-color: var(--light-gray);
        }

        .border-primary {
            border-color: var(--primary-color) !important;
        }

        .category-badge {
            background-color: var(--secondary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }

        .category-badge:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .featured-card .card-img-top {
            height: 300px;
        }

        .news-meta {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .news-meta i {
            margin-right: 0.25rem;
        }

        .social-share {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .social-share a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .social-share a:hover {
            transform: scale(1.1);
        }

        .social-share .facebook {
            background-color: #1877f2;
        }

        .social-share .twitter {
            background-color: #1da1f2;
        }

        .social-share .linkedin {
            background-color: #0077b5;
        }

        .social-share .whatsapp {
            background-color: #25d366;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            .navbar-brand {
                font-size: 1.5rem;
            }
        }
        /* Enhanced Article Content Styling */

/* Image handling in articles */
.article-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.article-content img:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

/* Image alignment classes */
.article-content img .img-center,
.article-content .img-center {
    display: block;
    margin: 25px auto;
    text-align: center;
}

.article-content img .img-left,
.article-content .img-left {
    float: left;
    margin: 10px 25px 15px 0;
    max-width: 45%;
}

.article-content img .img-right,
.article-content .img-right {
    float: right;
    margin: 10px 0 15px 25px;
    max-width: 45%;
}

/* Image size variations */
.article-content img .img-small { max-width: 250px; }
.article-content img .img-medium { max-width: 450px; }
.article-content img .img-large { max-width: 700px; }
.article-content img .img-full { width: 100%; }

/* Image captions */
.image-caption {
    font-size: 0.9em;
    color: #6c757d;
    text-align: center;
    font-style: italic;
    margin: 10px auto 20px;
    max-width: 600px;
    line-height: 1.4;
}

/* Enhanced typography */
.article-content {
    font-size: 16px;
    line-height: 1.7;
    color: black;
    max-width: 800px;
    margin: 0 auto;
}

.article-content h1 {
    font-size: 2.5em;
    font-weight: 700;
    color: var(--primary-color);
    margin: 35px 0 20px;
    line-height: 1.2;
}

.article-content h2 {
    font-size: 2em;
    font-weight: 600;
    color: var(--primary-color);
    margin: 30px 0 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}

.article-content h3 {
    font-size: 1.5em;
    font-weight: 600;
    color: var(--primary-hover);
    margin: 25px 0 12px;
}

.article-content h4 {
    font-size: 1.25em;
    font-weight: 600;
    color: var(--primary-hover);
    margin: 20px 0 10px;
}

.article-content p {
    margin-bottom: 18px;
    text-align: justify;
    text-justify: inter-word;
    color: var(--primary-color);
}

.article-content p:first-of-type::first-letter {
    font-size: 3.5em;
    font-weight: bold;
    color: var(--secondary-color);
    float: left;
    line-height: 1;
    margin: 0 8px 0 0;
    padding: 0;
}

/* Enhanced blockquotes */
.article-content blockquote {
    position: relative;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: none;
    border-left: 4px solid var(--secondary-color);
    margin: 25px 0;
    padding: 20px 25px;
    font-style: italic;
    font-size: 1.1em;
    border-radius: 0 8px 8px 0;
}

.article-content blockquote::before {
    content: '"';
    font-size: 4em;
    color: var(--secondary-color);
    position: absolute;
    top: -10px;
    left: 15px;
    font-family: serif;
}

/* Enhanced lists */
.article-content ul,
.article-content ol {
    margin: 20px 0;
    padding-left: 25px;
}

.article-content ul li,
.article-content ol li {
    margin-bottom: 8px;
    line-height: 1.6;
}

.article-content ul li::marker {
    color: var(--secondary-color);
    font-size: 1.2em;
}

/* Code blocks */
.article-content pre {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    overflow-x: auto;
    font-family: 'Courier New', monospace;
}

.article-content code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    color: #e83e8c;
}

/* Article tags styling */
.article-tags {
    margin: 30px 0 20px;
    padding: 20px 0;
    border-top: 1px solid #e9ecef;
    text-align: center;
}

.article-tags h6 {
    color: var(--primary-color);
    margin-bottom: 15px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.tag-item {
    display: inline-block;
    background: linear-gradient(45deg, var(--primary-color), var(--primary-hover));
    color: white;
    padding: 8px 15px;
    margin: 5px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.tag-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    color: white;
    text-decoration: none;
}

/* Tag input enhancement */
.tag-input-container {
    position: relative;
}

.tag-preview {
    min-height: 40px;
    border: 1px dashed #dee2e6;
    border-radius: 8px;
    padding: 10px;
    margin-top: 8px;
    background: #f8f9fa;
}

.tag-suggestions {
    margin-top: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.tag-suggestions .badge {
    cursor: pointer;
    transition: all 0.2s ease;
}

.tag-suggestions .badge:hover {
    background-color: var(--primary-color) !important;
    color: white !important;
    transform: scale(1.05);
}

/* Article meta information */
.article-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 0;
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 25px;
    font-size: 14px;
    color: #6c757d;
}

.article-meta .author {
    display: flex;
    align-items: center;
}

.article-meta .author img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .article-content {
        font-size: 15px;
        padding: 0 15px;
    }
    
    .article-content h1 {
        font-size: 2em;
    }
    
    .article-content h2 {
        font-size: 1.7em;
    }
    
    .article-content img.left,
    .article-content img.right {
        float: none;
        display: block;
        margin: 15px auto;
        max-width: 90%;
    }
    
    .article-content p:first-of-type::first-letter {
        font-size: 2.5em;
    }
}

/* Print styles */
@media print {
    .article-content img {
        max-width: 100%;
        page-break-inside: avoid;
    }
    
    .article-tags,
    .tag-suggestions {
        display: none;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .article-content {
        color: #e9ecef;
    }
    
    .article-content blockquote {
        background: linear-gradient(135deg, #2c2c2c 0%, #1a1a1a 100%);
        color: #e9ecef;
    }
    
    .tag-preview {
        background: #2c2c2c;
        border-color: #495057;
    }
}

/* Accessibility improvements */
.article-content img[alt=""] {
    border: 2px solid #dc3545;
}

.article-content img:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Animation for content loading */
.article-content {
    animation: fadeIn 0.6s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
    </style>

    <!-- Additional page-specific CSS -->
    <?php if (isset($additional_css))
        echo $additional_css; ?>
</head>

<body>
    <!-- Navigation -->
    <?php include 'nav.php'; ?>

    <!-- Flash Messages -->
    <?php
    $flash = get_flash_message();
    if ($flash):
        ?>
        <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show"
            role="alert">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>