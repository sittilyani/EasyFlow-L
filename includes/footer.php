<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="../assets/css/bootstrap.css" type="text/css">
        <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
        <style>
                body {
                        margin: 0;
                        padding: 0;
                        font-family: Tahoma, Geneva, sans-serif;
                }
                .footer {
                        background: #722182;
                        color: white;
                        height: 80px;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        position: fixed;
                        margin-top: 10px;
                        bottom: 0;
                        left: 0;
                        width: 100%;
                        z-index: 10;
                }
                .footer-content {
                        display: flex;
                        align-items: center;
                        font-family: inherit;
                        font-size: 22px;
                        gap: 15px;
                }
                .social-links {
                        display: flex;
                        gap: 12px;
                        align-items: center;
                }
                .social-links a {
                        color: white;
                        font-size: 24px;
                        transition: transform 0.3s, opacity 0.3s;
                        text-decoration: none;
                }
                .social-links a:hover {
                        transform: scale(1.2);
                        opacity: 0.8;
                }
                /* Tablet Styles */
        @media screen and (max-width: 768px) {
            .footer-content {
                font-size: 16px;
                flex-direction: column;
                gap: 8px;
            }
            .social-links a {
                font-size: 20px;
            }
        }
        /* Mobile Styles */
        @media screen and (max-width: 480px) {
            .footer {
                padding: 15px 10px;
                height: auto;
            }
            .footer-content {
                font-size: 14px;
            }
            .social-links a {
                font-size: 18px;
            }
        }
        </style>
</head>
<body>
        <!-- Your content goes here -->
        <div class="footer">
                <div class="footer-content">
                        <span>EasyFlow-L: an Integrated Opioid Replacement Patient Management System - developed by LVCTHealth - Stawisha Pwani Project <?php echo date('Y');?> - &copy; LVCT@20</span>
                        <div class="social-links">
                                <a href="https://web.facebook.com/LVCTHealth/" target="_blank" rel="noopener noreferrer" title="Facebook">
                                        <i class="fab fa-facebook"></i>
                                </a>
                                <a href="https://www.youtube.com/user/TheLVCT" target="_blank" rel="noopener noreferrer" title="YouTube">
                                        <i class="fab fa-youtube"></i>
                                </a>
                                <a href="https://x.com/LVCTKe" target="_blank" rel="noopener noreferrer" title="X (Twitter)">
                                        <i class="fab fa-x-twitter"></i>
                                </a>
                                <a href="https://www.instagram.com/lvct_health/" target="_blank" rel="noopener noreferrer" title="Instagram">
                                        <i class="fab fa-instagram"></i>
                                </a>
                                <a href="https://www.linkedin.com/company/lvcthealth/" target="_blank" rel="noopener noreferrer" title="LinkedIn">
                                        <i class="fab fa-linkedin"></i>
                                </a>
                        </div>
                </div>
        </div>
</body>
</html>