<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle form submission
$form_submitted = false;
$form_error = '';

if ($_POST && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $form_error = 'Security token validation failed. Please try again.';
    } else {
        // Get and sanitize form data
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $subject = sanitize_input($_POST['subject'] ?? '');
        $message = sanitize_input($_POST['message'] ?? '');
        
        // Validate form data
        if (empty($name)) {
            $form_error = 'Please enter your name.';
        } elseif (empty($email) || !is_valid_email($email)) {
            $form_error = 'Please enter a valid email address.';
        } elseif (empty($subject)) {
            $form_error = 'Please enter a subject.';
        } elseif (empty($message)) {
            $form_error = 'Please enter your message.';
        } elseif (strlen($message) < 10) {
            $form_error = 'Your message must be at least 10 characters long.';
        } else {
            // Save contact message
            if (save_contact_message($name, $email, $subject, $message)) {
                $form_submitted = true;
                set_flash_message('Thank you for your message! We will get back to you soon.', 'success');
            } else {
                $form_error = 'There was an error sending your message. Please try again.';
            }
        }
    }
}

// Get contact page content from database
$contact_page = get_page_by_slug('contact');

// Page meta data
$page_title = $contact_page ? $contact_page['title'] : 'Contact Us';
$meta_description = $contact_page ? ($contact_page['meta_description'] ?: 'Get in touch with us for any inquiries or feedback') : 'Contact us for news tips, feedback, or general inquiries';

include 'includes/header.php';
?>

<!-- Page Header -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-envelope me-3"></i>Contact Us
                </h1>
                <p class="lead mb-0 opacity-75">
                    Get in touch with the <?php echo get_setting('site_name', 'Newspower'); ?> team
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Content -->
<section class="py-5">
    <div class="container">
        <!-- First Row: Contact Form + Contact Info -->
        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-8 mb-4">
                <?php if ($contact_page): ?>
                    <div class="contact-intro mb-5">
                        <?php echo $contact_page['content']; ?>
                    </div>
                <?php else: ?>
                    <div class="contact-intro mb-5">
                        <h2 class="text-primary mb-4">We'd Love to Hear from You</h2>
                        <p class="lead">
                            Have a news tip, feedback, or question? Our team at <?php echo get_setting('site_name', 'Newspower'); ?> is here to help.
                        </p>
                        <p>
                            Whether you want to share a story, report an issue, or just say hello, we value your input and are committed to responding promptly to all inquiries.
                        </p>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-paper-plane me-2"></i>Send us a Message
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($form_submitted): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Message Sent Successfully!</strong>
                                <p class="mb-0 mt-2">Thank you for contacting us. We'll get back to you as soon as possible.</p>
                            </div>
                        <?php else: ?>
                            <?php if ($form_error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo htmlspecialchars($form_error); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Form -->
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name"
                                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                               required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                               required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">Select a subject...</option>
                                        <option value="General Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'General Inquiry') ? 'selected' : ''; ?>>General Inquiry</option>
                                        <option value="News Tip" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'News Tip') ? 'selected' : ''; ?>>News Tip</option>
                                        <option value="Feedback" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Feedback') ? 'selected' : ''; ?>>Feedback</option>
                                        <option value="Technical Issue" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Technical Issue') ? 'selected' : ''; ?>>Technical Issue</option>
                                        <option value="Partnership" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Partnership') ? 'selected' : ''; ?>>Partnership Opportunity</option>
                                        <option value="Media Inquiry" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Media Inquiry') ? 'selected' : ''; ?>>Media Inquiry</option>
                                        <option value="Other" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="message" name="message" rows="6" 
                                              placeholder="Please provide as much detail as possible..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                                    <div class="form-text">Minimum 10 characters required.</div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Send Message
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>Contact Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (get_setting('contact_email')): ?>
                        <div class="contact-item mb-3 d-flex align-items-start">
                            <i class="fas fa-envelope text-primary"></i>
                            <div class="ms-3">
                                <h6 class="mb-1">Email</h6>
                                <a href="mailto:<?php echo get_setting('contact_email'); ?>" class="text-decoration-none">
                                    <?php echo get_setting('contact_email'); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (get_setting('contact_phone')): ?>
                        <div class="contact-item mb-3 d-flex align-items-start">
                            <i class="fas fa-phone text-primary"></i>
                            <div class="ms-3">
                                <h6 class="mb-1">Phone</h6>
                                <a href="tel:<?php echo get_setting('contact_phone'); ?>" class="text-decoration-none">
                                    <?php echo get_setting('contact_phone'); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (get_setting('contact_address')): ?>
                        <div class="contact-item mb-3 d-flex align-items-start">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                            <div class="ms-3">
                                <h6 class="mb-1">Address</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars(get_setting('contact_address'))); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="contact-item mb-0 d-flex align-items-start">
                            <i class="fas fa-clock text-primary"></i>
                            <div class="ms-3">
                                <h6 class="mb-1">Response Time</h6>
                                <p class="mb-0 small text-muted">We typically respond within 24-48 hours</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row: FAQ + Social Media -->
        <div class="row">
            <!-- FAQ -->
            <div class="col-lg-8 mb-4 mt-4">
                <h3 class="text-primary mb-4">Frequently Asked Questions</h3>
                <div class="accordion" id="faqAccordion">
                   <!-- FAQ Section -->
                <div class="mt-5">
                    <h3 class="text-primary mb-4">Frequently Asked Questions</h3>
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    How quickly do you respond to messages?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We typically respond to all inquiries within 24-48 hours during business days. Urgent news tips may receive faster responses.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Do you accept guest articles or submissions?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! We welcome high-quality guest submissions. Please use the contact form with "Partnership" as the subject and include details about your proposed article or content.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    How can I report an error in an article?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We take accuracy seriously. Please contact us with "Feedback" as the subject, include the article URL, and describe the error. We'll investigate and make corrections promptly.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Can I advertise on your website?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, we offer various advertising opportunities. Please contact us with "Partnership" as the subject to discuss advertising options and rates.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <!-- Social Media -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-share-alt me-2"></i>Follow Us
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <p class="mb-3">Stay connected for the latest updates</p>
                        <div class="d-flex justify-content-center gap-3">
                            <?php
                            $social_links = [
                                'facebook' => ['icon' => 'fab fa-facebook-f'],
                                'twitter' => ['icon' => 'fab fa-twitter'],
                                'linkedin' => ['icon' => 'fab fa-linkedin-in'],
                                'instagram' => ['icon' => 'fab fa-instagram']
                            ];
                            
                            foreach ($social_links as $platform => $config):
                                $url = get_setting("social_$platform");
                                if ($url):
                            ?>
                            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="<?php echo $config['icon']; ?>"></i>
                            </a>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>